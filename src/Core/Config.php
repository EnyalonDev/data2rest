<?php

namespace App\Core;

/**
 * Application Configuration Management
 * Handles global settings and paths for the system.
 */
class Config
{
    /** @var array|null Configuration cache */
    private static $config = null;

    /**
     * Initialize configuration if not already set.
     */
    private static function init()
    {
        if (self::$config !== null) {
            return;
        }

        self::$config = [
            'db_path' => getenv('DB_PATH') ?: __DIR__ . '/../../data/system.sqlite',
            'app_name' => getenv('APP_NAME') ?: 'Data2Rest',
            'base_url' => getenv('BASE_URL') ?: '',
            'upload_dir' => realpath(__DIR__ . '/../../public/uploads/') . '/',
            'db_storage_path' => realpath(__DIR__ . '/../../data/') . '/',
            'allowed_roles' => ['admin', 'user'],
            'dev_mode' => getenv('DEV_MODE') === 'true',
        ];
    }

    /**
     * Load environment variables from .env file if it exists.
     */
    public static function loadEnv()
    {
        $envFile = __DIR__ . '/../../.env';
        if (!file_exists($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }

    /**
     * Retrieve a configuration value by key.
     * 
     * @param string $key The configuration key to look up
     * @return mixed|null The configuration value or null if not found
     */
    public static function get($key)
    {
        self::init();
        return self::$config[$key] ?? null;
    }

    /**
     * Get a setting from the system_settings table in the database.
     */
    public static function getSetting($key, $default = null)
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT value FROM system_settings WHERE key = ?");
            $stmt->execute([$key]);
            $val = $stmt->fetchColumn();
            return ($val !== false) ? $val : $default;
        } catch (\Exception $e) {
            return $default;
        }
    }
}

