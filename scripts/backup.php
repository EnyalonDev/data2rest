<?php
// scripts/backup.php

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.");
}

require_once __DIR__ . '/../src/autoload.php';
use App\Core\Config;
use App\Core\Logger;

// Load Env
Config::loadEnv();

echo "[" . date('Y-m-d H:i:s') . "] Starting automated backup...\n";

// Configuration
$rootPath = dirname(__DIR__);
$backupDir = $rootPath . '/data/backups';
$dataPath = $rootPath . '/data';

if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Create Backup
$filename = 'auto_backup_' . date('Y-m-d_H-i-s') . '.zip';
$filepath = $backupDir . '/' . $filename;

$zip = new ZipArchive();
if ($zip->open($filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    $count = 0;
    $totalSize = 0;

    // Add all .sqlite files from data/
    // Exclude cache or temp files if any
    foreach (glob($dataPath . '/*.sqlite') as $dbFile) {
        $zip->addFile($dbFile, basename($dbFile));
        $totalSize += filesize($dbFile);
        $count++;
    }

    // Add manifest
    $zip->addFromString('manifest.json', json_encode([
        'created_at' => date('c'),
        'timestamp' => time(),
        'version' => '1.0',
        'type' => 'automated_cli',
        'files_count' => $count,
        'total_db_size' => $totalSize
    ], JSON_PRETTY_PRINT));

    $zip->close();

    $finalSize = filesize($filepath);
    echo "Backup created successfully: $filename\n";
    echo "Databases: $count | Size: " . round($finalSize / 1024 / 1024, 2) . " MB\n";

    // Log intent to system logs (if logger supports it without session)
    try {
        // Mock session for Logger if needed, or Logger should handle CLI
        if (session_status() == PHP_SESSION_NONE) {
            // Logger might rely on session for user_id, let's see. 
            // If Logger::log uses $_SESSION, we might need a workaround or just skip it.
            // Assuming Logger is robust enough or we just print to stdout.
        }
        // Logger::log('BACKUP_AUTO', ['file' => $filename]);
    } catch (\Exception $e) {
        // Ignore logging errors in CLI
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

    $backups = glob($backupDir . '/auto_backup_*.zip');

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
        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT value FROM system_settings WHERE key = 'backup_cloud_url'");
        $cloudUrl = $stmt->fetchColumn();

        if ($cloudUrl) {
            echo "Cloud URL found. Uploading to Google Drive...\n";

            // Limit file size for Cloud Sync (Google Apps Script usually has 50MB limits)
            if ($finalSize > 20 * 1024 * 1024) {
                echo "WARNING: File too large (>20MB) for standard GAS Sync. Skipping Cloud Upload.\n";
            } else {
                $fileContent = file_get_contents($filepath);
                $base64Data = base64_encode($fileContent);

                $payload = json_encode([
                    'filename' => $filename,
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

} else {
    echo "Error: Could not create zip archive at $filepath\n";
    exit(1);
}

echo "[" . date('Y-m-d H:i:s') . "] Backup process completed.\n";
