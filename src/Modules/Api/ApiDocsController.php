<?php

namespace App\Modules\Api;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Config;
use App\Core\BaseController;
use App\Core\Logger;
use PDO;

/**
 * API Docs Controller
 *
 * Provides management of API keys and generation of API documentation for databases.
 *
 * Core Features:
 * - List API keys and databases
 * - Create and revoke API keys
 * - Generate documentation for database tables and fields
 *
 * Security:
 * - Requires authenticated user
 * - Permission checks for viewing keys, creating keys, revoking keys, and viewing docs
 *
 * @package App\\Modules\\Api
 * @author DATA2REST Development Team
 * @version 1.0.0
 */
/**
 * ApiDocsController Controller
 *
 * Core Features: TODO
 *
 * Security: Requires login, permission checks as implemented.
 *
 * @package App\Modules\
 * @author DATA2REST Development Team
 * @version 1.0.0
 */
class ApiDocsController extends BaseController
{
    /**
     * Constructor - Requires user authentication.
     *
     * Ensures that all actions in this controller are only accessible to logged-in users.
     *
     * @return void
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
     * Display API keys and databases.
     *
     * Shows a list of active API keys and databases scoped to the current project.
     * Admin users see all keys, regular users see only their own.
     *
     * @return void Renders the `admin/api/index` view with keys and databases.
     * @example GET /admin/api
     */
    /**
     * index method
     *
     * @return void
     */
    public function index()
    {
        Auth::requirePermission('module:api.view_keys');
        $db = Database::getInstance()->getConnection();
        $projectId = Auth::getActiveProject();

        $userId = $_SESSION['user_id'] ?? null;

        if (Auth::isAdmin()) {
            // Super Admin sees all keys
            $sql = "SELECT * FROM api_keys WHERE status = 1 ORDER BY id DESC";
            $keys = $db->query($sql)->fetchAll();
        } else {
            // Regular users only see their own keys
            $stmt = $db->prepare("SELECT * FROM api_keys WHERE status = 1 AND user_id = ? ORDER BY id DESC");
            $stmt->execute([$userId]);
            $keys = $stmt->fetchAll();
        }

        // Scope databases list to project
        if ($projectId) {
            $stmt = $db->prepare("SELECT * FROM `databases` WHERE project_id = ? ORDER BY name ASC");
            $stmt->execute([$projectId]);
            $databases = $stmt->fetchAll();
        } else if (Auth::isAdmin()) {
            $databases = $db->query("SELECT * FROM `databases` ORDER BY name ASC")->fetchAll();
        } else {
            $databases = [];
        }

        $this->view('admin/api/index', [
            'keys' => $keys,
            'databases' => $databases,
            'title' => 'API Management',
            'breadcrumbs' => [\App\Core\Lang::get('common.api') => null]
        ]);
    }

    /**
     * Create a new API key.
     *
     * Generates a random 256-bit key and stores it associated with the current user.
     *
     * @return void Redirects back to the API management page.
     * @example POST /admin/api/createKey
     */
    /**
     * createKey method
     *
     * @return void
     */
    public function createKey()
    {
        Auth::requirePermission('module:api.create_keys');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? 'New Key';
            $description = $_POST['description'] ?? '';
            $rateLimit = (int) ($_POST['rate_limit'] ?? 1000);
            $key = bin2hex(random_bytes(32));
            $db = Database::getInstance()->getConnection();
            $userId = $_SESSION['user_id'] ?? null;
            $stmt = $db->prepare("INSERT INTO api_keys (key_value, name, description, rate_limit, user_id, status) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->execute([$key, $name, $description, $rateLimit, $userId]);

            Logger::log('API_KEY_CREATED', ['name' => $name, 'rate_limit' => $rateLimit]);
        }
        $this->redirect('admin/api');
    }

