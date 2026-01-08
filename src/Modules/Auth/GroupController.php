<?php

namespace App\Modules\Auth;

use App\Core\Auth;
use App\Core\Database;
use App\Core\BaseController;
use PDO;

class GroupController extends BaseController
{
    public function __construct()
    {
        Auth::requireAdmin();
    }

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

    public function form()
    {
        $id = $_GET['id'] ?? null;
        $group = null;
        $db = Database::getInstance()->getConnection();

        if ($id) {
            $stmt = $db->prepare("SELECT * FROM groups WHERE id = ?");
            $stmt->execute([$id]);
            $group = $stmt->fetch();
            $group['permissions'] = json_decode($group['permissions'] ?? '[]', true);
        }

        $databases = $db->query("SELECT * FROM databases ORDER BY name ASC")->fetchAll();

        $this->view('admin/groups/form', [
            'group' => $group,
            'databases' => $databases,
            'id' => $id,
            'title' => ($id ? 'Edit' : 'New') . ' Group',
            'breadcrumbs' => [
                \App\Core\Lang::get('common.team') => 'admin/users',
                \App\Core\Lang::get('common.groups') => 'admin/groups',
                ($id ? 'Edit' : 'Create') . ' Group' => null
            ]
        ]);
    }

    public function save()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            return;

        $db = Database::getInstance()->getConnection();
        $id = $_POST['id'] ?? null;
        $name = $_POST['name'];
        $description = $_POST['description'] ?? '';

        $permissions = [];
        $permissions['modules'] = $_POST['modules'] ?? [];
        $permissions['databases'] = $_POST['db_perms'] ?? [];
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

    public function delete()
    {
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
