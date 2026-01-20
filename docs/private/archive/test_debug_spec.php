<?php
// Mock environment
define('APP_ROOT', __DIR__);
require_once 'src/autoload.php';
\App\Core\Config::loadEnv();
\App\Core\Auth::init();

// Mock GET
$_GET['db_id'] = 1; // Mock DB ID

try {
     = new \App\Modules\Api\SwaggerController();
    ->spec();
} catch (Exception ) {
    echo "EXCEPTION: " . ->getMessage();
}
