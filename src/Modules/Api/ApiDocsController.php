<?php

namespace App\Modules\Api;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Config;
use App\Core\BaseController;
use PDO;

class ApiDocsController extends BaseController
{
    public function __construct()
    {
        Auth::requireLogin();
    }

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
            $stmt = $db->prepare("SELECT * FROM databases WHERE project_id = ? ORDER BY name ASC");
            $stmt->execute([$projectId]);
            $databases = $stmt->fetchAll();
        } else if (Auth::isAdmin()) {
            $databases = $db->query("SELECT * FROM databases ORDER BY name ASC")->fetchAll();
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

    public function createKey()
    {
        Auth::requirePermission('module:api.create_keys');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? 'New Key';
            $key = bin2hex(random_bytes(32));
            $db = Database::getInstance()->getConnection();
            $userId = $_SESSION['user_id'] ?? null;
            $stmt = $db->prepare("INSERT INTO api_keys (key_value, name, user_id, status) VALUES (?, ?, ?, 1)");
            $stmt->execute([$key, $name, $userId]);
        }
        $this->redirect('admin/api');
    }

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

    public function docs()
    {
        $db_id = $_GET['db_id'] ?? null;
        Auth::requirePermission('module:api.view_docs');

        // Ensure user can access this DB context
        // Auth::requireDatabaseAccess is legacy/db-specific ID permission. 
        // We should just check if the DB belongs to current project, which requireDatabaseAccess does in the new Auth.php logic?
        // Let's verify Auth.php update -> Yes, it checks module:db:id. 
        // BUT we are moving away from db-specific permissions. 
        // We really just need to know if the DB is in the active project.

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM databases WHERE id = ?");
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
            $targetDb = new PDO('sqlite:' . $database['path']);
            $stmt = $targetDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $tableDetails = [];
            foreach ($tables as $table) {
                $stmt = $targetDb->query("PRAGMA table_info($table)");
                $tableDetails[$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
