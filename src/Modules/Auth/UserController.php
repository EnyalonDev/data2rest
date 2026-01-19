<?php

namespace App\Modules\Auth;

use App\Core\Auth;
use App\Core\Database;
use App\Core\BaseController;
use PDO;

/**
 * User Management Controller
 * 
 * Comprehensive user administration system with role-based access control
 * and group isolation for multi-tenant environments.
 * 
 * Core Features:
 * - User CRUD operations
 * - Role and group assignment
 * - Password management
 * - User search and filtering
 * - Group-based isolation for non-admins
 * - Self-deletion prevention
 * 
 * Access Control:
 * - Admin: Full access to all users
 * - Non-Admin: Access limited to own group
 * - Permission-based operations (view, create, edit, delete)
 * 
 * Security:
 * - Password hashing with PASSWORD_DEFAULT
 * - Permission validation on all operations
 * - Group isolation enforcement
 * - Self-deletion prevention
 * 
 * User Fields:
 * - username (sanitized, unique)
 * - password (hashed)
 * - role_id (defines permissions)
 * - group_id (multi-tenant isolation)
 * - status (active/inactive)
 * - public_name, email, phone, address
 * 
 * @package App\Modules\Auth
 * @author DATA2REST Development Team
 * @version 1.0.0
 */
/**
 * UserController Controller
 *
 * Core Features: TODO
 *
 * Security: Requires login, permission checks as implemented.
 *
 * @package App\Modules\
 * @author DATA2REST Development Team
 * @version 1.0.0
 */
class UserController extends BaseController
{
    /**
     * Constructor - Requires user view permission
     * 
     * Enforces that only users with 'module:users.view_users'
     * permission can access user management functionality.
     */
    /**
     * __construct method
     *
     * @return void
     */
    public function __construct()
    {
        // Allow anyone with view access to enter
        Auth::requirePermission('module:users.view_users');
    }

