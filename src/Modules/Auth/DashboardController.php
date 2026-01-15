<?php

namespace App\Modules\Auth;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Config;
use App\Core\BaseController;
use App\Core\Maintenance;
use PDO;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class DashboardController extends BaseController
{
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
                if (!file_exists($database['path']))
                    continue;
                $targetDb = new PDO('sqlite:' . $database['path']);
                $targetDb->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

                $tables = $targetDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);

                foreach ($tables as $table) {
                    $count = $targetDb->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                    $totalRecords += $count;

                    // Try to get last edits
                    try {
                        $lastEdits = $targetDb->query("SELECT *, '$table' as table_source, '{$database['name']}' as db_source FROM $table ORDER BY fecha_edicion DESC LIMIT 2")->fetchAll();
                        foreach ($lastEdits as $edit) {
                            $recentActivity[] = [
                                'table' => $table,
                                'db' => $database['name'],
                                'id' => $edit['id'],
                                'date' => $edit['fecha_edicion'] ?? $edit['fecha_de_creacion'] ?? 'Unknown',
                                'label' => $edit['nombre'] ?? $edit['name'] ?? $edit['title'] ?? $edit['id']
                            ];
                        }
                    } catch (\Exception $e) {
                    }
                }
            } catch (\Exception $e) {
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
        $stmt = $db->prepare("SELECT date(created_at) as day, COUNT(*) as count FROM activity_logs WHERE created_at >= date('now', '-6 days') GROUP BY day ORDER BY day ASC");
        $stmt->execute();
        $activityDays = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $activityDays[$row['day']] = (int) $row['count'];
        }

        // 6b. Storage Distribution
        foreach ($databases as $database) {
            if (file_exists($database['path'])) {
                $chartData['storage']['labels'][] = $database['name'];
                $chartData['storage']['data'][] = round(filesize($database['path']) / 1024 / 1024, 2);
            }
        }

        // 6c. Record Growth & Activity Fill
        $growthDays = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-$i days"));
            $chartData['activity']['labels'][] = date('D', strtotime($day));
            $chartData['activity']['data'][] = $activityDays[$day] ?? 0;

            $chartData['growth']['labels'][] = date('D', strtotime($day));
            $growthDays[$day] = 0;
        }

        foreach ($databases as $database) {
            try {
                if (!file_exists($database['path']))
                    continue;
                $targetDb = new PDO('sqlite:' . $database['path']);
                $targetDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $tables = $targetDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($tables as $table) {
                    $cols = $targetDb->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_COLUMN, 1);
                    if (in_array('fecha_de_creacion', $cols)) {
                        $growthStmt = $targetDb->prepare("SELECT date(fecha_de_creacion) as day, COUNT(*) as count FROM $table WHERE fecha_de_creacion >= date('now', '-6 days') GROUP BY day");
                        $growthStmt->execute();
                        while ($grow = $growthStmt->fetch(PDO::FETCH_ASSOC)) {
                            if (isset($growthDays[$grow['day']])) {
                                $growthDays[$grow['day']] += (int) $grow['count'];
                            }
                        }
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
