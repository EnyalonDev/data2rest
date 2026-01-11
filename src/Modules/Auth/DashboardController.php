<?php

namespace App\Modules\Auth;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Config;
use App\Core\BaseController;
use PDO;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class DashboardController extends BaseController
{
    public function index()
    {
        Auth::requireLogin();

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

        // Sort activity
        usort($recentActivity, function ($a, $b) {
            return strcmp($b['date'] ?? '', $a['date'] ?? '');
        });
        $recentActivity = array_slice($recentActivity, 0, 8);

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

        $this->view('admin/dashboard', [
            'title' => 'Dashboard - ' . ($projectInfo['name'] ?? 'Data2Rest'),
            'project' => $projectInfo,
            'globalDbCount' => $globalDbCount,
            'showWelcomeBanner' => $showWelcomeBanner,
            'stats' => [
                'total_databases' => count($databases),
                'total_records' => $totalRecords,
                'storage_usage' => $this->formatBytes($totalStorage),
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
