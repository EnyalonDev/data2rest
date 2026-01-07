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
        $databases = $db->query("SELECT * FROM databases")->fetchAll();

        $totalRecords = 0;
        $recentActivity = [];
        $totalStorage = 0;

        // 1. Calculate record counts and activity
        foreach ($databases as $database) {
            try {
                $targetDb = new PDO('sqlite:' . $database['path']);
                $targetDb->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

                $tables = $targetDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);

                foreach ($tables as $table) {
                    $count = $targetDb->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                    $totalRecords += $count;

                    // Try to get last edits
                    try {
                        $lastEdits = $targetDb->query("SELECT *, '$table' as table_source, '{$database['name']}' as db_source FROM $table ORDER BY fecha_edicion DESC LIMIT 3")->fetchAll();
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
            return strcmp($b['date'], $a['date']);
        });
        $recentActivity = array_slice($recentActivity, 0, 5);

        // 2. Calculate storage size
        $uploadDir = Config::get('upload_dir');
        if (is_dir($uploadDir)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploadDir));
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $totalStorage += $file->getSize();
                }
            }
        }

        $this->view('admin/dashboard', [
            'title' => 'Dashboard - Control Center',
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
