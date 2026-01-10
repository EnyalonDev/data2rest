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
            'db_path' => __DIR__ . '/../../data/system.sqlite',
            'app_name' => 'Data2Rest',
            'base_url' => '',
            'upload_dir' => realpath(__DIR__ . '/../../public/uploads/') . '/',
            'db_storage_path' => realpath(__DIR__ . '/../../data/') . '/',
            'allowed_roles' => ['admin', 'user'],
        ];
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
}

