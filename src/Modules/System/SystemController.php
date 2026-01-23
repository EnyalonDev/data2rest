<?php

namespace App\Modules\System;

use App\Core\BaseController;
use App\Core\Auth;

/**
 * SystemController
 * Provides internal system information and settings.
 */
/**
 * SystemController Controller
 *
 * Core Features: TODO
 *
 * Security: Requires login, permission checks as implemented.
 *
 * @package App\Modules\
 * @author DATA2REST Development Team
 * @version 1.0.0
 */
class SystemController extends BaseController
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
     * Returns JSON with critical server configuration.
     */
    /**
     * info method
     *
     * @return void
     */
    public function info()
    {
        while (ob_get_level())
            ob_end_clean(); // Ensure clean output
        $db = \App\Core\Database::getInstance()->getConnection();
        // Use key_name as per current schema
        $stmt = $db->query("SELECT value FROM system_settings WHERE key_name = 'time_offset_total'");
        $offset = (int) ($stmt->fetchColumn() ?: 0);

        $now = new \DateTime();
        if ($offset !== 0) {
            $now->modify(($offset >= 0 ? '+' : '') . $offset . ' minutes');
        }

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
            'server_time' => $now->format('Y-m-d H:i:s'),
            'time_offset' => $offset,
            'os' => PHP_OS,
            'database_type' => \App\Core\Config::get('system_db_config')['type'] ?? 'sqlite',
            'sqlite_version' => \PDO::class ? (new \PDO('sqlite::memory:'))->query('select sqlite_version()')->fetchColumn() : 'N/A'
        ]);
        exit;
    }

    /**
     * Updates the global time offset in minutes.
     */
    public function updateTimeOffset()
    {
        while (ob_get_level())
            ob_end_clean(); // Ensure clean output
        Auth::requireAdmin();
        $hours = (int) ($_POST['hours'] ?? 0);
        $minutes = (int) ($_POST['minutes'] ?? 0);
        $totalMinutes = ($hours * 60) + ($minutes >= 0 ? $minutes : 0);
        if ($hours < 0 && $minutes > 0) {
            $totalMinutes = ($hours * 60) - $minutes;
        }

        $db = \App\Core\Database::getInstance()->getConnection();
        $adapter = \App\Core\Database::getInstance()->getAdapter();

        // Fix: Use key_name instead of key
        $sql = "INSERT INTO system_settings (key_name, value, updated_at) VALUES (?, ?, datetime('now')) 
                ON CONFLICT(key_name) DO UPDATE SET value=excluded.value, updated_at=excluded.updated_at";

        // Use standard SQL for MySQL/Postgres if needed, but for SQLite ON CONFLICT is good.
        // Actually, let's use a simpler DELETE/INSERT or the adapter if it supports key_name override.
        // Let's just use raw SQL for safety with the new column name.
        if ($adapter->getType() === 'mysql') {
            $sql = "INSERT INTO system_settings (key_name, value, updated_at) VALUES (?, ?, NOW()) 
                     ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = NOW()";
        }

        $stmt = $db->prepare($sql);
        $stmt->execute(['time_offset_total', $totalMinutes]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'new_offset' => $totalMinutes]);
        exit;
    }

    /**
     * Toggles development mode in system settings.
     */
    public function toggleDevMode()
    {
        while (ob_get_level())
            ob_end_clean(); // Ensure clean output
        Auth::requireAdmin();
        $db = \App\Core\Database::getInstance()->getConnection();

        $stmt = $db->query("SELECT value FROM system_settings WHERE key_name = 'dev_mode'");
        $current = $stmt->fetchColumn();
        $newValue = ($current === 'on') ? 'off' : 'on';

        // Upsert logic
        $adapter = \App\Core\Database::getInstance()->getAdapter();
        if ($adapter->getType() === 'mysql') {
            $sql = "INSERT INTO system_settings (key_name, value, updated_at) VALUES (?, ?, NOW()) 
                     ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = NOW()";
        } else {
            $sql = "INSERT INTO system_settings (key_name, value, updated_at) VALUES (?, ?, datetime('now')) 
                ON CONFLICT(key_name) DO UPDATE SET value=excluded.value, updated_at=excluded.updated_at";
        }

        $stmt = $db->prepare($sql);
        $stmt->execute(['dev_mode', $newValue]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'dev_mode' => $newValue]);
        exit;
    }

    /**
     * Clears application temporary cache files.
     */
    public function clearCache()
    {
        // Prevent accidental output (like newlines) from breaking JSON
        while (ob_get_level())
            ob_end_clean();

        Auth::requireAdmin();
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

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
        // Prevent accidental output (like newlines) from breaking JSON
        while (ob_get_level())
            ob_end_clean();

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
        while (ob_get_level())
            ob_end_clean(); // Ensure clean output
        Auth::requireAdmin();
        $db = \App\Core\Database::getInstance()->getConnection();
        $adapter = \App\Core\Database::getInstance()->getAdapter();

        if ($adapter->getType() === 'mysql') {
            $sql = "INSERT INTO system_settings (key_name, value, updated_at) VALUES (?, ?, NOW()) 
                     ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = NOW()";
        } else {
            $sql = "INSERT INTO system_settings (key_name, value, updated_at) VALUES (?, ?, datetime('now')) 
                ON CONFLICT(key_name) DO UPDATE SET value=excluded.value, updated_at=excluded.updated_at";
        }

        $stmt = $db->prepare($sql);
        $stmt->execute(['show_welcome_banner', '0']);

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * Show Google Settings View
     */
    public function googleSettings()
    {
        Auth::requireAdmin();

        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM system_settings WHERE key_name IN ('google_client_id', 'google_client_secret', 'google_redirect_uri', 'google_login_enabled')");
        $settings = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $settings[$row['key_name']] = $row['value'];
        }

        $this->view('admin/settings/google', ['title' => 'Google Login Settings', 'settings' => $settings]);
    }

    /**
     * Update Google Settings
     */
    public function updateGoogleSettings()
    {
        Auth::requireAdmin();

        $clientId = trim($_POST['google_client_id'] ?? '');
        $clientSecret = trim($_POST['google_client_secret'] ?? '');
        $redirectUri = trim($_POST['google_redirect_uri'] ?? '');
        $enabled = isset($_POST['google_login_enabled']) ? '1' : '0';

        $db = \App\Core\Database::getInstance()->getConnection();
        $adapter = \App\Core\Database::getInstance()->getAdapter(); // For checking type if needed

        // Helper to upsert
        $upsert = function ($key, $val) use ($db, $adapter) {
            try {
                if ($adapter->getType() === 'mysql') {
                    $sql = "INSERT INTO system_settings (key_name, value, updated_at) VALUES (?, ?, NOW()) 
                             ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = NOW()";
                } else {
                    $sql = "INSERT INTO system_settings (key_name, value, updated_at) VALUES (?, ?, datetime('now')) 
                        ON CONFLICT(key_name) DO UPDATE SET value=excluded.value, updated_at=excluded.updated_at";
                }
                $stmt = $db->prepare($sql);
                $stmt->execute([$key, $val]);
            } catch (\PDOException $e) {
                // If error is about missing column 'updated_at', fallback to simple update
                if (strpos($e->getMessage(), 'no column named updated_at') !== false || strpos($e->getMessage(), "Unknown column 'updated_at'") !== false) {
                    if ($adapter->getType() === 'mysql') {
                        $sql = "INSERT INTO system_settings (key_name, value) VALUES (?, ?) 
                                 ON DUPLICATE KEY UPDATE value = VALUES(value)";
                    } else {
                        $sql = "INSERT INTO system_settings (key_name, value) VALUES (?, ?) 
                            ON CONFLICT(key_name) DO UPDATE SET value=excluded.value";
                    }
                    $stmt = $db->prepare($sql);
                    $stmt->execute([$key, $val]);
                } else {
                    throw $e;
                }
            }
        };

        $upsert('google_client_id', $clientId);
        // Only update secret if provided (not empty), allowing placeholders in UI
        if (!empty($clientSecret) && $clientSecret !== '••••••••••••••••••••••••••••••') {
            $upsert('google_client_secret', $clientSecret);
        }
        $upsert('google_redirect_uri', $redirectUri);
        $upsert('google_login_enabled', $enabled);

        Auth::setFlashError("Google settings updated successfully!", "success");
        $this->redirect('admin/settings/google');
    }
}
