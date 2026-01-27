<?php

namespace App\Modules\Projects;

use App\Core\BaseController;
use App\Core\Database;
use App\Core\Auth;

class ProjectLogsController extends BaseController
{
    /**
     * Vista de logs del proyecto
     */
    public function index($projectId)
    {
        if (!Auth::isAdmin()) {
            return $this->redirect('admin/dashboard');
        }

        $db = Database::getInstance()->getConnection();

        // Filtros
        $userId = $_GET['user_id'] ?? null;
        $action = $_GET['action'] ?? null;
        $dateFrom = $_GET['date_from'] ?? null;
        $search = $_GET['search'] ?? null;

        // Query base
        $sql = "
            SELECT al.*, u.username, u.email
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.project_id = ?
        ";

        $params = [$projectId];

        // Aplicar filtros
        if ($userId) {
            $sql .= " AND al.user_id = ?";
            $params[] = $userId;
        }

        if ($action) {
            $sql .= " AND al.action = ?";
            $params[] = $action;
        }

        if ($dateFrom) {
            $sql .= " AND DATE(al.created_at) >= ?";
            $params[] = $dateFrom;
        }

        if ($search) {
            $sql .= " AND (al.details LIKE ? OR al.action LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " ORDER BY al.created_at DESC LIMIT 50";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        // Obtener proyecto
        $stmtProj = $db->prepare("SELECT * FROM projects WHERE id = ?");
        $stmtProj->execute([$projectId]);
        $project = $stmtProj->fetch();

        // Obtener usuarios únicos para filtro
        $users = $db->query("
            SELECT DISTINCT u.id, u.username
            FROM users u
            JOIN activity_logs al ON u.id = al.user_id
            WHERE al.project_id = $projectId
            ORDER BY u.username
        ")->fetchAll();

        // Obtener acciones únicas
        $actions = $db->query("
            SELECT DISTINCT action
            FROM activity_logs
            WHERE project_id = $projectId
            ORDER BY action
        ")->fetchAll();

        return $this->view('admin.projects.logs', [
            'project' => $project,
            'logs' => $logs,
            'users' => $users,
            'actions' => $actions,
            'filters' => [
                'user_id' => $userId,
                'action' => $action,
                'date_from' => $dateFrom,
                'search' => $search
            ]
        ]);
    }

    /**
     * Exportar logs a CSV
     */
    public function exportCsv($projectId)
    {
        if (!Auth::isAdmin()) {
            die('Unauthorized');
        }

        $db = Database::getInstance()->getConnection();

        // Misma lógica de filtros simplificada
        $sql = "SELECT * FROM activity_logs WHERE project_id = ? ORDER BY created_at DESC LIMIT 1000";
        $stmt = $db->prepare($sql);
        $stmt->execute([$projectId]);
        $logs = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="logs-project-' . $projectId . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'User ID', 'Action', 'Details', 'IP', 'Date']);

        foreach ($logs as $row) {
            fputcsv($output, $row);
        }
    }
}
