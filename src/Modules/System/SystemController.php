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
}
