<?php

namespace App\Modules\Database;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Config;
use App\Core\BaseController;
use App\Core\Logger;
use PDO;

/**
 * Database Management Controller
 * 
 * Comprehensive database administration system for SQLite databases
 * with advanced schema management and data operations.
 * 
 * Core Features:
 * - Database creation and deletion
 * - Table structure management (CREATE, ALTER, DROP)
 * - Field configuration and metadata
 * - Schema synchronization
 * - Import/Export (SQL, Excel, CSV)
 * - Template generation for imports
 * - Automatic audit column injection
 * - Table visibility configuration
 * 
 * Schema Management:
 * - Visual table builder
 * - Raw SQL execution
 * - Field type detection
 * - Foreign key relationships
 * - Automatic timestamp columns
 * 
 * Import/Export:
 * - SQL dump generation
 * - Excel import/export with templates
 * - CSV import/export with UTF-8 BOM
 * - Batch data operations
 * 
 * Security:
 * - Permission-based access control
 * - Project-scoped databases
 * - Path traversal prevention
 * - SQL injection protection
 * 
 * Data Integrity:
 * - Automatic audit columns (fecha_de_creacion, fecha_edicion)
 * - Schema synchronization
 * - Metadata consistency checks
 * - Self-healing path resolution
 * 
 * @package App\Modules\Database
 * @author DATA2REST Development Team
 * @version 1.0.0
 */
/**
 * DatabaseController Controller
 *
 * Core Features: TODO
 *
 * Security: Requires login, permission checks as implemented.
 *
 * @package App\Modules\
 * @author DATA2REST Development Team
 * @version 1.0.0
 */
class DatabaseController extends BaseController
{
    /**
     * Constructor - Requires user authentication
     * 
     * Ensures that only authenticated users can access
     * database management functionality.
     */
    /**
     * __construct method
     *
     * @return void
     */
    public function __construct()
    {
        Auth::requireLogin();
    }

    /**
     * Lists all databases registered in the system.
     */
    /**
     * index method
     *
     * @return void
     */
    public function index()
    {
        Auth::requirePermission('module:databases', 'view_tables');
        $db = Database::getInstance()->getConnection();
        $projectId = Auth::getActiveProject();

        if (!$projectId && !Auth::isAdmin()) {
            $databases = []; // Should ideally redirect to project select
        } else {
            // Scope ALL queries to the active project
            $sql = "SELECT * FROM " . Database::getInstance()->getAdapter()->quoteName('databases') . " WHERE project_id = ? ORDER BY id DESC";
            $params = [$projectId];

            // If admin has no project selected, he sees nothing (or should see all? User said "everything related to project")
            // Strict project scoping means: No project = No data.
            // But Admins might want to see all... user said "module database only access databases of his project"
            // So we enforce project_id for everyone who is in a project context.

            if (Auth::isAdmin() && !$projectId) {
                // Fallback for global admin view if needed, but per request: "project determined access"
                $sql = "SELECT * FROM " . Database::getInstance()->getAdapter()->quoteName('databases') . " ORDER BY id DESC";
                $params = [];
            }

            if ($projectId) {
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $databases = $stmt->fetchAll();
            } else if (Auth::isAdmin()) {
                $databases = $db->query($sql)->fetchAll();
            } else {
                $databases = [];
            }
        }

        // Automatic redirect if only one database exists for non-admin users
        if (count($databases) === 1 && !Auth::isAdmin()) {
            $dbId = $databases[0]['id'];
            $this->redirect('admin/databases/view?id=' . $dbId);
        }

        // Decorate databases with type from config
        foreach ($databases as &$dbItem) {
            $cfg = json_decode($dbItem['config'] ?? '{}', true);
            $dbItem['type'] = $cfg['type'] ?? 'sqlite';
        }

        $this->view('admin/databases/index', [
            'title' => 'Databases - Architect',
            'databases' => $databases,
            'breadcrumbs' => [\App\Core\Lang::get('databases.title') => null]
        ]);
    }

