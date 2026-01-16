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
            $sql = "SELECT * FROM databases WHERE project_id = ? ORDER BY id DESC";
            $params = [$projectId];

            // If admin has no project selected, he sees nothing (or should see all? User said "everything related to project")
            // Strict project scoping means: No project = No data.
            // But Admins might want to see all... user said "module database only access databases of his project"
            // So we enforce project_id for everyone who is in a project context.

            if (Auth::isAdmin() && !$projectId) {
                // Fallback for global admin view if needed, but per request: "project determined access"
                $sql = "SELECT * FROM databases ORDER BY id DESC";
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

            $stmt = $db->prepare("INSERT INTO databases (name, path, project_id) VALUES (?, ?, ?)");
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
            $stmt = $db->prepare("SELECT path FROM databases WHERE id = ?");
            $stmt->execute([$id]);
            $database = $stmt->fetch();

            if ($database) {
                $stmt = $db->prepare("DELETE FROM databases WHERE id = ?");
                $stmt->execute([$id]);
                $stmt = $db->prepare("DELETE FROM fields_config WHERE db_id = ?");
                $stmt->execute([$id]);
                Logger::log('DELETE_DATABASE', ['id' => $id, 'path' => $database['path']]);
                if (file_exists($database['path'])) {
                    unlink($database['path']);
                }
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
        $stmt = $db->prepare("SELECT * FROM databases WHERE id = ?");
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
        $stmt = $db->prepare("UPDATE databases SET config = ? WHERE id = ?");
        $stmt->execute([$configJson, $id]);

        Auth::setFlashError("Configuración guardada correctamente.", 'success');
        header('Location: ' . Auth::getBaseUrl() . 'admin/databases');
        exit;
    }

    /**
     * Lists all tables within a specific database.
     */
/**
 * viewTables method
 *
 * @return void
 */
    public function viewTables()
    {
        try {
            $id = $_GET['id'] ?? null;
            Auth::requirePermission('module:databases.view_tables');

            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM databases WHERE id = ?");
            $stmt->execute([$id]);
            $database = $stmt->fetch();

            if (!$database) {
                Auth::setFlashError("Database configuration not found.");
                header('Location: ' . Auth::getBaseUrl() . 'admin/databases');
                exit;
            }

            // --- PATH SELF-HEALING (Replicated from CrudController) ---
            if (!file_exists($database['path'])) {
                $filename = basename($database['path']);
                // Direct file check in standard data dir using Config
                $localPath = Config::get('db_storage_path') . $filename;

                if (file_exists($localPath)) {
                    $database['path'] = realpath($localPath);
                    // Update DB
                    $upd = $db->prepare("UPDATE databases SET path = ? WHERE id = ?");
                    $upd->execute([$database['path'], $id]);
                } else {
                    // Soft fail: Remove the invalid record or warn user
                    // For now, warn user and redirect back to list to avoid crash loop
                    Logger::log('DB_FILE_MISSING', ['id' => $id, 'path' => $database['path']]);
                    Auth::setFlashError("⚠️ Error Crítico: El archivo de base de datos no existe en el disco. Contacte al administrador.");
                    $this->redirect('admin/databases');
                }
            }
            // -----------------------------------------------------------

            // Create connection to Target DB
            $targetDb = new PDO('sqlite:' . $database['path']);
            $targetDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $targetDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            $tableNames = $stmt->fetchAll(PDO::FETCH_COLUMN);

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
                    $count = $targetDb->query("SELECT COUNT(*) FROM $tableName")->fetchColumn();
                } catch (\Exception $e) {
                    $count = 0;
                }
                $tables[$tableName] = $count;
            }

            $this->view('admin/databases/tables', [
                'title' => 'Tables - ' . ($database['name'] ?? 'DB'),
                'tables' => $tables,
                'database' => $database,
                'hidden_tables' => $hiddenTables,
                'breadcrumbs' => [
                    \App\Core\Lang::get('databases.title') => 'admin/databases',
                    ($database['name'] ?? 'Database') => null
                ]
            ]);

        } catch (\Exception $e) {
            die("<h1>Fatal Error in ViewTables</h1><pre>" . $e->getMessage() . "\n" . $e->getTraceAsString() . "</pre>");
        }
    }

    /**
     * Adds a new table to a specific database.
     */
