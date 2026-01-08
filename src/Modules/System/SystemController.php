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
}
