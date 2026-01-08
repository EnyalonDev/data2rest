<?php

namespace App\Modules\Auth;

use App\Core\Auth;
use App\Core\Database;
use App\Core\BaseController;
use PDO;

/**
 * User Management Controller
 * Handles CRUD operations for system users and their assignments to roles and groups.
 */
class UserController extends BaseController
{
    /**
     * Constructor enforces access control.
     * Requirement: 'module:users' permission with 'manage' action.
     */
    public function __construct()
    {
        Auth::requirePermission('module:users', 'manage');
    }

    /**
     * Displays the list of all system users.
     */
    public function index()
    {
        $db = Database::getInstance()->getConnection();
        // Fetch users with their associated role and group names
        $users = $db->query("SELECT u.*, r.name as role_name, g.name as group_name FROM users u 
                             LEFT JOIN roles r ON u.role_id = r.id 
                             LEFT JOIN groups g ON u.group_id = g.id
                             ORDER BY u.id DESC")->fetchAll();

        $this->view('admin/users/index', [
            'users' => $users,
            'title' => 'Users - Control Center',
            'breadcrumbs' => [
                \App\Core\Lang::get('common.team') => 'admin/users',
                \App\Core\Lang::get('common.users') => null
            ]
        ]);
    }

    /**
     * Displays a form to create or edit a user.
     */
    public function form()
    {
        $id = $_GET['id'] ?? null;
        $user = null;
        $db = Database::getInstance()->getConnection();

        // If an ID is provided, fetch existing user data for editing
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch();
        }

        // Fetch available roles and groups for selection in the form
        $roles = $db->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();
        $groups = $db->query("SELECT * FROM groups ORDER BY name ASC")->fetchAll();

        $this->view('admin/users/form', [
            'user' => $user,
            'roles' => $roles,
            'groups' => $groups,
            'id' => $id,
            'title' => ($id ? 'Edit' : 'New') . ' User',
            'breadcrumbs' => [
                \App\Core\Lang::get('common.team') => 'admin/users',
                \App\Core\Lang::get('common.users') => 'admin/users',
                ($id ? 'Edit' : 'New') . ' Agent' => null
            ]
        ]);
    }

    /**
     * Handles the saving (insert or update) of user data.
     */
    public function save()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            return;

        $db = Database::getInstance()->getConnection();
        $id = $_POST['id'] ?? null;
        $username = $_POST['username'];

        // Sanitize username: replace spaces and special characters with underscores
        $username = preg_replace('/[^a-zA-Z0-9]+/', '_', trim($username));
        $username = trim($username, '_');

        $role_id = $_POST['role_id'];
        $status = isset($_POST['status']) ? 1 : 0;
        $group_id = !empty($_POST['group_id']) ? $_POST['group_id'] : null;

        if ($id) {
            // Update existing user
            if (!empty($_POST['password'])) {
                // If password is provided, re-hash and update it
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET username = ?, password = ?, role_id = ?, group_id = ?, status = ? WHERE id = ?");
                $stmt->execute([$username, $password, $role_id, $group_id, $status, $id]);
            } else {
                // Skip password update if field is empty
                $stmt = $db->prepare("UPDATE users SET username = ?, role_id = ?, group_id = ?, status = ? WHERE id = ?");
                $stmt->execute([$username, $role_id, $group_id, $status, $id]);
            }
        } else {
            // Create new user
            $password = password_hash($_POST['password'] ?? '123456', PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, password, role_id, group_id, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $password, $role_id, $group_id, $status]);
        }

        $this->redirect('admin/users');
    }

    /**
     * Deletes a user by ID.
     * Prevents self-deletion.
     */
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