    /**
     * Display list of users
     * 
     * Shows all users with role and group information.
     * Implements group-based isolation for non-admin users.
     * 
     * Features:
     * - Admin: View all users with optional group filter
     * - Non-Admin: View only users in same group
     * - Search functionality (username, name, email)
     * - Role and group information display
     * 
     * @return void Renders user list view
     * 
     * @example
     * GET /admin/users
     * GET /admin/users?group_id=1&search=john
     */
    /**
     * index method
     *
     * @return void
     */
    public function index()
    {
        $db = Database::getInstance()->getConnection();

        if (Auth::isAdmin()) {
            $groupId = $_GET['group_id'] ?? null;
            $search = $_GET['search'] ?? null;

            $sql = "SELECT u.*, r.name as role_name, g.name as group_name FROM users u 
                    LEFT JOIN roles r ON u.role_id = r.id 
                    LEFT JOIN " . Database::getInstance()->getAdapter()->quoteName('groups') . " g ON u.group_id = g.id";
            $params = [];
            $where = [];

            if ($groupId) {
                $where[] = "u.group_id = ?";
                $params[] = $groupId;
            }

            if ($search) {
                $where[] = "(u.username LIKE ? OR u.public_name LIKE ? OR u.email LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }

            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }

            $sql .= " ORDER BY u.id DESC";

            // Execute safe query
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll();

        } else {
            // Non-admin: Enforce own group isolation
            $userGroup = $_SESSION['group_id'] ?? null;
            if (!$userGroup) {
                // No group assigned? See only self? Or nothing? 
                // Let's show only self to be safe.
                $sql = "SELECT u.*, r.name as role_name, g.name as group_name FROM users u 
                        LEFT JOIN roles r ON u.role_id = r.id 
                        LEFT JOIN " . Database::getInstance()->getAdapter()->quoteName('groups') . " g ON u.group_id = g.id
                        WHERE u.id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$_SESSION['user_id']]);
                $users = $stmt->fetchAll();
            } else {
                $sql = "SELECT u.*, r.name as role_name, g.name as group_name FROM users u 
                        LEFT JOIN roles r ON u.role_id = r.id 
                        LEFT JOIN " . Database::getInstance()->getAdapter()->quoteName('groups') . " g ON u.group_id = g.id
                        WHERE u.group_id = ?
                        ORDER BY u.username ASC";
                $stmt = $db->prepare($sql);
                $stmt->execute([$userGroup]);
                $users = $stmt->fetchAll();
            }
        }

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
     * Display user creation/edit form
     * 
     * Renders a form for creating new users or editing existing ones.
     * Loads available roles and groups for assignment.
     * 
     * Features:
     * - Create new user (requires invite_users permission)
     * - Edit existing user (requires edit_users permission)
     * - Role selection dropdown
     * - Group assignment
     * - User profile fields
     * 
     * @return void Renders user form view
     * 
     * @example
     * GET /admin/users/form (new user)
     * GET /admin/users/form?id=5 (edit user)
     */
    /**
     * form method
     *
     * @return void
     */
    public function form()
    {
        $id = $_GET['id'] ?? null;
        if ($id) {
            Auth::requirePermission('module:users.edit_users');
        } else {
            Auth::requirePermission('module:users.invite_users');
        }
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
        $groups = $db->query("SELECT * FROM " . Database::getInstance()->getAdapter()->quoteName('groups') . " ORDER BY name ASC")->fetchAll();

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
     * Save user data (create or update)
     * 
     * Processes form submission to create new users or update existing ones.
     * Handles password hashing and username sanitization.
     * 
     * Features:
     * - Username sanitization (alphanumeric + underscores)
     * - Password hashing with PASSWORD_DEFAULT
     * - Optional password update (only if provided)
     * - Role and group assignment
     * - Status management (active/inactive)
     * - Profile information (name, email, phone, address)
     * 
     * @return void Redirects to user list on success
     * 
     * @example
     * POST /admin/users/save
     * Body: username=john_doe&password=secret&role_id=2&group_id=1
     */
    /**
     * save method
     *
     * @return void
     */
    public function save()
    {
        $id = $_POST['id'] ?? null;
        if ($id) {
            Auth::requirePermission('module:users.edit_users');
        } else {
            Auth::requirePermission('module:users.invite_users');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            return;

        $db = Database::getInstance()->getConnection();
        $username = $_POST['username'];

        // Sanitize username: replace spaces and special characters with underscores
        $username = preg_replace('/[^a-zA-Z0-9]+/', '_', trim($username));
        $username = trim($username, '_');

        $role_id = $_POST['role_id'];
        $status = isset($_POST['status']) ? 1 : 0;
        $group_id = !empty($_POST['group_id']) ? $_POST['group_id'] : null;

        $public_name = $_POST['public_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';

        if ($id) {
            // Update existing user
            if (!empty($_POST['password'])) {
                // If password is provided, re-hash and update it
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET username = ?, password = ?, role_id = ?, group_id = ?, status = ?, public_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
                $stmt->execute([$username, $password, $role_id, $group_id, $status, $public_name, $email, $phone, $address, $id]);
            } else {
                // Skip password update if field is empty
                $stmt = $db->prepare("UPDATE users SET username = ?, role_id = ?, group_id = ?, status = ?, public_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
                $stmt->execute([$username, $role_id, $group_id, $status, $public_name, $email, $phone, $address, $id]);
            }
        } else {
            // Create new user
            $password = password_hash($_POST['password'] ?? '123456', PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, password, role_id, group_id, status, public_name, email, phone, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $password, $role_id, $group_id, $status, $public_name, $email, $phone, $address]);
        }

        $this->redirect('admin/users');
    }

    /**
     * Delete a user
     * 
     * Removes a user from the system with self-deletion prevention.
     * 
     * Security:
     * - Requires delete_users permission
     * - Prevents users from deleting themselves
     * - Hard delete (no soft delete)
     * 
     * @return void Redirects to user list
     * 
     * @example
     * GET /admin/users/delete?id=5
     */
    /**
     * delete method
     *
     * @return void
     */
    public function delete()
    {
        Auth::requirePermission('module:users.delete_users');
        $id = $_GET['id'] ?? null;
        if ($id && $id != $_SESSION['user_id']) {
            $db = Database::getInstance()->getConnection();
            $db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        }
        $this->redirect('admin/users');
    }
}

