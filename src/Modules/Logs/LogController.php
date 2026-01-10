<?php

namespace App\Modules\Logs;

use App\Core\Auth;
use App\Core\Database;
use App\Core\BaseController;
use PDO;

/**
 * Log Controller
 * Handles viewing and filtering system activity logs.
 */
class LogController extends BaseController
{
    public function __construct()
    {
        Auth::requireLogin();
    }

    /**
     * Lists activity logs.
     */
    public function index()
    {
        $db = Database::getInstance()->getConnection();
        $projectId = Auth::getActiveProject();

        // Base Query
        $sql = "SELECT l.*, u.username, u.group_id 
                FROM activity_logs l 
                LEFT JOIN users u ON l.user_id = u.id 
                WHERE 1=1";
        $params = [];

        // 1. Scope by Project
        if ($projectId) {
            $sql .= " AND l.project_id = ?";
            $params[] = $projectId;
        } else if (!Auth::isAdmin()) {
            // User outside of any project context should see nothing?
            // Or only their own global actions? 
            // Let's hide everything if no project selected for non-admin.
            $sql .= " AND 1=0";
        }

        // 2. Scope by Team/Group Visibility
        // Admin sees all.
        // Users see: Their own logs + Logs of others IN THE SAME GROUP.
        if (!Auth::isAdmin()) {
            $currentUserId = $_SESSION['user_id'];
            $currentUserGroupId = $_SESSION['group_id'] ?? null;

            if ($currentUserGroupId) {
                // See self OR members of same group
                // Note: We need to trust u.group_id from the join, but user might have changed group.
                // Ideally, logs should snapshot the group_id at time of event? 
                // schema check: activity_logs usually has user_id, project_id. 
                // We rely on current user group membership.

                // Get all users currently in my group
                $teamMembers = Auth::getTeamMembers();
                $teamMembers[] = $currentUserId;
                $placeholders = implode(',', array_fill(0, count($teamMembers), '?'));

                $sql .= " AND l.user_id IN ($placeholders)";
                $params = array_merge($params, $teamMembers);
            } else {
                // No group? See only self.
                $sql .= " AND l.user_id = ?";
                $params[] = $currentUserId;
            }
        }

        $sql .= " ORDER BY l.created_at DESC LIMIT 100";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        // Stats: API Usage count by Action/Endpoint
        $stats = [
            'api_calls' => 0,
            'data_changes' => 0,
            'top_endpoints' => []
        ];

        foreach ($logs as $log) {
            if (strpos($log['action'], 'API_') === 0) {
                $stats['api_calls']++;
            }
            if (in_array($log['action'], ['INSERT_RECORD', 'UPDATE_RECORD', 'DELETE_RECORD'])) {
                $stats['data_changes']++;
            }
        }

        // Top Endpoints (simplified from actions for now)
        $sqlTop = "SELECT action, COUNT(*) as count FROM activity_logs WHERE action LIKE 'API_%'";
        $topParams = [];
        if ($projectId) {
            $sqlTop .= " AND project_id = ?";
            $topParams[] = $projectId;
        }
        $sqlTop .= " GROUP BY action ORDER BY count DESC LIMIT 5";
        $stmtTop = $db->prepare($sqlTop);
        $stmtTop->execute($topParams);
        $stats['top_endpoints'] = $stmtTop->fetchAll();

        $this->view('admin/logs/index', [
            'logs' => $logs,
            'stats' => $stats,
            'projectId' => $projectId,
            'title' => 'Activity Logs',
            'breadcrumbs' => ['Activity' => null]
        ]);
    }

    /**
     * Logs an action (Helper to be called statically or internally).
     * Actually, logging usually happens in model/service or via a global Logger class.
     * This controller is for VIEWING.
     */
}
