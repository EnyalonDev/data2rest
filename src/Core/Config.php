<?php

namespace App\Core;

class Config
{
    private static $config = [
        'db_path' => __DIR__ . '/../../data/system.sqlite',
        'app_name' => 'Data2Rest',
        'base_url' => '', // Will be detected or left empty for relative
        'upload_dir' => __DIR__ . '/../../public/uploads/',
        'db_storage_path' => __DIR__ . '/../../data/',
        'allowed_roles' => ['admin', 'user'],
    ];

    public static function get($key)
    {
        return self::$config[$key] ?? null;
    }
}
