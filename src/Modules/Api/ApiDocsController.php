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
        Auth::requirePermission('module:api', 'view_keys');
        $db = Database::getInstance()->getConnection();
        $keys = $db->query("SELECT * FROM api_keys ORDER BY id DESC")->fetchAll();
        $databases = $db->query("SELECT * FROM databases ORDER BY name ASC")->fetchAll();

        $this->view('admin/api/index', [
            'keys' => $keys,
            'databases' => $databases,
            'title' => 'API Management',
            'breadcrumbs' => [\App\Core\Lang::get('common.api') => null]
        ]);
    }

    public function createKey()
    {
        Auth::requirePermission('module:api', 'manage_keys');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? 'New Key';
            $key = bin2hex(random_bytes(32));
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("INSERT INTO api_keys (key_value, name, status) VALUES (?, ?, 1)");
            $stmt->execute([$key, $name]);
        }
        $this->redirect('admin/api');
    }

    public function deleteKey()
    {
        Auth::requirePermission('module:api', 'manage_keys');
        $id = $_GET['id'] ?? null;
        if ($id) {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("DELETE FROM api_keys WHERE id = ?");
            $stmt->execute([$id]);
        }
        $this->redirect('admin/api');
    }

    public function docs()
    {
        $db_id = $_GET['db_id'] ?? null;
        Auth::requirePermission('module:api', 'view_docs');
        Auth::requireDatabaseAccess($db_id);

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM databases WHERE id = ?");
        $stmt->execute([$db_id]);
        $database = $stmt->fetch();

        if (!$database) {
            die("Database not found");
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
