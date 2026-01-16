<?php

namespace App\Modules\Auth;

use App\Core\Auth;
use App\Core\Database;
use App\Core\DatabaseManager;
use App\Core\Config;
use App\Core\BaseController;
use App\Core\Maintenance;
use PDO;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;


/**
 * Dashboard Controller
 * 
 * Main dashboard with comprehensive statistics, charts, and activity tracking
 * for project-scoped data visualization.
 * 
 * Core Features:
 * - Project-scoped statistics
 * - Real-time activity tracking
 * - Storage usage monitoring
 * - Interactive charts (activity, storage, growth)
 * - Recent activity feed
 * - Database record counting
 * - Automatic maintenance execution
 * 
 * Statistics Displayed:
 * - Total databases in project
 * - Total records across all tables
 * - Storage usage with quota tracking
 * - Recent activity (last 10 items)
 * 
 * Charts:
 * - Activity Chart: Last 7 days of system activity
 * - Storage Chart: Distribution by database
 * - Growth Chart: Record creation trend (7 days)
 * 
 * Access Control:
 * - Admin: View all projects or global stats
 * - User: View only assigned project data
 * - Automatic project selection enforcement
 * 
 * @package App\Modules\Auth
 * @author DATA2REST Development Team
 * @version 1.0.0
 */
/**
 * DashboardController Controller
 *
 * Core Features: TODO
 *
 * Security: Requires login, permission checks as implemented.
 *
 * @package App\Modules\
 * @author DATA2REST Development Team
 * @version 1.0.0
 */