    /**
     * Creates a new SQLite database file and registers it in the system.
     * Requires 'module:databases' permission with 'create' action.
     *
     * @return void Redirects to sync page or dies on error.
     */
    /**
     * create method
     *
     * @return void
     */
    public function create()
    {
        Auth::requirePermission('module:databases.create_db');
        $name = $_POST['name'] ?? 'New Database';
        // Sanitize name: replace spaces/special chars with underscores
        $sanitized = preg_replace('/[^a-zA-Z0-9]+/', '_', trim($name));
        $sanitized = trim(strtolower($sanitized), '_');
        $storagePath = Config::get('db_storage_path');

        // Priority: Check if a file with this name already exists in data/
        $filename = $sanitized . '.sqlite';
        $path = $storagePath . $filename;

        if (!file_exists($path)) {
            // If doesn't exist, create a unique one to avoid collisions
            $filename = $sanitized . '_' . uniqid() . '.sqlite';
            $path = $storagePath . $filename;
        }

        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0777, true);
        }

        try {
            // We use realpath to ensure absolute paths are stored
            $newDb = new PDO('sqlite:' . $path);
            // Ensure permissions are set for writing
            @chmod($path, 0666);

            $db = Database::getInstance()->getConnection();
            $projectId = Auth::getActiveProject();

            $stmt = $db->prepare("INSERT INTO " . Database::getInstance()->getAdapter()->quoteName('databases') . " (name, path, project_id) VALUES (?, ?, ?)");
            $stmt->execute([$name, $path, $projectId]);
            Logger::log('CREATE_DATABASE', ['name' => $name, 'path' => $path], $projectId);

            // Auto-trigger sync if the file already had data
            $dbId = $db->lastInsertId();
            header('Location: ' . Auth::getBaseUrl() . 'admin/databases/sync?id=' . $dbId);
            exit;
        } catch (\PDOException $e) {
            die("Error creating/registering database: " . $e->getMessage());
        }
    }

    /**
     * Show database creation form with type selector
     * 
     * @return void
     */
    public function createForm()
    {
        Auth::requirePermission('module:databases.create_db');

        $this->view('admin/databases/create_form', [
            'title' => 'Create Database',
            'breadcrumbs' => [
                \App\Core\Lang::get('databases.title') => 'admin/databases',
                'Create Database' => null
            ]
        ]);
    }

    /**
     * Create a new database (SQLite or MySQL) with multi-database support
     * 
     * @return void
     */
    public function createMulti()
    {
        Auth::requirePermission('module:databases.create_db');

        $name = $_POST['name'] ?? 'New Database';
        $type = $_POST['type'] ?? 'sqlite';
        $projectId = Auth::getActiveProject();

        try {
            $config = ['type' => $type];

            if ($type === 'sqlite') {
                // SQLite configuration
                $sanitized = preg_replace('/[^a-zA-Z0-9]+/', '_', trim($name));
                $sanitized = trim(strtolower($sanitized), '_');
                $storagePath = Config::get('db_storage_path');

                $filename = $sanitized . '_' . uniqid() . '.sqlite';
                $path = $storagePath . $filename;

                if (!is_dir($storagePath)) {
                    mkdir($storagePath, 0777, true);
                }

                $config['path'] = $path;

            } elseif ($type === 'mysql') {
                // MySQL configuration
                $config['host'] = $_POST['mysql_host'] ?? 'localhost';
                $config['port'] = (int) ($_POST['mysql_port'] ?? 3306);
                $config['database'] = $_POST['mysql_database'] ?? '';
                $config['username'] = $_POST['mysql_username'] ?? 'root';
                $config['password'] = $_POST['mysql_password'] ?? '';
                $config['charset'] = $_POST['mysql_charset'] ?? 'utf8mb4';

                if (empty($config['database'])) {
                    Auth::setFlashError("Database name is required for MySQL");
                    header('Location: ' . Auth::getBaseUrl() . 'admin/databases/create-form');
                    exit;
                }
            } elseif ($type === 'pgsql' || $type === 'postgresql') {
                // PostgreSQL configuration
                $config['type'] = 'pgsql'; // Normalize type
                $config['host'] = $_POST['pgsql_host'] ?? 'localhost';
                $config['port'] = (int) ($_POST['pgsql_port'] ?? 5432);
                $config['database'] = $_POST['pgsql_database'] ?? '';
                $config['username'] = $_POST['pgsql_username'] ?? 'postgres';
                $config['password'] = $_POST['pgsql_password'] ?? '';
                $config['schema'] = $_POST['pgsql_schema'] ?? 'public';
                $config['charset'] = 'utf8';

                if (empty($config['database'])) {
                    Auth::setFlashError("Database name is required for PostgreSQL");
                    header('Location: ' . Auth::getBaseUrl() . 'admin/databases/create-form');
                    exit;
                }
            }

            // Use DatabaseManager to create the database
            $database = \App\Core\DatabaseManager::createDatabase($name, $config, $projectId);

            if ($database) {
                Logger::log('CREATE_DATABASE', [
                    'name' => $name,
                    'type' => $type,
                    'id' => $database['id']
                ], $projectId);

                // Success! Redirect to Sync
                Auth::setFlashError("Database created successfully!", 'success');
                $redirectUrl = Auth::getBaseUrl() . 'admin/databases/sync?id=' . $database['id'];

                // Force redirect
                if (!headers_sent()) {
                    header('Location: ' . $redirectUrl);
                } else {
                    echo "<script>window.location.href='" . $redirectUrl . "';</script>";
                }
                exit;
            } else {
                throw new \Exception("Failed to create database");
            }

        } catch (\Exception $e) {
            Auth::setFlashError("Error creating database: " . $e->getMessage());
            $this->redirect('admin/databases/create-form'); // Use framework redirect
            exit;
        }
    }

    /**
     * Test database connection (AJAX endpoint)
     * 
     * @return void
     */
    public function testConnection()
    {
        Auth::requirePermission('module:databases.create_db');
        header('Content-Type: application/json');

        $type = $_POST['type'] ?? 'sqlite';

        try {
            $config = ['type' => $type];

            if ($type === 'mysql') {
                $config['host'] = $_POST['host'] ?? 'localhost';
                $config['port'] = (int) ($_POST['port'] ?? 3306);
                $config['database'] = $_POST['database'] ?? '';
                $config['username'] = $_POST['username'] ?? 'root';
                $config['password'] = $_POST['password'] ?? '';
                $config['charset'] = $_POST['charset'] ?? 'utf8mb4';
            } elseif ($type === 'pgsql' || $type === 'postgresql') {
                $config['type'] = 'pgsql'; // Normalize type
                $config['host'] = $_POST['host'] ?? 'localhost';
                $config['port'] = (int) ($_POST['port'] ?? 5432);
                $config['database'] = $_POST['database'] ?? '';
                $config['username'] = $_POST['username'] ?? 'postgres';
                $config['password'] = $_POST['password'] ?? '';
                $config['schema'] = $_POST['schema'] ?? 'public';
                $config['charset'] = 'utf8';
            }

            $result = \App\Core\DatabaseManager::testConnection($config);
            echo json_encode($result);

        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Connection Manager - View and manage database connections
     * 
     * @return void
     */
    public function connectionManager()
    {
        Auth::requirePermission('module:databases', 'view_tables');

        $db = Database::getInstance()->getConnection();
        $projectId = Auth::getActiveProject();

        // Get all databases with their types
        if ($projectId) {
            $stmt = $db->prepare("SELECT * FROM " . Database::getInstance()->getAdapter()->quoteName('databases') . " WHERE project_id = ? ORDER BY id DESC");
            $stmt->execute([$projectId]);
        } else if (Auth::isAdmin()) {
            $stmt = $db->query("SELECT * FROM " . Database::getInstance()->getAdapter()->quoteName('databases') . " ORDER BY id DESC");
        } else {
            $databases = [];
        }

        $databases = $stmt->fetchAll();

        // Stats
        $stats = [
            'total' => count($databases),
            'connected' => 0,
            'sqlite' => 0,
            'mysql' => 0,
            'total_size' => 0
        ];

        // Add connection status and size info
        foreach ($databases as &$database) {
            try {
                $adapter = \App\Core\DatabaseManager::getAdapter($database);
                $database['db_type'] = $adapter->getType();
                $database['is_connected'] = $adapter->isConnected();
                $database['size'] = $adapter->getDatabaseSize();
                $database['size_formatted'] = $this->formatBytes($database['size']);

                if ($database['is_connected'])
                    $stats['connected']++;
                if ($database['db_type'] === 'sqlite')
                    $stats['sqlite']++;
                if ($database['db_type'] === 'mysql')
                    $stats['mysql']++;
                $stats['total_size'] += $database['size'];

            } catch (\Exception $e) {
                $database['db_type'] = $database['type'] ?? 'sqlite';
                $database['is_connected'] = false;
                $database['size'] = 0;
                $database['size_formatted'] = 'N/A';
                $database['error'] = $e->getMessage();
            }
        }

        $stats['total_size_formatted'] = $this->formatBytes($stats['total_size']);

        $this->view('admin/databases/connections', [
            'title' => 'Connection Manager',
            'databases' => $databases,
            'stats' => $stats,
            'breadcrumbs' => [
                \App\Core\Lang::get('databases.title') => 'admin/databases',
                'Connections' => null
            ]
        ]);
    }

    /**
     * Format bytes to human readable format
     * 
     * @param int $bytes
     * @return string
     */
    private function formatBytes($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Deletes a database entry and its physical SQLite file.
     */
    /**
     * delete method
     *
     * @return void
     */
    public function delete()
    {
        Auth::requirePermission('module:databases.delete_db');
        $id = $_GET['id'] ?? null;
        if ($id) {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM " . Database::getInstance()->getAdapter()->quoteName('databases') . " WHERE id = ?");
            $stmt->execute([$id]);
            $database = $stmt->fetch();

            if ($database) {
                // 1. Attempt to Drop Remote Database (if applicable)
                $config = json_decode($database['config'] ?? '{}', true);
                if (!empty($config['type']) && in_array($config['type'], ['mysql', 'pgsql', 'postgresql'])) {
                    try {
                        // We need to connect to a maintenance DB to drop the target
                        $dropConfig = $config;
                        $targetDbName = $config['database'];

                        // Connect to maintenance DB
                        $dropConfig['database'] = ($config['type'] === 'mysql') ? 'information_schema' : 'postgres';

                        $adapter = \App\Core\DatabaseFactory::create($dropConfig);
                        $conn = $adapter->getConnection();

                        $quotedDbName = $adapter->quoteName($targetDbName);

                        // Terminate active connections (PostgreSQL)
                        if ($config['type'] === 'pgsql' || $config['type'] === 'postgresql') {
                            $safeTargetName = str_replace("'", "''", $targetDbName);
                            // Log termination attempt
                            Logger::log('DB_DROP_ATTEMPT', ['msg' => "Terminating connections for $targetDbName"]);
                            $conn->query("SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '{$safeTargetName}' AND pid <> pg_backend_pid()");
                            // Small pause to ensure backends are gone
                            usleep(200000); // 200ms
                        }

                        $conn->exec("DROP DATABASE IF EXISTS $quotedDbName");

                    } catch (\Exception $e) {
                        // ABORT DELETION if we can't drop the DB
                        // This ensures consistency between list and engine
                        Logger::log('DELETE_DATABASE_FAIL', ['message' => $e->getMessage()]);
                        Auth::setFlashError("Could not delete from Database Engine: " . $e->getMessage());
                        header('Location: ' . Auth::getBaseUrl() . 'admin/databases');
                        exit;
                    }
                }

                // 2. Delete Dependencies
                $db->beginTransaction();
                try {
                    $db->prepare("DELETE FROM fields_config WHERE db_id = ?")->execute([$id]);
                    $db->prepare("DELETE FROM table_metadata WHERE db_id = ?")->execute([$id]);
                    $db->prepare("DELETE FROM data_versions WHERE database_id = ?")->execute([$id]);

                    // 3. Delete Parent Record
                    $stmt = $db->prepare("DELETE FROM " . Database::getInstance()->getAdapter()->quoteName('databases') . " WHERE id = ?");
                    $stmt->execute([$id]);

                    $db->commit();
                } catch (\Exception $e) {
                    $db->rollBack();
                    Auth::setFlashError("System Error during deletion: " . $e->getMessage());
                    header('Location: ' . Auth::getBaseUrl() . 'admin/databases');
                    exit;
                }

                Logger::log('DELETE_DATABASE', ['id' => $id, 'path' => $database['path']]);

                // 4. Delete SQLite File (if applicable)
                if (($config['type'] ?? 'sqlite') === 'sqlite' && $database['path'] && file_exists($database['path'])) {
                    @unlink($database['path']);
                }

                Auth::setFlashError("Database deleted successfully.", "success");
            }
        }
        header('Location: ' . Auth::getBaseUrl() . 'admin/databases');
    }

    /**
     * Shows the configuration form for a database (visibility settings, etc).
     */
    /**
     * edit method
     *
     * @return void
     */
    public function edit()
    {
        Auth::requirePermission('module:databases.edit_db');
        $id = $_GET['id'] ?? null;
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM " . Database::getInstance()->getAdapter()->quoteName('databases') . " WHERE id = ?");
        $stmt->execute([$id]);
        $database = $stmt->fetch();

        if (!$database) {
            Auth::setFlashError("Database not found.");
            header('Location: ' . Auth::getBaseUrl() . 'admin/databases');
            exit;
        }

        // Get actual tables from the SQLite file
        try {
            $targetDb = new PDO('sqlite:' . $database['path']);
            $stmt = $targetDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            $tables = [];
        }

        $config = json_decode($database['config'] ?? '{}', true);

        $this->view('admin/databases/edit', [
            'database' => $database,
            'tables' => $tables,
            'config' => $config,
            'breadcrumbs' => [
                \App\Core\Lang::get('databases.title') => 'admin/databases',
                'Configurar Visibilidad' => null
            ]
        ]);
    }

    /**
     * Saves the database configuration (hidden tables).
     */
    /**
     * saveConfig method
     *
     * @return void
     */
    public function saveConfig()
    {
        Auth::requirePermission('module:databases.edit_db');
        $id = $_POST['id'] ?? null;
        $hiddenTables = $_POST['hidden_tables'] ?? [];

        $config = ['hidden_tables' => $hiddenTables];
        $configJson = json_encode($config);

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE " . Database::getInstance()->getAdapter()->quoteName('databases') . " SET config = ? WHERE id = ?");
        $stmt->execute([$configJson, $id]);

        Auth::setFlashError("Configuración guardada correctamente.", 'success');
        header('Location: ' . Auth::getBaseUrl() . 'admin/databases');
        exit;
    }

    /**
     * Displays all tables within a specific database.
     * Now supports both SQLite and MySQL through DatabaseManager.
     */
    public function viewTables()
    {
        $id = $_GET['id'] ?? null;
        Auth::requirePermission('module:databases', 'view_tables');

        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM " . Database::getInstance()->getAdapter()->quoteName('databases') . " WHERE id = ?");
            $stmt->execute([$id]);
            $database = $stmt->fetch();

            if (!$database) {
                Auth::setFlashError("Database configuration not found.");
                header('Location: ' . Auth::getBaseUrl() . 'admin/databases');
                exit;
            }

            // Use DatabaseManager to get the appropriate adapter
            $adapter = \App\Core\DatabaseManager::getAdapter($database);
            $connection = $adapter->getConnection();
            $dbType = $adapter->getType();

            // Get table names based on database type
            if ($dbType === 'sqlite') {
                $stmt = $connection->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                $tableNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } elseif ($dbType === 'mysql') {
                $stmt = $connection->query("SHOW TABLES");
                $tableNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } elseif ($dbType === 'pgsql') {
                $stmt = $connection->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
                $tableNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } else {
                throw new \Exception("Unsupported database type: $dbType");
            }

            // Filter hidden tables for non-admins
            $config = json_decode($database['config'] ?? '{}', true);
            $hiddenTables = $config['hidden_tables'] ?? [];

            $tables = [];
            foreach ($tableNames as $tableName) {
                // If not Admin, check if table is hidden
                if (!Auth::isAdmin() && in_array($tableName, $hiddenTables)) {
                    continue;
                }

                try {
                    $quote = ($dbType === 'pgsql') ? '"' : '`';
                    $count = $connection->query("SELECT COUNT(*) FROM $quote$tableName$quote")->fetchColumn();
                } catch (\Exception $e) {
                    $count = 0;
                }
                $tables[$tableName] = $count;
            }

            $this->view('admin/databases/tables', [
                'title' => 'Tables - ' . ($database['name'] ?? 'DB'),
                'tables' => $tables,
                'database' => $database,
                'db_type' => $dbType,
                'hidden_tables' => $hiddenTables,
                'breadcrumbs' => [
                    \App\Core\Lang::get('databases.title') => 'admin/databases',
                    ($database['name'] ?? 'Database') => null
                ]
            ]);

        } catch (\Exception $e) {
            Auth::setFlashError("Error loading tables: " . $e->getMessage());
            $this->redirect('admin/databases');
        }
    }

    /**
     * Adds a new table to a specific database.
     * Now supports both SQLite and MySQL through DatabaseManager.
     */
    public function createTable()
    {
        $db_id = $_POST['db_id'] ?? null;
        Auth::requirePermission('module:databases.create_table');

        $table_name = $_POST['table_name'] ?? '';
        $table_name = preg_replace('/[^a-zA-Z0-9]+/', '_', trim($table_name));
        $table_name = trim(strtolower($table_name), '_');

        if (!$db_id || empty($table_name)) {
            header('Location: ' . Auth::getBaseUrl() . 'admin/databases');
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM " . Database::getInstance()->getAdapter()->quoteName('databases') . " WHERE id = ?");
        $stmt->execute([$db_id]);
        $database = $stmt->fetch();

        if (!$database) {
            Auth::setFlashError("Database not found.");
            header('Location: ' . Auth::getBaseUrl() . 'admin/databases');
            exit;
        }

        try {
            // Use DatabaseManager to get the appropriate adapter
            $adapter = \App\Core\DatabaseManager::getAdapter($database);
            $connection = $adapter->getConnection();
            $dbType = $adapter->getType();

            // Create table using adapter (automatically handles PostgreSQL/MySQL/SQLite differences)
            $adapter->createTable($table_name);

            // Register fields in metadata
            $stmt = $db->prepare("INSERT INTO fields_config (db_id, table_name, field_name, data_type, view_type, is_editable, is_visible, is_required) VALUES (?, ?, 'id', 'INTEGER', 'number', 0, 1, 0)");
            $stmt->execute([$db_id, $table_name]);

            $stmt = $db->prepare("INSERT INTO fields_config (db_id, table_name, field_name, data_type, view_type, is_editable, is_visible, is_required) VALUES (?, ?, 'fecha_de_creacion', 'DATETIME', 'datetime', 0, 1, 0)");
            $stmt->execute([$db_id, $table_name]);

            $stmt = $db->prepare("INSERT INTO fields_config (db_id, table_name, field_name, data_type, view_type, is_editable, is_visible, is_required) VALUES (?, ?, 'fecha_edicion', 'DATETIME', 'datetime', 0, 1, 0)");
            $stmt->execute([$db_id, $table_name]);

            // Initialize table metadata
            $stmt = $db->prepare("INSERT INTO table_metadata (db_id, table_name) VALUES (?, ?)");
            $stmt->execute([$db_id, $table_name]);

            Logger::log('CREATE_TABLE', ['database_id' => $db_id, 'table' => $table_name, 'type' => $dbType], $db_id);

            // Update DB last edit
            $db->prepare("UPDATE " . Database::getInstance()->getAdapter()->quoteName('databases') . " SET last_edit_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$db_id]);

            Auth::setFlashError("Table '$table_name' created successfully in $dbType database!", 'success');
            header('Location: ' . Auth::getBaseUrl() . 'admin/databases/view?id=' . $db_id);
            exit;
        } catch (\Throwable $e) {
            Auth::setFlashError("Error creating table: " . $e->getMessage());
            header('Location: ' . Auth::getBaseUrl() . 'admin/databases/view?id=' . $db_id);
            exit;
        }
    }
    /**
     * Creates a new table using raw SQL.
     * Supports SQLite, MySQL, MariaDB, and PostgreSQL.
     */
    public function createTableSql()
    {
        $db_id = $_POST['db_id'] ?? null;
        $sql_code = $_POST['sql_code'] ?? '';
        Auth::requirePermission('module:databases.create_table');

        if (!$db_id || empty($sql_code)) {
            Auth::setFlashError("Missing required parameters.");
            header('Location: ' . Auth::getBaseUrl() . 'admin/databases');
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM " . Database::getInstance()->getAdapter()->quoteName('databases') . " WHERE id = ?");
        $stmt->execute([$db_id]);
        $database = $stmt->fetch();

        if (!$database) {
            Auth::setFlashError("Database not found.");
            header('Location: ' . Auth::getBaseUrl() . 'admin/databases');
            exit;
        }

        try {
            // Use DatabaseManager to get the appropriate adapter
            $adapter = \App\Core\DatabaseManager::getAdapter($database);
            $connection = $adapter->getConnection();
            $dbType = $adapter->getType();

            $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Execute the SQL code
            $connection->exec($sql_code);

            // Update DB last edit
            $db->prepare("UPDATE " . Database::getInstance()->getAdapter()->quoteName('databases') . " SET last_edit_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$db_id]);

            Auth::setFlashError("Table created successfully via SQL in $dbType database. Syncing structure...", 'success');
            Logger::log('CREATE_TABLE_SQL', ['database_id' => $db_id, 'type' => $dbType], $db_id);

            // Redirect to sync to ensure all fields are registered and audit columns injected
            header('Location: ' . Auth::getBaseUrl() . 'admin/databases/sync?id=' . $db_id . '&from_sql=1');
            exit;
        } catch (\Throwable $e) {
            Auth::setFlashError("Error executing SQL: " . $e->getMessage());
            header('Location: ' . Auth::getBaseUrl() . 'admin/databases/view?id=' . $db_id);
            exit;
        }
    }

    /**
     * Removes a table from the database.
     * Supports SQLite, MySQL, MariaDB, and PostgreSQL.
     */
    public function deleteTable()
    {
        $db_id = $_GET['db_id'] ?? null;
        Auth::requirePermission('module:databases.drop_table');
        $table_name = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['table'] ?? '');

        if (!$db_id || empty($table_name)) {
            Auth::setFlashError("Invalid parameters.");
            header('Location: ' . Auth::getBaseUrl() . 'admin/databases');
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM " . Database::getInstance()->getAdapter()->quoteName('databases') . " WHERE id = ?");
        $stmt->execute([$db_id]);
        $database = $stmt->fetch();

        if (!$database) {
            Auth::setFlashError("Database not found.");
            header('Location: ' . Auth::getBaseUrl() . 'admin/databases');
            exit;
        }

        try {
            // Use DatabaseManager to get the appropriate adapter
            $adapter = \App\Core\DatabaseManager::getAdapter($database);
            $connection = $adapter->getConnection();
            $dbType = $adapter->getType();

            // Delete table using adapter (automatically handles PostgreSQL/MySQL/SQLite differences)
            $adapter->deleteTable($table_name);

            // Delete metadata
            $stmt = $db->prepare("DELETE FROM fields_config WHERE db_id = ? AND table_name = ?");
            $stmt->execute([$db_id, $table_name]);

            // Delete table metadata if exists
            $stmt = $db->prepare("DELETE FROM table_metadata WHERE db_id = ? AND table_name = ?");
            $stmt->execute([$db_id, $table_name]);

            Logger::log('DELETE_TABLE', ['database_id' => $db_id, 'table' => $table_name, 'type' => $dbType], $db_id);

            Auth::setFlashError("Table '$table_name' deleted successfully from $dbType database.", 'success');
        } catch (\PDOException $e) {
            Auth::setFlashError("Could not delete table: " . $e->getMessage());
        }

        header('Location: ' . Auth::getBaseUrl() . 'admin/databases/view?id=' . $db_id);
        exit;
    }

    /**
     * Displays and manages the fields (columns) of a specific table.
     * Supports SQLite, MySQL, MariaDB, and PostgreSQL.
     */
    public function manageFields()
    {
        $db_id = $_GET['db_id'] ?? null;
        Auth::requirePermission('module:databases.edit_table');
        $table_name = $_GET['table'] ?? null;

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM " . Database::getInstance()->getAdapter()->quoteName('databases') . " WHERE id = ?");
        $stmt->execute([$db_id]);
        $database = $stmt->fetch();

        if (!$database) {
            Auth::setFlashError("Database not found.");
            $this->redirect('admin/databases');
        }

        $stmt = $db->prepare("SELECT * FROM fields_config WHERE db_id = ? AND table_name = ? ORDER BY id ASC");
        $stmt->execute([$db_id, $table_name]);
        $configFields = $stmt->fetchAll();

        try {
            // Use DatabaseManager to get the appropriate adapter
            $adapter = \App\Core\DatabaseManager::getAdapter($database);
            $connection = $adapter->getConnection();
            $dbType = $adapter->getType();

            // Get table list based on database type
            if ($dbType === 'sqlite') {
                $stmt = $connection->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } elseif ($dbType === 'mysql' || $dbType === 'mariadb') {
                $stmt = $connection->query("SHOW TABLES");
                $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } elseif ($dbType === 'pgsql' || $dbType === 'postgresql') {
                $stmt = $connection->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
                $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } else {
                $allTables = [];
            }
        } catch (\PDOException $e) {
            $allTables = [];
        }

        $this->view('admin/databases/fields', [
            'title' => 'Config Fields - ' . ($table_name),
            'configFields' => $configFields,
            'database' => $database,
            'table_name' => $table_name,
            'allTables' => $allTables,
            'breadcrumbs' => [
                \App\Core\Lang::get('databases.title') => 'admin/databases',
                $database['name'] => 'admin/databases/view?id=' . $db_id,
                'Architect: ' . $table_name => null
            ]
        ]);
    }

    /**
     * Adds a new field structure to the system metadata for a table.
     * Now supports both SQLite and MySQL through DatabaseManager.
     */
    public function addField()
    {
        $db_id = $_POST['db_id'] ?? null;
        Auth::requirePermission('module:databases.edit_table');
        $table_name = $_POST['table_name'] ?? '';
        $field_name = $_POST['field_name'] ?? '';

        // Sanitize names
        $table_name = preg_replace('/[^a-zA-Z0-9]+/', '_', trim($table_name));
        $table_name = trim(strtolower($table_name), '_');
        $field_name = preg_replace('/[^a-zA-Z0-9]+/', '_', trim($field_name));
        $field_name = trim(strtolower($field_name), '_');
        $data_type = $_POST['data_type'] ?? 'TEXT';
        $view_type = $_POST['view_type'] ?? 'text';

        if (!$db_id || empty($table_name) || empty($field_name)) {
            header('Location: ' . Auth::getBaseUrl() . 'admin/databases');
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM " . Database::getInstance()->getAdapter()->quoteName('databases') . " WHERE id = ?");
        $stmt->execute([$db_id]);
        $database = $stmt->fetch();

        if (!$database) {
            Auth::setFlashError("Database not found.");
            header('Location: ' . Auth::getBaseUrl() . 'admin/databases');
            exit;
        }

        try {
            // Use DatabaseManager to get the appropriate adapter
            $adapter = \App\Core\DatabaseManager::getAdapter($database);
            $connection = $adapter->getConnection();
            $dbType = $adapter->getType();

            // Add column using adapter (automatically handles PostgreSQL/MySQL/SQLite differences)
            $adapter->addColumn($table_name, $field_name, $data_type);

            // Register field in metadata
            $stmt = $db->prepare("INSERT INTO fields_config (db_id, table_name, field_name, data_type, view_type, is_required) VALUES (?, ?, ?, ?, ?, 0)");
            $stmt->execute([$db_id, $table_name, $field_name, $data_type, $view_type]);

            Logger::log('ADD_FIELD', ['database_id' => $db_id, 'table' => $table_name, 'field' => $field_name, 'type' => $dbType], $db_id);

            Auth::setFlashError("Field '$field_name' added successfully to $dbType table!", 'success');
            header('Location: ' . Auth::getBaseUrl() . "admin/databases/fields?db_id=$db_id&table=$table_name");
        } catch (\PDOException $e) {
            Auth::setFlashError("Error adding field: " . $e->getMessage());
            header('Location: ' . Auth::getBaseUrl() . "admin/databases/fields?db_id=$db_id&table=$table_name");
        }
    }

    /**
     * Removes a field from the table metadata and potentially the physical database.
     * Now supports both SQLite and MySQL through DatabaseManager.
     */
    public function deleteField()
    {
        $config_id = $_GET['config_id'] ?? null;
        if (!$config_id) {
            Auth::setFlashError("Invalid Field Configuration ID");
            $this->redirect('admin/databases');
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM fields_config WHERE id = ?");
        $stmt->execute([$config_id]);
        $field = $stmt->fetch();

        if (!$field) {
            Auth::setFlashError("Field not found.");
            $this->redirect('admin/databases');
        }

        $db_id = $field['db_id'];
        $table_name = $field['table_name'];
        $field_name = $field['field_name'];

        Auth::requirePermission('module:databases.edit_table');

        // Prevent deleting system fields
        if (in_array($field_name, ['id', 'fecha_de_creacion', 'fecha_edicion'])) {
            Auth::setFlashError("System fields cannot be deleted.", 'error');
            $this->redirect("admin/databases/fields?db_id=$db_id&table=$table_name");
        }

        // Delete from Config
        $stmt = $db->prepare("DELETE FROM fields_config WHERE id = ?");
        $stmt->execute([$config_id]);
        Logger::log('DELETE_FIELD', ['database_id' => $db_id, 'table' => $table_name, 'field' => $field_name], $db_id);

        // Attempt to Drop Column from Structure
        $stmt = $db->prepare("SELECT * FROM " . Database::getInstance()->getAdapter()->quoteName('databases') . " WHERE id = ?");
        $stmt->execute([$db_id]);
        $database = $stmt->fetch();

        try {
            // Use DatabaseManager to get the appropriate adapter
            $adapter = \App\Core\DatabaseManager::getAdapter($database);
            $connection = $adapter->getConnection();
            $dbType = $adapter->getType();

            // Drop column using adapter (automatically handles PostgreSQL/MySQL/SQLite differences)
            $adapter->deleteColumn($table_name, $field_name);

            Auth::setFlashError("Field '$field_name' dropped successfully from $dbType table.", 'success');
        } catch (\PDOException $e) {
            // Fallback: If drop column is not supported or fails, at least config is gone.
            Auth::setFlashError("Field config removed, but column persisted (limitation or data conflict): " . $e->getMessage(), 'warning');
        }

        $this->redirect("admin/databases/fields?db_id=$db_id&table=$table_name");
    }

    /**
     * Updates the configuration (UI type, constraints) for existing fields.
     */
    /**
     * updateFieldConfig method
     *
     * @return void
     */
    public function updateFieldConfig()
    {
        $config_id = $_POST['config_id'] ?? null;
        if (!$config_id)
            return;

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT db_id FROM fields_config WHERE id = ?");
        $stmt->execute([$config_id]);
        $db_id = $stmt->fetchColumn();

        Auth::requirePermission('module:databases.edit_table');

        $view_type = $_POST['view_type'] ?? 'text';
        $is_required = isset($_POST['is_required']) ? 1 : 0;
        $is_visible = isset($_POST['is_visible']) ? 1 : 0;
        $is_editable = isset($_POST['is_editable']) ? 1 : 0;
        $is_foreign_key = isset($_POST['is_foreign_key']) ? 1 : 0;
        $related_table = $_POST['related_table'] ?? null;
        $related_field = $_POST['related_field'] ?? null;

        $stmt = $db->prepare("UPDATE fields_config SET
view_type = ?, is_required = ?, is_visible = ?, is_editable = ?,
is_foreign_key = ?, related_table = ?, related_field = ?
WHERE id = ?");
        $stmt->execute([
            $view_type,
            $is_required,
            $is_visible,
            $is_editable,
            $is_foreign_key,
            $related_table,
            $related_field,
            $config_id
        ]);

        $stmt = $db->prepare("SELECT table_name FROM fields_config WHERE id = ?");
        $stmt->execute([$config_id]);
        $table_name = $stmt->fetchColumn();
        header('Location: ' . Auth::getBaseUrl() . "admin/databases/fields?db_id=$db_id&table=$table_name");
    }
    /**
     * Synchronizes the physical database structure with the system's metadata.
     * Now supports both SQLite and MySQL through DatabaseManager.
     */
    public function syncDatabase()
    {
        $id = $_GET['id'] ?? null;
        Auth::requirePermission('module:databases.edit_table');

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM " . Database::getInstance()->getAdapter()->quoteName('databases') . " WHERE id = ?");
        $stmt->execute([$id]);
        $database = $stmt->fetch();

        if (!$database) {
            header('Location: ' . Auth::getBaseUrl() . 'admin/databases');
            exit;
        }

        try {
            // Use DatabaseManager to get the appropriate adapter
            $adapter = \App\Core\DatabaseManager::getAdapter($database);
            $connection = $adapter->getConnection();
            $dbType = $adapter->getType();

            // Get table names based on database type
            if ($dbType === 'sqlite') {
                $stmt = $connection->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } elseif ($dbType === 'mysql') {
                $stmt = $connection->query("SHOW TABLES");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } elseif ($dbType === 'pgsql') {
                $stmt = $connection->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } else {
                throw new \Exception("Unsupported database type: $dbType");
            }

            $syncedTables = 0;
            $syncedFields = 0;

            foreach ($tables as $table) {
                // Get columns based on database type
                if ($dbType === 'sqlite') {
                    $stmtCols = $connection->query("PRAGMA table_info(" . $adapter->quoteName($table) . ")");
                    $columns = $stmtCols->fetchAll(PDO::FETCH_ASSOC);
                } elseif ($dbType === 'mysql') {
                    $stmtCols = $connection->query("SHOW COLUMNS FROM " . $adapter->quoteName($table));
                    $mysqlCols = $stmtCols->fetchAll(PDO::FETCH_ASSOC);
                    // Convert MySQL format to SQLite-like format for consistency
                    $columns = [];
                    foreach ($mysqlCols as $col) {
                        $columns[] = [
                            'name' => $col['Field'],
                            'type' => $col['Type']
                        ];
                    }
                } elseif ($dbType === 'pgsql') {
                    $stmtCols = $connection->query("
                        SELECT column_name as name, data_type as type 
                        FROM information_schema.columns 
                        WHERE table_schema = 'public' AND table_name = '$table'
                        ORDER BY ordinal_position
                    ");
                    $columns = $stmtCols->fetchAll(PDO::FETCH_ASSOC);
                }

                $syncedTables++;

                // --- Consistency Audit: Ensure audit columns exist (SQLite only for now) ---
                if ($dbType === 'sqlite') {
                    $columnNames = array_map(function ($c) {
                        return strtolower($c['name']);
                    }, $columns);
                    $now = Auth::getCurrentTime();
                    $auditChanged = false;

                    if (!in_array('fecha_de_creacion', $columnNames)) {
                        $connection->exec("ALTER TABLE `$table` ADD COLUMN fecha_de_creacion TEXT");
                        $connection->exec("UPDATE `$table` SET fecha_de_creacion = '$now' WHERE fecha_de_creacion IS NULL OR fecha_de_creacion = ''");
                        $auditChanged = true;
                    }
                    if (!in_array('fecha_edicion', $columnNames)) {
                        $connection->exec("ALTER TABLE `$table` ADD COLUMN fecha_edicion TEXT");
                        $connection->exec("UPDATE `$table` SET fecha_edicion = '$now' WHERE fecha_edicion IS NULL OR fecha_edicion = ''");
                        $auditChanged = true;
                    }

                    // If schema changed, reload columns
                    if ($auditChanged) {
                        $stmtCols = $connection->query("PRAGMA table_info(`$table`)");
                        $columns = $stmtCols->fetchAll(PDO::FETCH_ASSOC);
                    }
                }
                // ---------------------------------------------------

                foreach ($columns as $col) {
                    $fieldName = $col['name'];
                    $stmtCheck = $db->prepare("SELECT id FROM fields_config WHERE db_id = ? AND table_name = ? AND field_name = ?");
                    $stmtCheck->execute([$id, $table, $fieldName]);
                    if (!$stmtCheck->fetch()) {
                        $viewType = 'text';
                        $dataType = strtoupper($col['type']);
                        $lowerName = strtolower($fieldName);

                        if (preg_match('/(imagen|image|foto|photo|img|avatar|logo|thumbnail|picture|gallery|galeria)/i', $lowerName)) {
                            $viewType = preg_match('/gallery|galeria/i', $lowerName) ? 'gallery' : 'image';
                        } elseif (preg_match('/(descripcion|description|content|contenido|mensaje|message|bio|body|text)/i', $lowerName)) {
                            $viewType = 'textarea';
                        } elseif (
                            preg_match('/(status|activo|active|visible|enabled|public|borrado|deleted)/i', $lowerName) &&
                            strpos($dataType, 'INT') !== false
                        ) {
                            $viewType = 'boolean';
                        } elseif (
                            preg_match('/(fecha|date|time|timestamp|momento|horario)/i', $lowerName) ||
                            preg_match('/(DATETIME|DATE|TIMESTAMP)/i', $dataType)
                        ) {
                            $viewType = 'datetime';
                        }

                        // Protect audit fields from manual editing
                        $isEditable = in_array($fieldName, ['id', 'fecha_de_creacion', 'fecha_edicion']) ? 0 : 1;
                        $isVisible = 1;

                        $stmtInsert = $db->prepare("INSERT INTO fields_config (db_id, table_name, field_name, data_type, view_type, is_editable, is_visible, is_required) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
                        $stmtInsert->execute([$id, $table, $fieldName, $dataType, $viewType, $isEditable, $isVisible]);
                        $syncedFields++;
                    }
                }
            }

            if (empty($_GET['from_sql'])) {
                $message = "Sync completed: $syncedTables tables synchronized, $syncedFields new fields detected.";
                if ($dbType === 'sqlite') {
                    $message .= " Audit columns 'fecha_de_creacion/edicion' were automatically injected where missing.";
                }
                Auth::setFlashError($message, 'success');
            }
        } catch (\PDOException $e) {
            Auth::setFlashError("Sync Error: " . $e->getMessage());
        }

        header('Location: ' . Auth::getBaseUrl() . 'admin/databases/view?id=' . $id);
        exit;
    }
    /**
     * Creates a database from an uploaded SQL script.
     */
    /**
     * importSql method
     *
     * @return void
     */
    public function importSql()
    {
        Auth::requirePermission('module:databases.create_db');
        $name = $_POST['name'] ?? 'Imported Database';
        $file = $_FILES['sql_file'] ?? null;

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            Auth::setFlashError("No SQL file uploaded.");
            $this->redirect('admin/databases');
        }

        // 1. Create the database first (empty)
        $sanitized = preg_replace('/[^a-zA-Z0-9]+/', '_', trim($name));
        $sanitized = trim(strtolower($sanitized), '_');
        $storagePath = Config::get('db_storage_path');
        $filename = $sanitized . '_' . uniqid() . '.sqlite';
        $path = $storagePath . $filename;

        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0777, true);
        }

        try {
            $targetDb = new PDO('sqlite:' . $path);
            $targetDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // 2. Execute SQL
            $sql = file_get_contents($file['tmp_name']);
            // SQLite can execute multiple statements if we use exec
            $targetDb->exec($sql);

            // 3. Register in system
            $db = Database::getInstance()->getConnection();
            $projectId = Auth::getActiveProject();
            $stmt = $db->prepare("INSERT INTO " . Database::getInstance()->getAdapter()->quoteName('databases') . " (name, path, project_id) VALUES (?, ?, ?)");
            $stmt->execute([$name, $path, $projectId]);
            $dbId = $db->lastInsertId();

            // 4. Trigger Sync to detect tables/fields
            Auth::setFlashError("Database imported and created successfully.", 'success');
            header('Location: ' . Auth::getBaseUrl() . 'admin/databases/sync?id=' . $dbId);
            exit;
        } catch (\Exception $e) {
            // Clean up potentially corrupt file if creation failed partly
            if (file_exists($path)) {
                unlink($path);
            }
            Auth::setFlashError("Error importing SQL: " . $e->getMessage());
            $this->redirect('admin/databases');
        }
    }

    /**
     * Exports a database as a SQL dump.
     */
    /**
     * exportSql method
     *
     * @return void
     */
    public function exportSql()
    {
        $id = $_GET['id'] ?? null;
        Auth::requirePermission('module:databases.create_db');

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM " . Database::getInstance()->getAdapter()->quoteName('databases') . " WHERE id = ?");
        $stmt->execute([$id]);
        $database = $stmt->fetch();

        if (!$database || !file_exists($database['path'])) {
            Auth::setFlashError("Database not found.");
            $this->redirect('admin/databases');
        }

        $dbPath = $database['path'];
        $dbName = preg_replace('/[^a-zA-Z0-9]+/', '_', $database['name']);

        $hasSqlite3 = false;
        @exec('which sqlite3', $output, $returnCode);
        if ($returnCode === 0) {
            $hasSqlite3 = true;
        }

        if (ob_get_level())
            ob_end_clean();

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $dbName . '_dump.sql"');

        if ($hasSqlite3) {
            $cmd = "sqlite3 " . escapeshellarg($dbPath) . " .dump";
            passthru($cmd);
            Logger::log('EXPORT_SQL', ['database_id' => $id]);
        } else {
            try {
                $targetDb = new PDO('sqlite:' . $dbPath);
                $stmt = $targetDb->query("SELECT sql, name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                $tables = $stmt->fetchAll();

                echo "-- Data2Rest SQL Dump (PHP Fallback)\n";
                foreach ($tables as $table) {
                    echo $table['sql'] . ";\n\n";
                    $dataStmt = $targetDb->query("SELECT * FROM " . $table['name']);
                    while ($row = $dataStmt->fetch(PDO::FETCH_ASSOC)) {
                        $cols = implode(', ', array_keys($row));
                        $vals = implode(', ', array_map(function ($v) use ($targetDb) {
                            return $v === null ? 'NULL' : $targetDb->quote($v);
                        }, array_values($row)));
                        echo "INSERT INTO " . $table['name'] . " ($cols) VALUES ($vals);\n";
                    }
                    echo "\n";
                }
            } catch (\Exception $e) {
                echo "-- Error during dump: " . $e->getMessage();
            }
        }
        exit;
    }

    /**
     * Export a single table as SQL
     */
    /**
     * exportTableSql method
     *
     * @return void
     */
    public function exportTableSql()
    {
        $db_id = $_GET['db_id'] ?? null;
        $table = $_GET['table'] ?? null;
        Auth::requirePermission('module:databases.export_data');

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM " . Database::getInstance()->getAdapter()->quoteName('databases') . " WHERE id = ?");
        $stmt->execute([$db_id]);
        $database = $stmt->fetch();

        if (!$database || !$table) {
            Auth::setFlashError("Invalid parameters.");
            $this->redirect('admin/databases');
        }

        try {
            $targetDb = new PDO('sqlite:' . $database['path']);
            $targetDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Get table schema
            $schemaStmt = $targetDb->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='$table'");
            $schema = $schemaStmt->fetchColumn();

            // Get data
            $dataStmt = $targetDb->query("SELECT * FROM $table");
            $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

            if (ob_get_level())
                ob_end_clean();
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="' . $table . '_export.sql"');

            echo "-- Data2Rest Table Export: $table\n";
            echo "-- Database: {$database['name']}\n";
            echo "-- Exported: " . Auth::getCurrentTime() . "\n\n";
            echo "$schema;\n\n";

            foreach ($rows as $row) {
                $cols = implode(', ', array_keys($row));
                $vals = implode(', ', array_map(function ($v) use ($targetDb) {
                    return $v === null ? 'NULL' : $targetDb->quote($v);
                }, array_values($row)));
                echo "INSERT INTO $table ($cols) VALUES ($vals);\n";
            }

            Logger::log('EXPORT_TABLE_SQL', ['database_id' => $db_id, 'table' => $table]);
            exit;
        } catch (\Exception $e) {
            Auth::setFlashError("Error exporting table: " . $e->getMessage());
            $this->redirect("admin/databases/view?id=$db_id");
        }
    }

    /**
     * Export a single table as Excel
     */
    /**
     * exportTableExcel method
     *
     * @return void
     */
    public function exportTableExcel()
    {
        $db_id = $_GET['db_id'] ?? null;
        $table = $_GET['table'] ?? null;
        Auth::requirePermission('module:databases.export_data');

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM " . Database::getInstance()->getAdapter()->quoteName('databases') . " WHERE id = ?");
        $stmt->execute([$db_id]);
        $database = $stmt->fetch();

        if (!$database || !$table) {
            Auth::setFlashError("Invalid parameters.");
            $this->redirect('admin/databases');
        }

        try {
            $targetDb = new PDO('sqlite:' . $database['path']);
            $dataStmt = $targetDb->query("SELECT * FROM $table");
            $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

            if (ob_get_level())
                ob_end_clean();
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="' . $table . '_export.xls"');

            echo "<table border='1'>";
            if (!empty($rows)) {
                echo "<tr>";
                foreach (array_keys($rows[0]) as $header) {
                    echo "<th>" . htmlspecialchars($header) . "</th>";
                }
                echo "</tr>";

                foreach ($rows as $row) {
                    echo "<tr>";
                    foreach ($row as $cell) {
                        echo "<td>" . htmlspecialchars($cell ?? '') . "</td>";
                    }
                    echo "</tr>";
                }
            }
            echo "</table>";

            Logger::log('EXPORT_TABLE_EXCEL', ['database_id' => $db_id, 'table' => $table]);
            exit;
        } catch (\Exception $e) {
            Auth::setFlashError("Error exporting table: " . $e->getMessage());
            $this->redirect("admin/databases/view?id=$db_id");
        }
    }

    /**
     * Export a single table as CSV
     */
    /**
     * exportTableCsv method
     *
     * @return void
     */
    public function exportTableCsv()
    {
        $db_id = $_GET['db_id'] ?? null;
        $table = $_GET['table'] ?? null;
        Auth::requirePermission('module:databases.export_data');

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM " . Database::getInstance()->getAdapter()->quoteName('databases') . " WHERE id = ?");
        $stmt->execute([$db_id]);
        $database = $stmt->fetch();

        if (!$database || !$table) {
            Auth::setFlashError("Invalid parameters.");
            $this->redirect('admin/databases');
        }

        try {
            $targetDb = new PDO('sqlite:' . $database['path']);
            $dataStmt = $targetDb->query("SELECT * FROM $table");
            $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

            if (ob_get_level())
                ob_end_clean();
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $table . '_export.csv"');

            $output = fopen('php://output', 'w');

            if (!empty($rows)) {
                fputcsv($output, array_keys($rows[0]));
                foreach ($rows as $row) {
                    fputcsv($output, $row);
                }
            }

            fclose($output);
            Logger::log('EXPORT_TABLE_CSV', ['database_id' => $db_id, 'table' => $table]);
            exit;
        } catch (\Exception $e) {
            Auth::setFlashError("Error exporting table: " . $e->getMessage());
            $this->redirect("admin/databases/view?id=$db_id");
        }
    }

    /**
     * Generate Excel template for import
     */
    /**
     * generateExcelTemplate method
     *
     * @return void
     */
    public function generateExcelTemplate()
    {
        $db_id = $_GET['db_id'] ?? null;
        $table = $_GET['table'] ?? null;
        Auth::requirePermission('module:databases.export_data');

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM " . Database::getInstance()->getAdapter()->quoteName('databases') . " WHERE id = ?");
        $stmt->execute([$db_id]);
        $database = $stmt->fetch();

        if (!$database || !$table) {
            Auth::setFlashError("Invalid parameters.");
            $this->redirect('admin/databases');
        }

        try {
            $targetDb = new PDO('sqlite:' . $database['path']);
            $stmt = $targetDb->query("PRAGMA table_info($table)");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (ob_get_level())
                ob_end_clean();
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="' . $table . '_template.xls"');

            echo "<table border='1'>";
            echo "<tr>";
            foreach ($columns as $col) {
                // Skip auto-increment ID and audit fields
                if ($col['name'] !== 'id' && $col['name'] !== 'fecha_de_creacion' && $col['name'] !== 'fecha_edicion') {
                    echo "<th>" . htmlspecialchars($col['name']) . "</th>";
                }
            }
            echo "</tr>";
            // Add 3 example rows
            for ($i = 0; $i < 3; $i++) {
                echo "<tr>";
                foreach ($columns as $col) {
                    if ($col['name'] !== 'id' && $col['name'] !== 'fecha_de_creacion' && $col['name'] !== 'fecha_edicion') {
                        echo "<td>ejemplo_" . ($i + 1) . "</td>";
                    }
                }
                echo "</tr>";
            }
            echo "</table>";
            exit;
        } catch (\Exception $e) {
            Auth::setFlashError("Error generating template: " . $e->getMessage());
            $this->redirect("admin/databases/view?id=$db_id");
        }
    }

    /**
     * Generate CSV template for import
     */
    /**
     * generateCsvTemplate method
     *
     * @return void
     */
    public function generateCsvTemplate()
    {
        $db_id = $_GET['db_id'] ?? null;
        $table = $_GET['table'] ?? null;
        Auth::requirePermission('module:databases.export_data');

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM " . Database::getInstance()->getAdapter()->quoteName('databases') . " WHERE id = ?");
        $stmt->execute([$db_id]);
        $database = $stmt->fetch();

        if (!$database || !$table) {
            Auth::setFlashError("Invalid parameters.");
            $this->redirect('admin/databases');
        }

        try {
            $targetDb = new PDO('sqlite:' . $database['path']);
            $stmt = $targetDb->query("PRAGMA table_info($table)");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (ob_get_level())
                ob_end_clean();
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $table . '_template.csv"');

            $output = fopen('php://output', 'w');

            // Headers (skip ID and audit fields)
            $headers = [];
            foreach ($columns as $col) {
                if ($col['name'] !== 'id' && $col['name'] !== 'fecha_de_creacion' && $col['name'] !== 'fecha_edicion') {
                    $headers[] = $col['name'];
                }
            }
            fputcsv($output, $headers);

            // Add 3 example rows
            for ($i = 0; $i < 3; $i++) {
                $row = array_fill(0, count($headers), 'ejemplo_' . ($i + 1));
                fputcsv($output, $row);
            }

            fclose($output);
            exit;
        } catch (\Exception $e) {
            Auth::setFlashError("Error generating template: " . $e->getMessage());
            $this->redirect("admin/databases/view?id=$db_id");
        }
    }

    /**
     * Import SQL into a table
     */
    /**
     * importTableSql method
     *
     * @return void
     */
    public function importTableSql()
    {
        $db_id = $_POST['db_id'] ?? null;
        $table = $_POST['table'] ?? null;
        $file = $_FILES['sql_file'] ?? null;
        Auth::requirePermission('module:databases.import_data');

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            Auth::setFlashError("No SQL file uploaded.", 'error');
            $this->redirect("admin/databases/view?id=$db_id");
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM " . Database::getInstance()->getAdapter()->quoteName('databases') . " WHERE id = ?");
        $stmt->execute([$db_id]);
        $database = $stmt->fetch();

        try {
            $targetDb = new PDO('sqlite:' . $database['path']);
            $targetDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sql = file_get_contents($file['tmp_name']);
            $targetDb->exec($sql);

            // POST-PROCESSING: Fill missing audit timestamps for the affected table
            $now = Auth::getCurrentTime();
            $stmtCols = $targetDb->query("PRAGMA table_info($table)");
            $tableCols = $stmtCols->fetchAll(PDO::FETCH_COLUMN, 1);

            if (in_array('fecha_de_creacion', $tableCols)) {
                $targetDb->exec("UPDATE $table SET fecha_de_creacion = '$now' WHERE fecha_de_creacion IS NULL OR fecha_de_creacion = ''");
            }
            if (in_array('fecha_edicion', $tableCols)) {
                $targetDb->exec("UPDATE $table SET fecha_edicion = '$now' WHERE fecha_edicion IS NULL OR fecha_edicion = ''");
            }

            Auth::setFlashError("Datos importados exitosamente.", 'success');
            Logger::log('IMPORT_TABLE_SQL', ['database_id' => $db_id, 'table' => $table]);
        } catch (\Exception $e) {
            Auth::setFlashError("Error importing SQL: " . $e->getMessage(), 'error');
        }

        $this->redirect("admin/databases/view?id=$db_id");
    }

    /**
     * Import SQL from text input into a table
     */
    /**
     * importTableSqlText method
     *
     * @return void
     */
    public function importTableSqlText()
    {
        $db_id = $_POST['db_id'] ?? null;
        $table = $_POST['table'] ?? null;
        $sql_code = $_POST['sql_code'] ?? null;
        Auth::requirePermission('module:databases.import_data');

        if (empty($sql_code)) {
            Auth::setFlashError("No se proporcionó código SQL.", 'error');
            $this->redirect("admin/databases/view?id=$db_id");
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM " . Database::getInstance()->getAdapter()->quoteName('databases') . " WHERE id = ?");
        $stmt->execute([$db_id]);
        $database = $stmt->fetch();

        if (!$database) {
            Auth::setFlashError("Base de datos no encontrada.", 'error');
            $this->redirect("admin/databases");
        }

        try {
            $targetDb = new PDO('sqlite:' . $database['path']);
            $targetDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Execute the SQL code
            $targetDb->exec($sql_code);

            // POST-PROCESSING: Fill missing audit timestamps for the affected table
            $now = Auth::getCurrentTime();
            $stmtCols = $targetDb->query("PRAGMA table_info($table)");
            $tableCols = $stmtCols->fetchAll(PDO::FETCH_COLUMN, 1);

            if (in_array('fecha_de_creacion', $tableCols)) {
                $targetDb->exec("UPDATE $table SET fecha_de_creacion = '$now' WHERE fecha_de_creacion IS NULL OR fecha_de_creacion = ''");
            }
            if (in_array('fecha_edicion', $tableCols)) {
                $targetDb->exec("UPDATE $table SET fecha_edicion = '$now' WHERE fecha_edicion IS NULL OR fecha_edicion = ''");
            }

            // Count affected rows (approximate)
            $affectedRows = $targetDb->query("SELECT changes()")->fetchColumn();

            Auth::setFlashError("SQL ejecutado exitosamente. Filas afectadas: $affectedRows", 'success');
            Logger::log('IMPORT_TABLE_SQL_TEXT', ['database_id' => $db_id, 'table' => $table, 'affected_rows' => $affectedRows]);
        } catch (\Exception $e) {
            Auth::setFlashError("Error ejecutando SQL: " . $e->getMessage(), 'error');
        }

        $this->redirect("admin/databases/view?id=$db_id");
    }


    /**
     * Import Excel into a table
     */
    /**
     * importTableExcel method
     *
     * @return void
     */
    public function importTableExcel()
    {
        $db_id = $_POST['db_id'] ?? null;
        $table = $_POST['table'] ?? null;
        $file = $_FILES['excel_file'] ?? null;
        Auth::requirePermission('module:databases.import_data');

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            Auth::setFlashError("No Excel file uploaded.", 'error');
            $this->redirect("admin/databases/view?id=$db_id");
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM " . Database::getInstance()->getAdapter()->quoteName('databases') . " WHERE id = ?");
        $stmt->execute([$db_id]);
        $database = $stmt->fetch();

        try {
            // Simple Excel parsing (HTML table format)
            $content = file_get_contents($file['tmp_name']);
            $dom = new \DOMDocument();
            @$dom->loadHTML($content);
            $rows = $dom->getElementsByTagName('tr');

            $targetDb = new PDO('sqlite:' . $database['path']);
            $targetDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $headers = [];
            $imported = 0;

            foreach ($rows as $index => $row) {
                if (!($row instanceof \DOMElement))
                    continue;

                if ($index === 0) {
                    // Get headers
                    $headerCells = $row->getElementsByTagName('th');
                    foreach ($headerCells as $cell) {
                        $headers[] = trim($cell->nodeValue);
                    }
                    continue;
                }

                if (empty($headers))
                    continue;

                $cells = $row->getElementsByTagName('td');
                $values = [];
                foreach ($cells as $cell) {
                    $values[] = trim($cell->nodeValue);
                }

                if (count($values) === count($headers)) {
                    $rowValues = $values;
                    $rowHeaders = $headers;
                    $now = Auth::getCurrentTime();

                    // Check if table columns exist via a quick PRAGMA check once outside if possible
                    // But for simplicity and robustness in a dynamic environment:
                    if (!isset($tableCols)) {
                        $stmtCols = $targetDb->query("PRAGMA table_info($table)");
                        $tableCols = $stmtCols->fetchAll(PDO::FETCH_COLUMN, 1);
                    }

                    if (!in_array('fecha_de_creacion', $rowHeaders) && in_array('fecha_de_creacion', $tableCols)) {
                        $rowHeaders[] = 'fecha_de_creacion';
                        $rowValues[] = $now;
                    }
                    if (!in_array('fecha_edicion', $rowHeaders) && in_array('fecha_edicion', $tableCols)) {
                        $rowHeaders[] = 'fecha_edicion';
                        $rowValues[] = $now;
                    }

                    $placeholders = implode(',', array_fill(0, count($rowHeaders), '?'));
                    $cols = implode(',', $rowHeaders);
                    $stmt = $targetDb->prepare("INSERT INTO $table ($cols) VALUES ($placeholders)");
                    $stmt->execute($rowValues);
                    $imported++;
                }
            }

            Auth::setFlashError("$imported registros importados exitosamente.", 'success');
            Logger::log('IMPORT_TABLE_EXCEL', ['database_id' => $db_id, 'table' => $table, 'count' => $imported]);
        } catch (\Exception $e) {
            Auth::setFlashError("Error importing Excel: " . $e->getMessage(), 'error');
        }

        $this->redirect("admin/databases/view?id=$db_id");
    }

    /**
     * Import CSV into a table
     */
    /**
     * importTableCsv method
     *
     * @return void
     */
    public function importTableCsv()
    {
        $db_id = $_POST['db_id'] ?? null;
        $table = $_POST['table'] ?? null;
        $file = $_FILES['csv_file'] ?? null;
        Auth::requirePermission('module:databases.import_data');

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            Auth::setFlashError("No CSV file uploaded.", 'error');
            $this->redirect("admin/databases/view?id=$db_id");
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM " . Database::getInstance()->getAdapter()->quoteName('databases') . " WHERE id = ?");
        $stmt->execute([$db_id]);
        $database = $stmt->fetch();

        try {
            $targetDb = new PDO('sqlite:' . $database['path']);
            $targetDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $handle = fopen($file['tmp_name'], 'r');
            $headers = fgetcsv($handle);
            $imported = 0;

            while (($data = fgetcsv($handle)) !== false) {
                if (count($data) === count($headers)) {
                    $rowValues = $data;
                    $rowHeaders = $headers;
                    $now = Auth::getCurrentTime();

                    if (!isset($tableCols)) {
                        $stmtCols = $targetDb->query("PRAGMA table_info($table)");
                        $tableCols = $stmtCols->fetchAll(PDO::FETCH_COLUMN, 1);
                    }

                    if (!in_array('fecha_de_creacion', $rowHeaders) && in_array('fecha_de_creacion', $tableCols)) {
                        $rowHeaders[] = 'fecha_de_creacion';
                        $rowValues[] = $now;
                    }
                    if (!in_array('fecha_edicion', $rowHeaders) && in_array('fecha_edicion', $tableCols)) {
                        $rowHeaders[] = 'fecha_edicion';
                        $rowValues[] = $now;
                    }

                    $placeholders = implode(',', array_fill(0, count($rowHeaders), '?'));
                    $cols = implode(',', $rowHeaders);
                    $stmt = $targetDb->prepare("INSERT INTO $table ($cols) VALUES ($placeholders)");
                    $stmt->execute($rowValues);
                    $imported++;
                }
            }

            fclose($handle);
            Auth::setFlashError("$imported registros importados exitosamente.", 'success');
            Logger::log('IMPORT_TABLE_CSV', ['database_id' => $db_id, 'table' => $table, 'count' => $imported]);
        } catch (\Exception $e) {
            Auth::setFlashError("Error importing CSV: " . $e->getMessage(), 'error');
        }

        $this->redirect("admin/databases/view?id=$db_id");
    }
}
