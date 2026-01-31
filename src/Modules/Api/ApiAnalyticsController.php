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

        // 3. Status Breakdown
        $stmt = $db->prepare("SELECT 
            SUM(CASE WHEN status_code >= 200 AND status_code < 300 THEN 1 ELSE 0 END) as success,
            SUM(CASE WHEN status_code IN (401, 403, 429) THEN 1 ELSE 0 END) as denied,
            SUM(CASE WHEN status_code >= 400 AND status_code < 500 AND status_code NOT IN (401, 403, 429) THEN 1 ELSE 0 END) as client_error,
            SUM(CASE WHEN status_code >= 500 THEN 1 ELSE 0 END) as server_error
            FROM api_access_logs WHERE created_at >= ?");
        $stmt->execute([$startDate]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        $successCount = $stats['success'] ?? 0;
        $deniedCount = $stats['denied'] ?? 0;
        $serverErrorCount = $stats['server_error'] ?? 0;
        $clientErrorCount = $stats['client_error'] ?? 0;
        $totalErrors = $deniedCount + $serverErrorCount + $clientErrorCount;

        $errorRate = $totalRequests > 0 ? round(($totalErrors / $totalRequests) * 100, 2) : 0;

        // 4. Requests Over Time (Hourly) - Split by Success vs Error
        // Use adapter-specific DATE formatting for aggregation
        $dateFormatSql = ($range === '30d' || $range === '7d') ? 'Y-m-d' : 'Y-m-d H:00';
        $groupBySql = $adapter->getDateFormatSQL('created_at', $dateFormatSql);

        $usageSql = "SELECT $groupBySql as time_slot, 
                     SUM(CASE WHEN status_code >= 200 AND status_code < 300 THEN 1 ELSE 0 END) as success_count,
                     SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as error_count
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
                'error_rate' => $errorRate,
                'denied_count' => $deniedCount,
                'server_error_count' => $serverErrorCount,
                'success_count' => $successCount
            ],
            'usage_data' => $usageData,
            'endpoints_data' => $endpointsData,
            'status_data' => $statusData
        ]);
    }
}
