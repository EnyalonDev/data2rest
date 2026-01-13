<?php
// scripts/maintenance.php

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.");
}

require_once __DIR__ . '/../src/autoload.php';
use App\Core\Config;
use App\Core\Maintenance;

// Load Env
Config::loadEnv();

echo "[" . date('Y-m-d H:i:s') . "] Starting system maintenance...\n";

$result = Maintenance::run(true); // Force run (bypass probability)

if ($result['success']) {
    echo "Maintenance completed successfully.\n";
    echo " - Deleted Versions: " . $result['deleted_versions'] . "\n";
    echo " - Deleted Logs: " . $result['deleted_logs'] . "\n";
} else {
    echo "Maintenance failed: " . ($result['error'] ?? 'Unknown error') . "\n";
}

echo "[" . date('Y-m-d H:i:s') . "] Process finished.\n";
