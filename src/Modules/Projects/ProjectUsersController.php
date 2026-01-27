<?php

namespace App\Modules\Projects;

use App\Core\BaseController;
use App\Core\Database;
use App\Core\Auth;

class ProjectUsersController extends BaseController
{
    /**
     * Lista usuarios del proyecto con acceso externo
     */
    public function listExternalUsers($projectId)
    {
        if (!Auth::isAdmin()) {
            return $this->redirect('admin/dashboard');
        }

        $db = Database::getInstance()->getConnection();

        // Usuarios activos
        $stmtActive = $db->prepare("
            SELECT u.id, u.username, u.email, pu.external_permissions, pu.external_access_enabled
            FROM users u
            JOIN project_users pu ON u.id = pu.user_id
            WHERE pu.project_id = ? AND pu.external_access_enabled = 1
            ORDER BY u.username
        ");
        $stmtActive->execute([$projectId]);
        $activeUsers = $stmtActive->fetchAll();

        // Usuarios pendientes
        $stmtPending = $db->prepare("
            SELECT u.id, u.username, u.email
            FROM users u
            JOIN project_users pu ON u.id = pu.user_id
            WHERE pu.project_id = ? AND pu.external_access_enabled = 0
            ORDER BY pu.assigned_at DESC
        ");
        $stmtPending->execute([$projectId]);
        $pendingUsers = $stmtPending->fetchAll();

        // Obtener info del proyecto
        $stmtProj = $db->prepare("SELECT * FROM projects WHERE id = ?");
        $stmtProj->execute([$projectId]);
        $project = $stmtProj->fetch();

        return $this->view('admin.projects.external_users', [
            'project' => $project,
            'activeUsers' => $activeUsers,
            'pendingUsers' => $pendingUsers
        ]);
    }

    /**
     * Actualizar permisos externos de un usuario
     */
    public function updateExternalPermissions()
    {
        if (!Auth::isAdmin()) {
            return $this->json(['error' => 'No autorizado'], 403);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $projectId = $data['project_id'];
        $userId = $data['user_id'];
        $enabled = $data['enabled'] ?? 1;
        $role = $data['role'] ?? 'client';
        $pages = $data['pages'] ?? [];
        $dataAccess = $data['data_access'] ?? 'own';
        $actions = $data['actions'] ?? [];

        // Construir JSON de permisos
        $permissions = [
            'role' => $role,
            'pages' => $pages,
            'data_access' => [
                'scope' => $dataAccess,
                'filters' => $this->buildFilters($dataAccess, $userId)
            ],
            'actions' => $actions
        ];

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            UPDATE project_users SET
                external_permissions = ?,
                external_access_enabled = ?
            WHERE project_id = ? AND user_id = ?
        ");
        $stmt->execute([
            json_encode($permissions),
            $enabled,
            $projectId,
            $userId
        ]);

        // Activar usuario si estaba inactivo y se habilita
        if ($enabled) {
            $db->prepare("UPDATE users SET status = 1 WHERE id = ?")->execute([$userId]);
        }

        return $this->json(['success' => true]);
    }

    /**
     * Construir filtros según alcance de datos
     */
    private function buildFilters($scope, $userId)
    {
        if ($scope === 'all') {
            return [];
        }

        // Filtros para scope "own"
        return [
            'pets' => "owner_id = $userId",
            'appointments' => "client_id = $userId",
            'medical_records' => "pet.owner_id = $userId"
        ];
    }

    /**
     * Buscar usuarios para agregar al proyecto
     */
    public function searchUsers()
    {
        $query = $_GET['q'] ?? '';
        $projectId = $_GET['project_id'] ?? 0;

        $db = Database::getInstance()->getConnection();

        // Buscar usuarios que NO estén en el proyecto
        $stmt = $db->prepare("
            SELECT u.id, u.username, u.email
            FROM users u
            WHERE (u.email LIKE ? OR u.username LIKE ?)
              AND (u.role_id = 4 OR u.role_id IS NULL) -- Solo usuarios normales
              AND u.id NOT IN (
                SELECT user_id FROM project_users WHERE project_id = ?
              )
            LIMIT 10
        ");
        $stmt->execute(["%$query%", "%$query%", $projectId]);
        $users = $stmt->fetchAll();

        return $this->json(['users' => $users]);
    }

    /**
     * Agregar usuario existente al proyecto
     */
    public function addUserToProject()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $projectId = $data['project_id'];
        $userId = $data['user_id'];

        $db = Database::getInstance()->getConnection();

        // Verificar si ya existe
        $check = $db->prepare("SELECT count(*) FROM project_users WHERE project_id = ? AND user_id = ?");
        $check->execute([$projectId, $userId]);
        if ($check->fetchColumn() > 0) {
            return $this->json(['error' => 'User already in project'], 400);
        }

        $stmt = $db->prepare("
            INSERT INTO project_users (project_id, user_id, external_access_enabled, assigned_at)
            VALUES (?, ?, 0, datetime('now'))
        ");
        $stmt->execute([$projectId, $userId]);

        return $this->json(['success' => true]);
    }
}
