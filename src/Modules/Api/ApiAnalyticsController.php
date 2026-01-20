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

        // Time filter (default 24h)
        $range = $_GET['range'] ?? '24h';
        $timeCondition = match ($range) {
            '1h' => "datetime(created_at) >= datetime('now', '-1 hour')",
            '7d' => "datetime(created_at) >= datetime('now', '-7 days')",
            '30d' => "datetime(created_at) >= datetime('now', '-30 days')",
            default => "datetime(created_at) >= datetime('now', '-24 hours')"
        };

        // 1. Total Requests
        $stmt = $db->query("SELECT COUNT(*) FROM api_access_logs WHERE $timeCondition");
        $totalRequests = $stmt->fetchColumn();

        // 2. Average Latency
        $stmt = $db->query("SELECT AVG(response_time_ms) FROM api_access_logs WHERE $timeCondition");
        $avgLatency = round($stmt->fetchColumn(), 2);

        // 3. Error Rate
        $stmt = $db->query("SELECT COUNT(*) FROM api_access_logs WHERE status_code >= 400 AND $timeCondition");
        $errorCount = $stmt->fetchColumn();
        $errorRate = $totalRequests > 0 ? round(($errorCount / $totalRequests) * 100, 2) : 0;

        // 4. Requests Over Time (Hourly)
        // Adjust group format based on range
        $dateFormat = ($range === '30d' || $range === '7d') ? '%Y-%m-%d' : '%Y-%m-%d %H:00';

        $usageSql = "SELECT strftime('$dateFormat', created_at) as time_slot, COUNT(*) as count 
                     FROM api_access_logs 
                     WHERE $timeCondition 
                     GROUP BY time_slot 
                     ORDER BY time_slot";
        $usageData = $db->query($usageSql)->fetchAll(PDO::FETCH_ASSOC);

        // 5. Top Endpoints
        $endpointsData = $db->query("SELECT endpoint, COUNT(*) as count 
                                     FROM api_access_logs 
                                     WHERE $timeCondition 
                                     GROUP BY endpoint 
                                     ORDER BY count DESC 
                                     LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

        // 6. Status Codes Distribution
        $statusData = $db->query("SELECT status_code, COUNT(*) as count 
                                  FROM api_access_logs 
                                  WHERE $timeCondition 
                                  GROUP BY status_code")->fetchAll(PDO::FETCH_ASSOC);

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