    /**
     * Delete (revoke) an API key.
     *
     * Removes the API key record, respecting admin rights or ownership.
     *
     * @return void Redirects back to the API management page.
     * @example GET /admin/api/deleteKey?id=5
     */
    /**
     * deleteKey method
     *
     * @return void
     */
    public function deleteKey()
    {
        Auth::requirePermission('module:api.revoke_keys'); // Mapped to 'revoke_keys' in policy_architect
        $id = $_GET['id'] ?? null;
        if ($id) {
            $db = Database::getInstance()->getConnection();
            $userId = $_SESSION['user_id'] ?? null;

            if (Auth::isAdmin()) {
                $stmt = $db->prepare("DELETE FROM api_keys WHERE id = ?");
                $stmt->execute([$id]);
            } else {
                $stmt = $db->prepare("DELETE FROM api_keys WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, $userId]);
            }
        }
        $this->redirect('admin/api');
    }

    /**
     * Generate API documentation for a database.
     *
     * Retrieves database schema and active API keys, then renders documentation view.
     *
     * @return void Renders the `admin/api/docs` view with schema details.
     * @example GET /admin/api/docs?db_id=3
     */
    /**
     * docs method
     *
     * @return void
     */
    public function docs()
    {
        $db_id = $_GET['db_id'] ?? null;
        Auth::requirePermission('module:api.view_docs');

        // Ensure user can access this DB context
        // Auth::requireDatabaseAccess is legacy/db-specific ID permission. 
        // We should just check if the DB belongs to current project, which requireDatabaseAccess does in the new Auth.php logic?
        // BUT we are moving away from db-specific permissions.
        // We really just need to know if the DB is in the active project.

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM `databases` WHERE id = ?");
        $stmt->execute([$db_id]);
        $database = $stmt->fetch();

        if (!$database)
            die("Database not found");

        // Scoping check
        $projectId = Auth::getActiveProject();
        if (!Auth::isAdmin() && $database['project_id'] != $projectId) {
            Auth::setFlashError("Access Denied: Database belongs to another project.", 'error');
            $this->redirect('admin/api');
        }

        $apiKeys = $db->query("SELECT name, key_value FROM api_keys WHERE status = 1 ORDER BY name ASC")->fetchAll();

        try {
            $adapter = \App\Core\DatabaseManager::getAdapter($database);
            $targetDb = $adapter->getConnection();
            $dbType = $adapter->getType();

            $tables = [];
            $tableDetails = [];

            if ($dbType === 'sqlite') {
                $stmt = $targetDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

                foreach ($tables as $table) {
                    $stmt = $targetDb->query("PRAGMA table_info(" . $adapter->quoteName($table) . ")");
                    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    // Standardize structure if needed, but existing view likely expects PRAGMA format (cid, name, type, notnull, dflt_value, pk)
                    $tableDetails[$table] = $cols;
                }
            } elseif ($dbType === 'mysql') {
                $stmt = $targetDb->query("SHOW TABLES");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

                foreach ($tables as $table) {
                    $stmt = $targetDb->query("SHOW COLUMNS FROM " . $adapter->quoteName($table));
                    $mysqlCols = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Convert MySQL SHOW COLUMNS format to SQLite PRAGMA-like format for view compatibility
                    // MySQL: Field, Type, Null, Key, Default, Extra
                    // SQLite: cid, name, type, notnull, dflt_value, pk
                    $cleanCols = [];
                    foreach ($mysqlCols as $idx => $col) {
                        $cleanCols[] = [
                            'cid' => $idx,
                            'name' => $col['Field'],
                            'type' => $col['Type'],
                            'notnull' => ($col['Null'] === 'NO') ? 1 : 0,
                            'dflt_value' => $col['Default'],
                            'pk' => ($col['Key'] === 'PRI') ? 1 : 0
                        ];
                    }
                    $tableDetails[$table] = $cleanCols;
                }
            } elseif ($dbType === 'pgsql') {
                $stmt = $targetDb->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

                foreach ($tables as $table) {
                    $stmt = $targetDb->query($adapter->getTableStructureSQL($table));
                    $pgCols = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Convert to SQLite-like format
                    $cleanCols = [];
                    foreach ($pgCols as $idx => $col) {
                        $cleanCols[] = [
                            'cid' => $idx,
                            'name' => $col['name'],
                            'type' => $col['type'],
                            'notnull' => ($col['is_nullable'] === 'NO') ? 1 : 0,
                            'dflt_value' => $col['dflt_value'],
                            'pk' => (isset($col['pk']) && $col['pk']) ? 1 : 0 // getTableStructureSQL might need update for PK
                        ];
                    }
                    $tableDetails[$table] = $cleanCols;
                }
            } elseif ($dbType === 'pgsql' || $dbType === 'postgresql') {
                $stmt = $targetDb->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

                // Basic PostgreSQL support for now
                foreach ($tables as $table) {
                    $stmt = $targetDb->prepare("SELECT column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_name = ?");
                    $stmt->execute([$table]);
                    $pgCols = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $cleanCols = [];
                    foreach ($pgCols as $idx => $col) {
                        $cleanCols[] = [
                            'cid' => $idx,
                            'name' => $col['column_name'],
                            'type' => $col['data_type'],
                            'notnull' => ($col['is_nullable'] === 'NO') ? 1 : 0,
                            'dflt_value' => $col['column_default'],
                            'pk' => 0 // TODO: Check constraints for PK
                        ];
                    }
                    $tableDetails[$table] = $cleanCols;
                }
            }
        } catch (\PDOException $e) {
            die("Error connecting to database: " . $e->getMessage());
        }

        $this->view('admin/api/docs', [
            'database' => $database,
            'apiKeys' => $apiKeys,
            'tableDetails' => $tableDetails,
            'title' => 'API Documentation - ' . $database['name'],
            'breadcrumbs' => [
                \App\Core\Lang::get('common.api') => 'admin/api',
                'Documentation: ' . $database['name'] => null
            ]
        ]);
    }
}