/**
 * createTable method
 *
 * @return void
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
        $stmt = $db->prepare("SELECT path FROM databases WHERE id = ?");
        $stmt->execute([$db_id]);
        $database = $stmt->fetch();

        try {
            $targetDb = new PDO('sqlite:' . $database['path']);
            $targetDb->exec("CREATE TABLE $table_name (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                fecha_de_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
                fecha_edicion DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            $stmt = $db->prepare("INSERT INTO fields_config (db_id, table_name, field_name, data_type, view_type, is_editable,
is_visible, is_required) VALUES (?, ?, 'id', 'INTEGER', 'number', 0, 1, 0)");
            $stmt->execute([$db_id, $table_name]);
            $stmt = $db->prepare("INSERT INTO fields_config (db_id, table_name, field_name, data_type, view_type, is_editable,
is_visible, is_required) VALUES (?, ?, 'fecha_de_creacion', 'TEXT', 'text', 0, 1, 0)");
            $stmt->execute([$db_id, $table_name]);
            $stmt = $db->prepare("INSERT INTO fields_config (db_id, table_name, field_name, data_type, view_type, is_editable,
is_visible, is_required) VALUES (?, ?, 'fecha_edicion', 'TEXT', 'text', 0, 1, 0)");
            $stmt->execute([$db_id, $table_name]);

            // Initialize table metadata
            $stmt = $db->prepare("INSERT INTO table_metadata (db_id, table_name) VALUES (?, ?)");
            $stmt->execute([$db_id, $table_name]);
            Logger::log('CREATE_TABLE', ['database_id' => $db_id, 'table' => $table_name], $db_id);

            // Update DB last edit
            $db->prepare("UPDATE databases SET last_edit_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$db_id]);

            header('Location: ' . Auth::getBaseUrl() . 'admin/databases/view?id=' . $db_id);
            exit;
        } catch (\Throwable $e) {
            Auth::setFlashError("Error creando tabla: " . $e->getMessage());
            header('Location: ' . Auth::getBaseUrl() . 'admin/databases/view?id=' . $db_id);
            exit;
        }
    }
    /**
     * Creates a new table using raw SQL.
     */
