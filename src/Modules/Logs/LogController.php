<?php

namespace App\Modules\Logs;

use App\Core\Auth;
use App\Core\Database;
use App\Core\BaseController;
use PDO;

/**
 * Log Controller
 *
 * Provides comprehensive management and viewing of system activity logs.
 *
 * Core Features:
 * - Filter logs by user, action, date range, and search term
 * - Project-scoped visibility with admin overrides
 * - Team/group based access control
 * - Statistics on API calls, data changes, and top endpoints
 * - Pagination limited to recent 200 entries
 *
 * Security:
 * - Requires authenticated user (Auth::requireLogin())
 * - Permission checks for project access and admin rights
 * - Prevents unauthorized log exposure via team visibility rules
 *
 * @package App\Modules\Logs
 * @author DATA2REST Development Team
 * @version 1.0.0
 */
/**
 * LogController Controller
 *
 * Core Features: TODO
 *
 * Security: Requires login, permission checks as implemented.
 *
 * @package App\Modules\
 * @author DATA2REST Development Team
 * @version 1.0.0
 */
class LogController extends BaseController
{
/**
 * __construct method
 *
 * @return void
 */
    public function __construct()
    {
        Auth::requireLogin();
    }

    /**
     * Lists activity logs.
     *
     * Retrieves and displays activity logs with optional filtering:
     * - User ID (`user_id`)
     * - Action type (`action_type`)
     * - Date range (`start_date`, `end_date`)
     * - Full-text search (`s`)
     *
     * Generates additional data for the view:
     * - Available users for filter dropdowns
     * - Distinct action types
     * - Statistics: API call count, data change count, top endpoints
     *
     * @return void Renders the `admin/logs/index` view with logs and metadata.
     * @example GET /admin/logs?user_id=5&action_type=API_GET&start_date=2024-01-01&end_date=2024-12-31&s=login
     */
/**
 * index method
 *
 * @return void
 */
    public function index()
    {
        $db = Database::getInstance()->getConnection();
        $projectId = Auth::getActiveProject();

        // Get filter inputs
        $filterUser = $_GET['user_id'] ?? null;
        $filterAction = $_GET['action_type'] ?? null;
        $filterStart = $_GET['start_date'] ?? null;
        $filterEnd = $_GET['end_date'] ?? null;
        $search = $_GET['s'] ?? '';

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
            $sql .= " AND 1=0";
        }

        // 2. Scope by Team/Group Visibility
        if (!Auth::isAdmin()) {
            $currentUserId = $_SESSION['user_id'];
            $currentUserGroupId = $_SESSION['group_id'] ?? null;

            if ($currentUserGroupId) {
                $teamMembers = Auth::getTeamMembers();
                $teamMembers[] = $currentUserId;
                $placeholders = implode(',', array_fill(0, count($teamMembers), '?'));
                $sql .= " AND l.user_id IN ($placeholders)";
                $params = array_merge($params, $teamMembers);
            } else {
                $sql .= " AND l.user_id = ?";
                $params[] = $currentUserId;
            }
        }

        // 3. Apply Advanced Filters
        if ($filterUser) {
            $sql .= " AND l.user_id = ?";
            $params[] = $filterUser;
        }
        if ($filterAction) {
            $sql .= " AND l.action = ?";
            $params[] = $filterAction;
        }
        if ($filterStart) {
            $sql .= " AND date(l.created_at) >= ?";
            $params[] = $filterStart;
        }
        if ($filterEnd) {
            $sql .= " AND date(l.created_at) <= ?";
            $params[] = $filterEnd;
        }
        if (!empty($search)) {
            $sql .= " AND l.details LIKE ?";
            $params[] = "%$search%";
        }

        $sql .= " ORDER BY l.created_at DESC LIMIT 200";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        // 4. Fetch Users for Filter
        $users = [];
        if (Auth::isAdmin() && !$projectId) {
            $stmtU = $db->query("SELECT id, username FROM users ORDER BY username ASC");
            $users = $stmtU->fetchAll();
        } else if ($projectId) {
            // Get users with activity in this project
            $stmtU = $db->prepare("SELECT DISTINCT u.id, u.username FROM users u JOIN activity_logs l ON l.user_id = u.id WHERE l.project_id = ? ORDER BY u.username ASC");
            $stmtU->execute([$projectId]);
            $users = $stmtU->fetchAll();
        }

        // 5. Fetch Unique Actions for Filter
        $sqlActions = "SELECT DISTINCT action FROM activity_logs WHERE 1=1";
        $actParams = [];
        if ($projectId) {
            $sqlActions .= " AND project_id = ?";
            $actParams[] = $projectId;
        }
        $stmtA = $db->prepare($sqlActions . " ORDER BY action ASC");
        $stmtA->execute($actParams);
        $actions = $stmtA->fetchAll(PDO::FETCH_COLUMN);

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

        // Top Endpoints
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
            'users' => $users,
            'actions' => $actions,
            'filters' => [
                'user_id' => $filterUser,
                'action_type' => $filterAction,
                'start_date' => $filterStart,
                'end_date' => $filterEnd,
                's' => $search
            ],
            'projectId' => $projectId,
            'title' => 'Audit Activity Logs',
            'breadcrumbs' => ['Activity' => null]
        ]);
    }

    /**
     * Logs an action (Helper to be called statically or internally).
     * Actually, logging usually happens in model/service or via a global Logger class.
     * This controller is for VIEWING.
     */
}
