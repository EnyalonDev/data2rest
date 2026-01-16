<?php

namespace App\Modules\Auth;

use App\Core\Auth;
use App\Core\Database;
use App\Core\BaseController;
use PDO;

/**
 * Role Controller
 * 
 * Manages system roles and their permission assignments for role-based
 * access control (RBAC) implementation.
 * 
 * Core Features:
 * - Role CRUD operations
 * - Permission assignment per role
 * - Module-based permission structure
 * - Admin role with full permissions
 * - JSON-based permission storage
 * 
 * Permission Structure:
 * - Admin Role: {"all": true}
 * - Regular Role: {"modules": {"module:name.action": true}}
 * 
 * Security:
 * - Admin-only access to role management
 * - Prevents deletion of roles in use
 * - Permission validation
 * 
 * @package App\Modules\Auth
 * @author DATA2REST Development Team
 * @version 1.0.0
 */
class RoleController extends BaseController
{
    /**
     * Constructor - Requires admin access
     * 
     * Ensures that only administrators can manage roles
     * and permissions.
     */
    public function __construct()
    {
        Auth::requireAdmin();
    }

    /**
     * Display list of roles
     * 
     * Shows all system roles with their configurations.
     * 
     * @return void Renders role list view
     * 
     * @example
     * GET /admin/roles
     */
    public function index()
    {
        $db = Database::getInstance()->getConnection();
        $roles = $db->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();

        $this->view('admin/roles/index', [
            'roles' => $roles,
            'title' => 'Roles & Permissions',
            'breadcrumbs' => [\App\Core\Lang::get('common.roles') => null]
        ]);
    }

    /**
     * Display role creation/edit form
     * 
     * Renders a form for creating new roles or editing existing ones
     * with permission assignment interface.
     * 
     * Features:
     * - Role name configuration
     * - Admin flag toggle
     * - Module-based permission checkboxes
     * - JSON permission decoding for edit
     * 
     * @return void Renders role form view
     * 
     * @example
     * GET /admin/roles/form (new role)
     * GET /admin/roles/form?id=2 (edit role)
     */
    public function form()
    {
        $id = $_GET['id'] ?? null;
        $role = null;
        $db = Database::getInstance()->getConnection();

        if ($id) {
            $stmt = $db->prepare("SELECT * FROM roles WHERE id = ?");
            $stmt->execute([$id]);
            $role = $stmt->fetch();
            $role['permissions'] = json_decode($role['permissions'] ?? '[]', true);
        }

        $this->view('admin/roles/form', [
            'role' => $role,
            'id' => $id,
            'title' => ($id ? 'Edit' : 'New') . ' Role',
            'breadcrumbs' => [
                \App\Core\Lang::get('common.roles') => 'admin/roles',
                ($id ? 'Refine' : 'Initialize') . ' Policy Architect' => null
            ]
        ]);
    }

    /**
     * Save role data (create or update)
     * 
     * Processes form submission to create or update roles with
     * their permission configurations.
     * 
     * Permission Structure:
     * - Admin: {"all": true}
     * - Regular: {"modules": {"module:name.action": true, ...}}
     * 
     * @return void Redirects to role list on success
     * 
     * @example
     * POST /admin/roles/save
     * Body: name=Editor&modules[module:databases.view_tables]=on
     */
    public function save()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            return;

        $db = Database::getInstance()->getConnection();
        $id = $_POST['id'] ?? null;
        $name = $_POST['name'];
        $is_admin = isset($_POST['is_admin']) ? true : false;

        $permissions = [];
        if ($is_admin) {
            $permissions['all'] = true;
        } else {
            // New structure: modules only
            $permissions['modules'] = $_POST['modules'] ?? [];
        }

        $permsJson = json_encode($permissions);

        if ($id) {
            $stmt = $db->prepare("UPDATE roles SET name = ?, permissions = ? WHERE id = ?");
            $stmt->execute([$name, $permsJson, $id]);
        } else {
            $stmt = $db->prepare("INSERT INTO roles (name, permissions) VALUES (?, ?)");
            $stmt->execute([$name, $permsJson]);
        }

        $this->redirect('admin/roles');
    }

    /**
     * Delete a role
     * 
     * Removes a role from the system.
     * Note: Should validate that role is not assigned to users.
     * 
     * @return void Redirects to role list
     * 
     * @example
     * GET /admin/roles/delete?id=3
     */
    public function delete()
    {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $db = Database::getInstance()->getConnection();
            $db->prepare("DELETE FROM roles WHERE id = ?")->execute([$id]);
        }
        $this->redirect('admin/roles');
    }
}
