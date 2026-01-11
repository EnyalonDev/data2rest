<?php

namespace App\Modules\Database;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Config;
use App\Core\BaseController;
use PDO;

class MaintenanceController extends BaseController
{
    public function __construct()
    {
        Auth::requireLogin();
    }

    public function resetSystem()
    {
        Auth::requireAdmin();

        $db = Database::getInstance()->getConnection();

        // 1. Get all databases to delete files
        $databases = $db->query("SELECT * FROM databases")->fetchAll();
        foreach ($databases as $d) {
            $fullPath = realpath($d['path']) ?: $d['path'];
            error_log("Attempting to delete database file: " . $fullPath);
            if (file_exists($fullPath)) {
                if (unlink($fullPath)) {
                    error_log("Deleted successfully: " . $fullPath);
                } else {
                    error_log("Failed to delete: " . $fullPath);
                }
            } else {
                error_log("File does not exist: " . $fullPath);
            }
        }

        // 2. Clear tables
        $db->exec("DELETE FROM databases");
        $db->exec("DELETE FROM fields_config");
        $db->exec("DELETE FROM user_db_permissions");
        $db->exec("DELETE FROM logs");

        // 3. Reset roles permissions (keeping only module perms)
        $stmt = $db->query("SELECT id, permissions FROM roles");
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $updateStmt = $db->prepare("UPDATE roles SET permissions = :perms WHERE id = :id");

        foreach ($roles as $role) {
            $perms = json_decode($role['permissions'] ?? '[]', true);
            if ($perms && isset($perms['databases'])) {
                unset($perms['databases']);
                $newPerms = json_encode($perms);
                $updateStmt->execute([':perms' => $newPerms, ':id' => $role['id']]);
            }
        }

        $countRemoved = count($databases);
        Auth::setFlashError("System reset successful. $countRemoved databases and all configurations have been purged.", 'success');
        $this->redirect('admin/dashboard');
    }

    public function loadDemo()
    {
        Auth::requirePermission('module:databases', 'create');

        $db = Database::getInstance()->getConnection();

        // Check if DBs already exist
        $count = $db->query("SELECT COUNT(*) FROM databases")->fetchColumn();
        if ($count > 0) {
            Auth::setFlashError("Demo can only be loaded when no databases are present.");
            $this->redirect('admin/dashboard');
        }

        $jsonFile = __DIR__ . '/demo/enterprise_demo.json';
        if (!file_exists($jsonFile)) {
            die("Demo configuration file not found at $jsonFile");
        }

        $demoData = json_decode(file_get_contents($jsonFile), true);
        if (!$demoData) {
            die("Error parsing demo JSON file.");
        }

        $dbName = $demoData['database']['name'];
        $filename = "enterprise_demo_" . uniqid() . ".sqlite";
        $storagePath = Config::get('db_storage_path');
        $path = $storagePath . $filename;

        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0777, true);
        }

        try {
            $targetDb = new PDO('sqlite:' . $path);
            $targetDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $now = Auth::getCurrentTime();

            // 1. Create Tables and Insert Data
            foreach ($demoData['tables'] as $tableName => $config) {
                // Table schema
                $cols = array_merge(['id INTEGER PRIMARY KEY AUTOINCREMENT'], $config['columns'], ['fecha_de_creacion TEXT', 'fecha_edicion TEXT']);
                $sql = "CREATE TABLE $tableName (" . implode(", ", $cols) . ")";
                $targetDb->exec($sql);

                // Data insertion
                if (!empty($config['data'])) {
                    foreach ($config['data'] as $row) {
                        $row['fecha_de_creacion'] = $now;
                        $row['fecha_edicion'] = $now;

                        $fields = array_keys($row);
                        $placeholders = array_fill(0, count($fields), '?');

                        $insertSql = "INSERT INTO $tableName (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $placeholders) . ")";
                        $stmt = $targetDb->prepare($insertSql);
                        $stmt->execute(array_values($row));
                    }
                }
            }

            // Register in System
            $stmt = $db->prepare("INSERT INTO databases (name, path, created_at) VALUES (?, ?, ?)");
            $stmt->execute([$dbName, $path, $now]);
            $dbId = $db->lastInsertId();

            // Set up Foreign Keys and run sync
            $this->internalSync($dbId, $path);

            $updateFk = $db->prepare("UPDATE fields_config SET is_foreign_key = 1, related_table = ?, related_field = ? WHERE db_id = ? AND table_name = ? AND field_name = ?");
            foreach ($demoData['foreign_keys'] as $setup) {
                $updateFk->execute([$setup['related_table'], $setup['related_field'], $dbId, $setup['table'], $setup['field']]);
            }

            Auth::setFlashError("Demo Data Loaded Successfully: $dbName is ready.", 'success');
            header('Location: ' . Auth::getBaseUrl() . 'admin/dashboard');
            exit;

        } catch (\PDOException $e) {
            die("Error generating demo: " . $e->getMessage());
        }
    }

    private function internalSync($id, $path)
    {
        $db = Database::getInstance()->getConnection();
        $targetDb = new PDO('sqlite:' . $path);
        $tables = $targetDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $columns = $targetDb->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($columns as $col) {
                $viewType = 'text';
                $dataType = strtoupper($col['type']);
                $lowerName = strtolower($col['name']);

                if (preg_match('/(imagen|image|foto|photo|img|avatar|logo|thumbnail|picture|gallery|galeria)/i', $lowerName)) {
                    $viewType = preg_match('/gallery|galeria/i', $lowerName) ? 'gallery' : 'image';
                } elseif (preg_match('/(descripcion|description|content|contenido|mensaje|message|bio|body|text)/i', $lowerName)) {
                    $viewType = 'textarea';
                } elseif (preg_match('/(status|activo|active|visible|enabled|public|borrado|deleted)/i', $lowerName) && ($dataType == 'INTEGER' || $dataType == 'INT')) {
                    $viewType = 'boolean';
                } elseif (preg_match('/(fecha|date|time|timestamp|momento|horario)/i', $lowerName) || preg_match('/(DATETIME|DATE|TIMESTAMP)/i', $dataType)) {
                    $viewType = 'datetime';
                }

                $isEditable = in_array($col['name'], ['id', 'fecha_de_creacion', 'fecha_edicion']) ? 0 : 1;
                $stmtInsert = $db->prepare("INSERT INTO fields_config (db_id, table_name, field_name, data_type, view_type, is_editable, is_visible, is_required) VALUES (?, ?, ?, ?, ?, ?, 1, 0)");
                $stmtInsert->execute([$id, $table, $col['name'], $dataType, $viewType, $isEditable]);
            }
        }
    }
}