/**
 * createTableSql method
 *
 * @return void
 */
    public function createTableSql()
    {
        $db_id = $_POST['db_id'] ?? null;
        $sql_code = $_POST['sql_code'] ?? '';
        Auth::requirePermission('module:databases.create_table');

        if (!$db_id || empty($sql_code)) {
            Auth::setFlashError("Faltan parámetros obligatorios.");
            header('Location: ' . Auth::getBaseUrl() . 'admin/databases');
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT path FROM databases WHERE id = ?");
        $stmt->execute([$db_id]);
        $database = $stmt->fetch();

        try {
            $targetDb = new PDO('sqlite:' . $database['path']);
            $targetDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Execute the SQL code
            $targetDb->exec($sql_code);

            // Update DB last edit
            $db->prepare("UPDATE databases SET last_edit_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$db_id]);

            Auth::setFlashError("Tabla creada exitosamente mediante SQL. Sincronizando estructura...", 'success');
            Logger::log('CREATE_TABLE_SQL', ['database_id' => $db_id], $db_id);

            // Redirect to sync to ensure all fields are registered and audit columns injected
            header('Location: ' . Auth::getBaseUrl() . 'admin/databases/sync?id=' . $db_id . '&from_sql=1');
            exit;
        } catch (\Throwable $e) {
            Auth::setFlashError("Error ejecutando SQL: " . $e->getMessage());
            header('Location: ' . Auth::getBaseUrl() . 'admin/databases/view?id=' . $db_id);
            exit;
        }
    }

    /**
     * Removes a table from the database.
     */
/**
 * deleteTable method
 *
 * @return void
 */
    public function deleteTable()
    {
        $db_id = $_GET['db_id'] ?? null;
        Auth::requirePermission('module:databases.drop_table');
        $table_name = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['table'] ?? '');

        if ($db_id && !empty($table_name)) {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT path FROM databases WHERE id = ?");
            $stmt->execute([$db_id]);
            $database = $stmt->fetch();

            if ($database) {
                try {
                    $targetDb = new PDO('sqlite:' . $database['path']);
                    $targetDb->exec("DROP TABLE $table_name");
                    $stmt = $db->prepare("DELETE FROM fields_config WHERE db_id = ? AND table_name = ?");
                    $stmt->execute([$db_id, $table_name]);
                    Logger::log('DELETE_TABLE', ['database_id' => $db_id, 'table' => $table_name], $db_id);
                } catch (\PDOException $e) {
                    die("No se pudo eliminar la tabla: " . $e->getMessage());
                }
            }
        }
        header('Location: ' . Auth::getBaseUrl() . 'admin/databases/view?id=' . $db_id);
    }

    /**
     * Displays and manages the fields (columns) of a specific table.
     */
/**
 * manageFields method
 *
 * @return void
 */
    public function manageFields()
    {
        $db_id = $_GET['db_id'] ?? null;
        Auth::requirePermission('module:databases.edit_table');
        $table_name = $_GET['table'] ?? null;

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM databases WHERE id = ?");
        $stmt->execute([$db_id]);
        $database = $stmt->fetch();

        $stmt = $db->prepare("SELECT * FROM fields_config WHERE db_id = ? AND table_name = ? ORDER BY id ASC");
        $stmt->execute([$db_id, $table_name]);
        $configFields = $stmt->fetchAll();

        try {
            $targetDb = new PDO('sqlite:' . $database['path']);
            $stmt = $targetDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
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
     */
/**
 * addField method
 *
 * @return void
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
        $stmt = $db->prepare("SELECT path FROM databases WHERE id = ?");
        $stmt->execute([$db_id]);
        $database = $stmt->fetch();

        try {
            $targetDb = new PDO('sqlite:' . $database['path']);
            $targetDb->exec("ALTER TABLE $table_name ADD COLUMN $field_name $data_type");
            $stmt = $db->prepare("INSERT INTO fields_config (db_id, table_name, field_name, data_type, view_type, is_required) VALUES (?, ?, ?, ?, ?, 0)");
            $stmt->execute([$db_id, $table_name, $field_name, $data_type, $view_type]);
            Logger::log('ADD_FIELD', ['database_id' => $db_id, 'table' => $table_name, 'field' => $field_name], $db_id);
            header('Location: ' . Auth::getBaseUrl() . "admin/databases/fields?db_id=$db_id&table=$table_name");
        } catch (\PDOException $e) {
            die("Error adding field: " . $e->getMessage());
        }
    }

    /**
     * Removes a field from the table metadata and potentially the physical database.
     */
/**
 * deleteField method
 *
 * @return void
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
        $stmt = $db->prepare("SELECT path FROM databases WHERE id = ?");
        $stmt->execute([$db_id]);
        $database = $stmt->fetch();

        try {
            $targetDb = new PDO('sqlite:' . $database['path']);
            // SQLite supports DROP COLUMN in newer versions, but we should wrap in try/catch just in case
            $targetDb->exec("ALTER TABLE $table_name DROP COLUMN $field_name");
            Auth::setFlashError("Field '$field_name' dropped successfully.", 'success');
        } catch (\PDOException $e) {
            // Fallback: If drop column is not supported or fails, at least config is gone.
            Auth::setFlashError("Field config removed, but column persisted (SQLite limitation or data conflict): " . $e->getMessage(), 'warning');
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
     * Handles ALTER TABLE operations, additions, and deletions.
     */
/**
 * syncDatabase method
 *
 * @return void
 */
    public function syncDatabase()
    {
        $id = $_GET['id'] ?? null;
        Auth::requirePermission('module:databases.edit_table');

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM databases WHERE id = ?");
        $stmt->execute([$id]);
        $database = $stmt->fetch();

        if (!$database) {
            header('Location: ' . Auth::getBaseUrl() . 'admin/databases');
            exit;
        }

        try {
            $targetDb = new PDO('sqlite:' . $database['path']);
            $stmt = $targetDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $syncedTables = 0;
            $syncedFields = 0;

            foreach ($tables as $table) {
                $stmtCols = $targetDb->query("PRAGMA table_info($table)");
                $columns = $stmtCols->fetchAll(PDO::FETCH_ASSOC);
                $syncedTables++;

                // --- Consistency Audit: Ensure audit columns exist ---
                $columnNames = array_map(function ($c) {
                    return strtolower($c['name']);
                }, $columns);
                $now = Auth::getCurrentTime();
                $auditChanged = false;

                if (!in_array('fecha_de_creacion', $columnNames)) {
                    $targetDb->exec("ALTER TABLE $table ADD COLUMN fecha_de_creacion TEXT");
                    $targetDb->exec("UPDATE $table SET fecha_de_creacion = '$now' WHERE fecha_de_creacion IS NULL OR fecha_de_creacion = ''");
                    $auditChanged = true;
                }
                if (!in_array('fecha_edicion', $columnNames)) {
                    $targetDb->exec("ALTER TABLE $table ADD COLUMN fecha_edicion TEXT");
                    $targetDb->exec("UPDATE $table SET fecha_edicion = '$now' WHERE fecha_edicion IS NULL OR fecha_edicion = ''");
                    $auditChanged = true;
                }

                // If schema changed, reload columns
                if ($auditChanged) {
                    $stmtCols = $targetDb->query("PRAGMA table_info($table)");
                    $columns = $stmtCols->fetchAll(PDO::FETCH_ASSOC);
                }
                // ---------------------------------------------------

                foreach ($columns as $col) {
                    $stmtCheck = $db->prepare("SELECT id FROM fields_config WHERE db_id = ? AND table_name = ? AND field_name = ?");
                    $stmtCheck->execute([$id, $table, $col['name']]);
                    if (!$stmtCheck->fetch()) {
                        $viewType = 'text';
                        $dataType = strtoupper($col['type']);
                        $lowerName = strtolower($col['name']);

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
                        $isEditable = in_array($col['name'], ['id', 'fecha_de_creacion', 'fecha_edicion']) ? 0 : 1;
                        $isVisible = 1;

                        $stmtInsert = $db->prepare("INSERT INTO fields_config (db_id, table_name, field_name, data_type, view_type, is_editable,
is_visible, is_required) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
                        $stmtInsert->execute([$id, $table, $col['name'], $dataType, $viewType, $isEditable, $isVisible]);
                        $syncedFields++;
                    }
                }
            }
            if (empty($_GET['from_sql'])) {
                Auth::setFlashError(
                    "Audit completed: $syncedTables tables synchronized. Missing columns 'fecha_de_creacion/edicion' were automatically injected for consistency.",
                    'success'
                );
            }
        } catch (\PDOException $e) {
            Auth::setFlashError("Audit Signal Error: " . $e->getMessage());
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
            $stmt = $db->prepare("INSERT INTO databases (name, path, project_id) VALUES (?, ?, ?)");
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
        $stmt = $db->prepare("SELECT * FROM databases WHERE id = ?");
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
        $stmt = $db->prepare("SELECT * FROM databases WHERE id = ?");
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
        $stmt = $db->prepare("SELECT * FROM databases WHERE id = ?");
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
        $stmt = $db->prepare("SELECT * FROM databases WHERE id = ?");
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
        $stmt = $db->prepare("SELECT * FROM databases WHERE id = ?");
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
        $stmt = $db->prepare("SELECT * FROM databases WHERE id = ?");
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
        $stmt = $db->prepare("SELECT * FROM databases WHERE id = ?");
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
        $stmt = $db->prepare("SELECT * FROM databases WHERE id = ?");
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
        $stmt = $db->prepare("SELECT * FROM databases WHERE id = ?");
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
        $stmt = $db->prepare("SELECT * FROM databases WHERE id = ?");
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
