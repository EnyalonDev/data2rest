<?php

namespace App\Modules\Auth;

use App\Core\Auth;
use App\Core\Database;
use PDO;

class RoleController {
    public function __construct() {
        Auth::requireAdmin();
    }

    public function index() {
        $db = Database::getInstance()->getConnection();
        $roles = $db->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();
        require_once __DIR__ . '/../../Views/admin/roles/index.php';
    }

    public function form() {
        $id = $_GET['id'] ?? null;
        $role = null;
        $db = Database::getInstance()->getConnection();
        
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM roles WHERE id = ?");
            $stmt->execute([$id]);
            $role = $stmt->fetch();
            $role['permissions'] = json_decode($role['permissions'] ?? '[]', true);
        }

        $databases = $db->query("SELECT * FROM databases ORDER BY name ASC")->fetchAll();
        
        require_once __DIR__ . '/../../Views/admin/roles/form.php';
    }

    public function save() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $db = Database::getInstance()->getConnection();
        $id = $_POST['id'] ?? null;
        $name = $_POST['name'];
        $is_admin = isset($_POST['is_admin']) ? true : false;
        
        $permissions = [];
        if ($is_admin) {
            $permissions['all'] = true;
        } else {
            $permissions['modules'] = $_POST['modules'] ?? [];
            $permissions['databases'] = $_POST['db_perms'] ?? [];
        }

        $permsJson = json_encode($permissions);

        if ($id) {
            $stmt = $db->prepare("UPDATE roles SET name = ?, permissions = ? WHERE id = ?");
            $stmt->execute([$name, $permsJson, $id]);
        } else {
            $stmt = $db->prepare("INSERT INTO roles (name, permissions) VALUES (?, ?)");
            $stmt->execute([$name, $permsJson]);
        }

        header('Location: ' . Auth::getBaseUrl() . 'admin/roles');
    }

    public function delete() {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $db = Database::getInstance()->getConnection();
            $db->prepare("DELETE FROM roles WHERE id = ?")->execute([$id]);
        }
        header('Location: ' . Auth::getBaseUrl() . 'admin/roles');
    }
}
