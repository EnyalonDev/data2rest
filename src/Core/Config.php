<?php

namespace App\Core;

/**
 * Application Configuration Management
 * Handles global settings and paths for the system.
 */
class Config
{
    /** @var array Default system configuration settings */
    private static $config = [
        'db_path' => __DIR__ . '/../../data/system.sqlite',
        'app_name' => 'Data2Rest',
        'base_url' => '', // Detected automatically if empty
        'upload_dir' => __DIR__ . '/../../public/uploads/',
        'db_storage_path' => __DIR__ . '/../../data/',
        'allowed_roles' => ['admin', 'user'],
    ];

    /**
     * Retrieve a configuration value by key.
     * 
     * @param string $key The configuration key to look up
     * @return mixed|null The configuration value or null if not found
     */
    public static function get($key)
    {
        return self::$config[$key] ?? null;
    }
}

