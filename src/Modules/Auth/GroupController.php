<?php

namespace App\Modules\Auth;

use App\Core\Auth;
use App\Core\Database;
use App\Core\BaseController;
use PDO;


/**
 * Group Controller
 * 
 * Manages user groups for multi-tenant organization and permission
 * inheritance in the system.
 * 
 * Core Features:
 * - Group CRUD operations
 * - User count per group
 * - Permission assignment per group
 * - Module-based permission structure
 * - User isolation by group
 * 
 * Use Cases:
 * - Multi-tenant organization
 * - Department segregation
 * - Client isolation
 * - Team management
 * 
 * Permission Structure:
 * - {"modules": {"module:name.action": true, ...}}
 * 
 * Security:
 * - Permission-based access control
 * - User group isolation
 * - Automatic user cleanup on group deletion
 * 
 * @package App\Modules\Auth
 * @author DATA2REST Development Team
 * @version 1.0.0
 */
/**
 * GroupController Controller
 *
 * Core Features: TODO
 *
 * Security: Requires login, permission checks as implemented.
 *
 * @package App\Modules\
 * @author DATA2REST Development Team
 * @version 1.0.0
 */
class GroupController extends BaseController
{
    /**
     * Constructor - Requires user view permission
     * 
     * Ensures that only users with appropriate permissions
     * can access group management.
     */
/**
 * __construct method
 *
 * @return void
 */
    public function __construct()
    {
        Auth::requirePermission('module:users.view_users');
    }

    /**
     * Display list of groups
     * 
     * Shows all groups with user count for each group.
     * 
     * Features:
     * - User count per group
     * - Alphabetical sorting
     * 
     * @return void Renders group list view
     * 
     * @example
     * GET /admin/groups
     */
/**
 * index method
 *
 * @return void
 */
    public function index()
    {
        $db = Database::getInstance()->getConnection();
        $groups = $db->query("
            SELECT g.*, COUNT(u.id) as user_count 
            FROM groups g 
            LEFT JOIN users u ON u.group_id = g.id 
            GROUP BY g.id 
            ORDER BY g.name ASC
        ")->fetchAll();

        $this->view('admin/groups/index', [
            'groups' => $groups,
            'title' => 'User Groups',
            'breadcrumbs' => [
                \App\Core\Lang::get('common.team') => 'admin/users',
                \App\Core\Lang::get('common.groups') => null
            ]
        ]);
    }

    /**
     * Display group creation/edit form
     * 
     * Renders a form for creating new groups or editing existing ones
     * with permission assignment interface.
     * 
     * Features:
     * - Group name and description
     * - Module-based permission checkboxes
     * - JSON permission decoding for edit
     * 
     * @return void Renders group form view
     * 
     * @example
     * GET /admin/groups/form (new group)
     * GET /admin/groups/form?id=2 (edit group)
     */
/**
 * form method
 *
 * @return void
 */
    public function form()
    {
        Auth::requirePermission('module:users.manage_groups');
        $id = $_GET['id'] ?? null;
        $group = null;
        $db = Database::getInstance()->getConnection();

        if ($id) {
            $stmt = $db->prepare("SELECT * FROM groups WHERE id = ?");
            $stmt->execute([$id]);
            $group = $stmt->fetch();
            $group['permissions'] = json_decode($group['permissions'] ?? '[]', true);
        }

        $this->view('admin/groups/form', [
            'group' => $group,
            'id' => $id,
            'title' => ($id ? 'Edit' : 'New') . ' Group',
            'breadcrumbs' => [
                \App\Core\Lang::get('common.team') => 'admin/users',
                \App\Core\Lang::get('common.groups') => 'admin/groups',
                ($id ? 'Edit' : 'Create') . ' Group' => null
            ]
        ]);
    }

    /**
     * Save group data (create or update)
     * 
     * Processes form submission to create or update groups with
     * their permission configurations.
     * 
     * Permission Structure:
     * - {"modules": {"module:name.action": true, ...}}
     * 
     * @return void Redirects to group list on success
     * 
     * @example
     * POST /admin/groups/save
     * Body: name=Marketing&description=Team&modules[module:databases.view_tables]=on
     */
/**
 * save method
 *
 * @return void
 */
    public function save()
    {
        Auth::requirePermission('module:users.manage_groups');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            return;

        $db = Database::getInstance()->getConnection();
        $id = $_POST['id'] ?? null;
        $name = $_POST['name'];
        $description = $_POST['description'] ?? '';

        $permissions = [];
        $permissions['modules'] = $_POST['modules'] ?? [];
        $permsJson = json_encode($permissions);

        if ($id) {
            $stmt = $db->prepare("UPDATE groups SET name = ?, description = ?, permissions = ? WHERE id = ?");
            $stmt->execute([$name, $description, $permsJson, $id]);
        } else {
            $stmt = $db->prepare("INSERT INTO groups (name, description, permissions) VALUES (?, ?, ?)");
            $stmt->execute([$name, $description, $permsJson]);
        }

        $this->redirect('admin/groups');
    }

    /**
     * Delete a group
     * 
     * Removes a group from the system and clears group assignment
     * from all users in that group.
     * 
     * Features:
     * - Automatic user cleanup (sets group_id to NULL)
     * - Prevents orphaned user assignments
     * 
     * @return void Redirects to group list
     * 
     * @example
     * GET /admin/groups/delete?id=3
     */
/**
 * delete method
 *
 * @return void
 */
    public function delete()
    {
        Auth::requirePermission('module:users.manage_groups');
        $id = $_GET['id'] ?? null;
        if ($id) {
            $db = Database::getInstance()->getConnection();
            $db->prepare("DELETE FROM groups WHERE id = ?")->execute([$id]);
            // Optional: Set users in this group to null group_id
            $db->prepare("UPDATE users SET group_id = NULL WHERE group_id = ?")->execute([$id]);
        }
        $this->redirect('admin/groups');
    }
}
