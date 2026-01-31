<?php
// scripts/backup.php

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.");
}

require_once __DIR__ . '/../src/autoload.php';

use App\Core\Config;
use App\Core\Database;
use App\Modules\Backups\BackupController;

// Load Env
Config::loadEnv();

echo "[" . date('Y-m-d H:i:s') . "] Starting automated backup...\n";

try {
    // Create backup using BackupController
    $controller = new BackupController();
    $result = $controller->createBackupCLI();

    echo "Backup created successfully: {$result['filename']}\n";
    echo "Databases: {$result['backed_up']}/{$result['total']} | ";
    echo "Size: " . round($result['size'] / 1024 / 1024, 2) . " MB\n";

    if ($result['failed'] > 0) {
        echo "Failed databases: " . implode(', ', $result['failed_databases']) . "\n";
    }

    // --- Retention Policy ---
    // Default: Keep last 50 automated backups
    $retention = 50;

    // Parse arguments, e.g., php backup.php --keep=10
    global $argv;
    foreach ($argv as $arg) {
        if (strpos($arg, '--keep=') === 0) {
            $val = (int) substr($arg, 7);
            if ($val > 0)
                $retention = $val;
        }
    }

    $backupDir = dirname(__DIR__) . '/data/backups';
    $backups = glob($backupDir . '/backup_*.zip');

    if (count($backups) > $retention) {
        // Sort by time: Oldest first
        usort($backups, function ($a, $b) {
            return filemtime($a) - filemtime($b);
        });

        $toDelete = array_slice($backups, 0, count($backups) - $retention);

        echo "Retention Policy (Keep $retention): Cleaning up " . count($toDelete) . " old backups...\n";

        foreach ($toDelete as $file) {
            unlink($file);
            echo " - Deleted: " . basename($file) . "\n";
        }
    }

    // --- Cloud Synchronization ---
    echo "Checking cloud sync configuration...\n";
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT value FROM system_settings WHERE key_name = 'backup_cloud_url'");
        $cloudUrl = $stmt->fetchColumn();

        if ($cloudUrl) {
            echo "Cloud URL found. Uploading to Google Drive...\n";

            // Limit file size for Cloud Sync (Google Apps Script usually has 50MB limits)
            if ($result['size'] > 20 * 1024 * 1024) {
                echo "WARNING: File too large (>20MB) for standard GAS Sync. Skipping Cloud Upload.\n";
            } else {
                $fileContent = file_get_contents($result['filepath']);
                $base64Data = base64_encode($fileContent);

                $payload = json_encode([
                    'filename' => $result['filename'],
                    'mimeType' => 'application/zip',
                    'data' => $base64Data
                ]);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $cloudUrl);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 300);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);

                if ($httpCode == 200 || $httpCode == 302) {
                    echo "Cloud Upload Successful.\n";
                } else {
                    echo "Cloud Upload Failed (HTTP $httpCode): $curlError\n";
                }
            }
        } else {
            echo "No Cloud URL configured (backup_cloud_url). Skipping sync.\n";
        }
    } catch (Exception $e) {
        echo "Cloud Sync Error: " . $e->getMessage() . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "[" . date('Y-m-d H:i:s') . "] Backup process completed.\n";
