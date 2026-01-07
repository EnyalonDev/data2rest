<?php

namespace App\Modules\Auth;

use App\Core\Auth;
use App\Core\Database;
use App\Core\BaseController;
use PDO;

class UserController extends BaseController
{
    public function __construct()
    {
        Auth::requirePermission('module:users', 'manage');
    }

    public function index()
    {
        $db = Database::getInstance()->getConnection();
        $users = $db->query("SELECT u.*, r.name as role_name FROM users u 
                             LEFT JOIN roles r ON u.role_id = r.id 
                             ORDER BY u.id DESC")->fetchAll();

        $this->view('admin/users/index', [
            'users' => $users,
            'title' => 'Users - Control Center',
            'breadcrumbs' => [\App\Core\Lang::get('common.users') => null]
        ]);
    }

    public function form()
    {
        $id = $_GET['id'] ?? null;
        $user = null;
        $db = Database::getInstance()->getConnection();

        if ($id) {
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch();
        }

        $roles = $db->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();

        $this->view('admin/users/form', [
            'user' => $user,
            'roles' => $roles,
            'id' => $id,
            'title' => ($id ? 'Edit' : 'New') . ' User',
            'breadcrumbs' => [
                \App\Core\Lang::get('common.users') => 'admin/users',
                ($id ? 'Edit' : 'New') . ' Agent' => null
            ]
        ]);
    }

    public function save()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            return;

        $db = Database::getInstance()->getConnection();
        $id = $_POST['id'] ?? null;
        $username = $_POST['username'];
        $role_id = $_POST['role_id'];
        $status = isset($_POST['status']) ? 1 : 0;

        if ($id) {
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET username = ?, password = ?, role_id = ?, status = ? WHERE id = ?");
                $stmt->execute([$username, $password, $role_id, $status, $id]);
            } else {
                $stmt = $db->prepare("UPDATE users SET username = ?, role_id = ?, status = ? WHERE id = ?");
                $stmt->execute([$username, $role_id, $status, $id]);
            }
        } else {
            $password = password_hash($_POST['password'] ?? '123456', PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, password, role_id, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $password, $role_id, $status]);
        }

        $this->redirect('admin/users');
    }

    public function delete()
    {
        $id = $_GET['id'] ?? null;
        if ($id && $id != $_SESSION['user_id']) {
            $db = Database::getInstance()->getConnection();
            $db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        }
        $this->redirect('admin/users');
    }
}