class DashboardController extends BaseController
{
    /**
     * Display main dashboard
     * 
     * Renders the main dashboard with comprehensive statistics,
     * charts, and activity tracking for the active project.
     * 
     * Features:
     * - Project-scoped data isolation
     * - Real-time statistics calculation
     * - Activity tracking from multiple sources
     * - Chart data preparation (7-day trends)
     * - Storage quota monitoring
     * - Automatic maintenance execution
     * 
     * Data Sources:
     * - Project databases (record counts)
     * - data_versions table (audit trail)
     * - activity_logs table (system activity)
     * - File system (storage usage)
     * 
     * @return void Renders dashboard view
     * 
     * @example
     * GET /admin/dashboard
     */
    /**
     * index method
     *
     * @return void
     */
    public function index()
    {
        Auth::requireLogin();
        Maintenance::run(); // Self-throttled cleanup procedure

        $db = Database::getInstance()->getConnection();
        $projectId = Auth::getActiveProject();

        // 0. Force project selection for non-admins if none active
        if (!$projectId && !Auth::isAdmin()) {
            $this->redirect('admin/projects/select');
        }

        // 1. Fetch Databases filtered by Project
        if (Auth::isAdmin() && !$projectId) {
            $stmt = $db->query("SELECT * FROM databases");
            $databases = $stmt->fetchAll();
        } else {
            if (!$projectId) {
                $databases = [];
            } else {
                $stmt = $db->prepare("SELECT * FROM databases WHERE project_id = ?");
                $stmt->execute([$projectId]);
                $databases = $stmt->fetchAll();
            }
        }

        // 2. Fetch Project Metadata
        $projectInfo = null;
        if ($projectId) {
            $stmt = $db->prepare("SELECT p.*, pp.plan_type, pp.next_billing_date 
                                 FROM projects p 
                                 LEFT JOIN project_plans pp ON p.id = pp.project_id 
                                 WHERE p.id = ?");
            $stmt->execute([$projectId]);
            $projectInfo = $stmt->fetch();
        }

        $totalRecords = 0;
        $recentActivity = [];
        $totalStorage = 0;

        // 3. Calculate record counts and activity (isolated to project dbs)
        foreach ($databases as $database) {
            try {
                // Get adapter for the specific database
                $adapter = DatabaseManager::getAdapter($database);

                // Ensure connection works
                if (!$adapter->getConnection())
                    continue;

                $tables = $adapter->getTables();
                $quotedDbName = $database['name'];

                foreach ($tables as $table) {
                    // Skip internal tables if any (though getTables should handle this)
                    if (str_starts_with($table, 'sqlite_'))
                        continue;

                    $quotedTable = $adapter->quoteName($table);

                    // Count records
                    try {
                        $count = $adapter->getConnection()->query("SELECT COUNT(*) FROM $quotedTable")->fetchColumn();
                        $totalRecords += $count;
                    } catch (\Exception $e) {
                        // Table might not exist or other error, skip
                        continue;
                    }

                    // Try to get last edits
                    // We assume tables might have fecha_edicion or fecha_de_creacion
                    try {
                        // Determine date column for sorting if possible, default to generic or fail
                        // We construct a query that tries to fetch standard columns
                        // Using raw query as we need specific fields and ordering
                        // LIMIT is standard across supported DBs

                        $sql = "SELECT *, '$table' as table_source, '$quotedDbName' as db_source FROM $quotedTable ORDER BY fecha_edicion DESC LIMIT 2";
                        $lastEdits = $adapter->getConnection()->query($sql)->fetchAll();

                        foreach ($lastEdits as $edit) {
                            $recentActivity[] = [
                                'table' => $table,
                                'db' => $database['name'],
                                'id' => $edit['id'] ?? null,
                                'date' => $edit['fecha_edicion'] ?? $edit['fecha_de_creacion'] ?? 'Unknown',
                                'label' => $edit['nombre'] ?? $edit['name'] ?? $edit['title'] ?? $edit['id'] ?? 'Unknown'
                            ];
                        }
                    } catch (\Exception $e) {
                        // Table probably doesn't have fecha_edicion, ignore
                    }
                }
            } catch (\Exception $e) {
                // Database connection failed or other issue
                continue;
            }
        }

        // 3b. Fetch Activity from data_versions (Deletions/Updates)
        try {
            $stmtVers = $db->prepare("SELECT v.*, d.name as db_name FROM data_versions v 
                                    JOIN databases d ON v.database_id = d.id 
                                    WHERE d.project_id = ? 
                                    ORDER BY v.created_at DESC LIMIT 10");
            $stmtVers->execute([$projectId]);
            $versions = $stmtVers->fetchAll();

            foreach ($versions as $v) {
                $details = json_decode((string) ($v['old_data'] ?? $v['new_data'] ?? '{}'), true);
                $recentActivity[] = [
                    'table' => $v['table_name'],
                    'db' => $v['db_name'],
                    'db_id' => $v['database_id'],
                    'id' => $v['record_id'],
                    'date' => $v['created_at'],
                    'action' => $v['action'], // DELETE or UPDATE
                    'label' => ($v['action'] === 'DELETE' ? 'Deleted: ' : 'Updated: ') . ($details['nombre'] ?? $details['name'] ?? $details['title'] ?? '#' . $v['record_id'])
                ];
            }
        } catch (\Exception $e) {
        }

        // Sort activity
        usort($recentActivity, function ($a, $b) {
            return strcmp($b['date'] ?? '', $a['date'] ?? '');
        });
        $recentActivity = array_slice($recentActivity, 0, 10);

        // 4. Calculate storage size scoped to project
        $storageInfo = $this->getProjectStorageInfo();
        $totalStorage = $storageInfo ? $storageInfo['used_bytes'] : 0;

        // If no project active, we can show total system storage as fallback for admin
        if (!$projectId && Auth::isAdmin()) {
            $uploadDir = Config::get('upload_dir');
            if (is_dir($uploadDir)) {
                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploadDir));
                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $totalStorage += $file->getSize();
                    }
                }
            }
        }

        // 5. Global Stats and Settings for Admin Banner
        $globalDbCount = 0;
        $showWelcomeBanner = 0;

        if (Auth::isAdmin()) {
            $stmt = $db->query("SELECT COUNT(*) FROM databases");
            $globalDbCount = $stmt->fetchColumn();

            $stmt = $db->prepare("SELECT value FROM system_settings WHERE key = 'show_welcome_banner'");
            $stmt->execute();
            $val = $stmt->fetchColumn();
            $showWelcomeBanner = ($val === false) ? 1 : (int) $val;
        }

        // 6. Chart Data Preparation
        $chartData = [
            'activity' => ['labels' => [], 'data' => []],
            'storage' => ['labels' => [], 'data' => []],
            'growth' => ['labels' => [], 'data' => []]
        ];

        // 6a. Activity (System logs - Last 7 days)
        // Note: activity_logs is in the system DB (SQLite usually), so generic SQL is fine if system DB is SQLite.
        // If system DB is migrated to MySQL/PgSQL, this query might need adjustment (date function), 
        // but currently system DB is likely SQLite based on codebase context. 
        // We will assume system DB is SQLite for now or uses compatible syntax.
        $stmt = $db->prepare("SELECT date(created_at) as day, COUNT(*) as count FROM activity_logs WHERE created_at >= date('now', '-6 days') GROUP BY day ORDER BY day ASC");
        $stmt->execute();
        $activityDays = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $activityDays[$row['day']] = (int) $row['count'];
        }

        // 6b. Storage Distribution
        foreach ($databases as $database) {
            try {
                $adapter = DatabaseManager::getAdapter($database);
                $size = $adapter->getDatabaseSize();
                // Only show if size > 0
                if ($size > 0) {
                    $chartData['storage']['labels'][] = $database['name'];
                    $chartData['storage']['data'][] = round($size / 1024 / 1024, 2);
                }
            } catch (\Exception $e) {
                // Ignore errors
            }
        }

        // 6c. Record Growth & Activity Fill
        $growthDays = [];
        for ($i = 6; $i >= 0; $i--) {
            // Logic for growth dates keys
            $day = date('Y-m-d', strtotime("-$i days"));
            $chartData['activity']['labels'][] = date('D', strtotime($day));
            $chartData['activity']['data'][] = $activityDays[$day] ?? 0;

            $chartData['growth']['labels'][] = date('D', strtotime($day));
            $growthDays[$day] = 0;
        }

        foreach ($databases as $database) {
            try {
                $adapter = DatabaseManager::getAdapter($database);
                $type = $adapter->getType();
                $tables = $adapter->getTables();

                // Determine Date Logic based on DB Type
                $dateColExp = "date(fecha_de_creacion)"; // Default (SQLite)
                $whereDate = "date('now', '-6 days')";   // Default (SQLite)

                if ($type === 'mysql') {
                    $dateColExp = "DATE(fecha_de_creacion)";
                    $whereDate = "DATE_SUB(NOW(), INTERVAL 6 DAY)";
                } elseif ($type === 'pgsql' || $type === 'postgresql') {
                    $dateColExp = "fecha_de_creacion::date";
                    $whereDate = "CURRENT_DATE - INTERVAL '6 days'";
                }

                foreach ($tables as $table) {
                    if (str_starts_with($table, 'sqlite_'))
                        continue;

                    try {
                        // Check if column exists by selecting one row
                        $adapter->getConnection()->query("SELECT fecha_de_creacion FROM " . $adapter->quoteName($table) . " LIMIT 1");

                        // If success, run growth query
                        $growthSql = "SELECT $dateColExp as day, COUNT(*) as count FROM " . $adapter->quoteName($table) . " WHERE fecha_de_creacion >= $whereDate GROUP BY day";

                        $growthStmt = $adapter->getConnection()->query($growthSql);
                        while ($grow = $growthStmt->fetch(PDO::FETCH_ASSOC)) {
                            // Normalize Day format just in case
                            // DB might return full timestamp or date string
                            $d = substr($grow['day'], 0, 10);
                            if (isset($growthDays[$d])) {
                                $growthDays[$d] += (int) $grow['count'];
                            }
                        }

                    } catch (\Exception $e) {
                        // Column doesn't exist or other error
                    }
                }
            } catch (\Exception $e) {
            }
        }
        $chartData['growth']['data'] = array_values($growthDays);

        $this->view('admin/dashboard', [
            'title' => 'Dashboard - ' . ($projectInfo['name'] ?? 'Data2Rest'),
            'project' => $projectInfo,
            'globalDbCount' => $globalDbCount,
            'showWelcomeBanner' => $showWelcomeBanner,
            'chartData' => $chartData,
            'stats' => [
                'total_databases' => count($databases),
                'total_records' => $totalRecords,
                'storage_usage' => $this->formatBytes($totalStorage),
                'storage_info' => $storageInfo,
                'recent_activity' => $recentActivity
            ]
        ]);
    }

    /**
     * Format bytes to human-readable string
     * 
     * Converts byte count to appropriate unit (B, KB, MB, GB, TB).
     * 
     * @param int $bytes Byte count
     * @param int $precision Decimal precision
     * @return string Formatted string (e.g., "15.5 MB")
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
