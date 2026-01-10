<?php

namespace App\Modules\System;

use App\Core\BaseController;
use App\Core\Auth;

/**
 * SystemController
 * Provides internal system information and settings.
 */
class SystemController extends BaseController
{
    public function __construct()
    {
        Auth::requireLogin();
    }

    /**
     * Returns JSON with critical server configuration.
     */
    public function info()
    {
        header('Content-Type: application/json');
        echo json_encode([
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time') . 's',
            'max_input_vars' => ini_get('max_input_vars'),
            'display_errors' => ini_get('display_errors') ? 'ON' : 'OFF',
            'timezone' => date_default_timezone_get(),
            'os' => PHP_OS,
            'sqlite_version' => \PDO::class ? (new \PDO('sqlite::memory:'))->query('select sqlite_version()')->fetchColumn() : 'N/A'
        ]);
        exit;
    }

    /**
     * Toggles development mode in system settings.
     */
    public function toggleDevMode()
    {
        Auth::requireAdmin();
        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT value FROM system_settings WHERE key = 'dev_mode'");
        $current = $stmt->fetchColumn();
        $newValue = ($current === 'on') ? 'off' : 'on';

        $stmt = $db->prepare("UPDATE system_settings SET value = ? WHERE key = 'dev_mode'");
        $stmt->execute([$newValue]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'dev_mode' => $newValue]);
        exit;
    }

    /**
     * Clears application temporary cache files.
     */
    public function clearCache()
    {
        Auth::requireAdmin();
        // Since sqlite doesn't have internal cache to clear via PHP, 
        // we'll focus on opcache and potentially temp files.
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        // Clear any specific app cache if it existed (e.g. data/cache/*)
        $cacheDir = __DIR__ . '/../../../data/cache';
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '/*');
            foreach ($files as $file) {
                if (is_file($file))
                    unlink($file);
            }
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * Clears all active PHP sessions except the current one.
     */
    public function clearSessions()
    {
        Auth::requireAdmin();
        $sessionDir = session_save_path();
        if (empty($sessionDir)) {
            $sessionDir = sys_get_temp_dir();
        }

        $currentSessionId = session_id();
        $files = glob($sessionDir . '/sess_*');
        $cleared = 0;

        foreach ($files as $file) {
            if (is_file($file) && strpos($file, $currentSessionId) === false) {
                unlink($file);
                $cleared++;
            }
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'cleared' => $cleared]);
        exit;
    }
    public function dismissBanner()
    {
        Auth::requireAdmin();
        $db = \App\Core\Database::getInstance()->getConnection();

        $stmt = $db->prepare("REPLACE INTO system_settings (key, value) VALUES ('show_welcome_banner', '0')");
        $stmt->execute();

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * Performs a global search across all databases and tables of the active project.
     */
    public function globalSearch()
    {
        $query = $_POST['q'] ?? '';
        if (strlen($query) < 2) {
            header('Content-Type: application/json');
            echo json_encode(['results' => []]);
            exit;
        }

        $sysDb = \App\Core\Database::getInstance()->getConnection();
        $projectId = Auth::getActiveProject();

        // Get all databases for the active project
        $stmt = $sysDb->prepare("SELECT id, name, path FROM databases WHERE project_id = ?");
        $stmt->execute([$projectId]);
        $databases = $stmt->fetchAll();

        $results = [];

        foreach ($databases as $dbInfo) {
            if (!file_exists($dbInfo['path']))
                continue;

            try {
                $targetDb = new \PDO('sqlite:' . $dbInfo['path']);
                $targetDb->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                // Get all tables in this database
                $stmtTables = $targetDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                $tables = $stmtTables->fetchAll(\PDO::FETCH_COLUMN);

                foreach ($tables as $table) {
                    // Get columns to search in
                    $stmtCols = $targetDb->query("PRAGMA table_info($table)");
                    $cols = $stmtCols->fetchAll(\PDO::FETCH_ASSOC);

                    $searchCols = [];
                    foreach ($cols as $col) {
                        // Only search in text or blob-like columns for efficiency
                        $type = strtolower($col['type']);
                        if (strpos($type, 'text') !== false || strpos($type, 'varchar') !== false || $type === '' || strpos($type, 'char') !== false) {
                            $searchCols[] = $col['name'];
                        }
                    }

                    if (empty($searchCols))
                        continue;

                    $whereParts = [];
                    foreach ($searchCols as $sc) {
                        $whereParts[] = "$sc LIKE ?";
                    }

                    $sql = "SELECT * FROM $table WHERE " . implode(" OR ", $whereParts) . " LIMIT 5";
                    $stmtSearch = $targetDb->prepare($sql);
                    $searchParams = array_fill(0, count($searchCols), "%$query%");
                    $stmtSearch->execute($searchParams);
                    $found = $stmtSearch->fetchAll(\PDO::FETCH_ASSOC);

                    foreach ($found as $item) {
                        // Find a good display value (first non-id column)
                        $displayVal = $item[array_keys($item)[1] ?? array_keys($item)[0]];

                        $results[] = [
                            'db_name' => $dbInfo['name'],
                            'db_id' => $dbInfo['id'],
                            'table' => $table,
                            'id' => $item['id'] ?? null,
                            'display' => mb_strimwidth(strip_tags((string) $displayVal), 0, 100, "..."),
                            'snippet' => mb_strimwidth(strip_tags(implode(' ', array_values($item))), 0, 150, "...")
                        ];
                    }

                    if (count($results) > 30)
                        break 2; // Cap total results
                }
            } catch (\Exception $e) {
                // Skip databases with errors
                continue;
            }
        }

        header('Content-Type: application/json');
        echo json_encode(['results' => $results]);
        exit;
    }
}
