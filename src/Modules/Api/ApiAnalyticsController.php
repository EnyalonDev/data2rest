<?php

namespace App\Modules\Api;

use App\Core\Auth;
use App\Core\BaseController;
use App\Core\Database;
use PDO;

/**
 * API Analytics Dashboard Controller
 * 
 * Visualizes API usage metrics, latency, and errors.
 * 
 * @package App\Modules\Api
 * @version 1.0.0
 */
class ApiAnalyticsController extends BaseController
{
    public function __construct()
    {
        Auth::requireLogin();
        Auth::requirePermission('module:api.view_analytics');
    }

    public function index()
    {
        $db = Database::getInstance()->getConnection();
        $adapter = Database::getInstance()->getAdapter();
        $adapterType = $adapter->getType();

        // Time filter (default 24h)
        $range = $_GET['range'] ?? '24h';

        $startDate = match ($range) {
            '1h' => date('Y-m-d H:i:s', strtotime('-1 hour')),
            '7d' => date('Y-m-d H:i:s', strtotime('-7 days')),
            '30d' => date('Y-m-d H:i:s', strtotime('-30 days')),
            default => date('Y-m-d H:i:s', strtotime('-24 hours'))
        };

        // 1. Total Requests
        $stmt = $db->prepare("SELECT COUNT(*) FROM api_access_logs WHERE created_at >= ?");
        $stmt->execute([$startDate]);
        $totalRequests = $stmt->fetchColumn();

        // 2. Average Latency
        $stmt = $db->prepare("SELECT AVG(response_time_ms) FROM api_access_logs WHERE created_at >= ?");
        $stmt->execute([$startDate]);
        $val = $stmt->fetchColumn();
        $avgLatency = round($val ?: 0, 2);

        // 3. Error Rate
        $stmt = $db->prepare("SELECT COUNT(*) FROM api_access_logs WHERE status_code >= 400 AND created_at >= ?");
        $stmt->execute([$startDate]);
        $errorCount = $stmt->fetchColumn();
        $errorRate = $totalRequests > 0 ? round(($errorCount / $totalRequests) * 100, 2) : 0;

        // 4. Requests Over Time (Hourly)
        // Group format: date-agnostic logic
        $timeFormat = ($range === '30d' || $range === '7d') ? 'Y-m-d' : 'Y-m-d H:00';

        // Use adapter-specific DATE formatting for aggregation
        if ($adapterType === 'sqlite') {
            $dateFormatSql = ($range === '30d' || $range === '7d') ? '%Y-%m-%d' : '%Y-%m-%d %H:00';
            $groupBySql = "strftime('$dateFormatSql', created_at)";
        } elseif ($adapterType === 'mysql') {
            $dateFormatSql = ($range === '30d' || $range === '7d') ? '%Y-%m-%d' : '%Y-%m-%d %H:00'; // MySQL uses different specifiers usually but strftime is SQLite. MySQL is DATE_FORMAT
            // MySQL DATE_FORMAT: %Y-%m-%d, %Y-%m-%d %H:00
            $mysqlFormat = ($range === '30d' || $range === '7d') ? '%Y-%m-%d' : '%Y-%m-%d %H:00';
            $groupBySql = "DATE_FORMAT(created_at, '$mysqlFormat')";
        } elseif ($adapterType === 'pgsql' || $adapterType === 'postgresql') {
            $pgFormat = ($range === '30d' || $range === '7d') ? 'YYYY-MM-DD' : 'YYYY-MM-DD HH24:00';
            $groupBySql = "TO_CHAR(created_at, '$pgFormat')";
        } else {
            // Fallback or error? defaulting to sqlite syntax as existing code did
            $dateFormatSql = ($range === '30d' || $range === '7d') ? '%Y-%m-%d' : '%Y-%m-%d %H:00';
            $groupBySql = "strftime('$dateFormatSql', created_at)";
        }

        $usageSql = "SELECT $groupBySql as time_slot, COUNT(*) as count 
                     FROM api_access_logs 
                     WHERE created_at >= ? 
                     GROUP BY time_slot 
                     ORDER BY time_slot";

        $stmt = $db->prepare($usageSql);
        $stmt->execute([$startDate]);
        $usageData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 5. Top Endpoints
        $stmt = $db->prepare("SELECT endpoint, COUNT(*) as count 
                                     FROM api_access_logs 
                                     WHERE created_at >= ? 
                                     GROUP BY endpoint 
                                     ORDER BY count DESC 
                                     LIMIT 10");
        $stmt->execute([$startDate]);
        $endpointsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 6. Status Codes Distribution
        $stmt = $db->prepare("SELECT status_code, COUNT(*) as count 
                                  FROM api_access_logs 
                                  WHERE created_at >= ? 
                                  GROUP BY status_code");
        $stmt->execute([$startDate]);
        $statusData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/api/analytics', [
            'title' => 'API Analytics',
            'range' => $range,
            'summary' => [
                'total_requests' => $totalRequests,
                'avg_latency' => $avgLatency,
                'error_rate' => $errorRate
            ],
            'usage_data' => $usageData,
            'endpoints_data' => $endpointsData,
            'status_data' => $statusData
        ]);
    }
}
