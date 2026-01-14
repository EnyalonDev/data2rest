#!/usr/bin/env php
<?php
/**
 * Automatic System Database Backup Script
 * 
 * This script creates automatic backups of the system database.
 * It should be run via cron job.
 * 
 * Recommended cron schedule (daily at 2 AM):
 * 0 2 * * * /usr/bin/php /opt/homebrew/var/www/data2rest/scripts/auto_backup_system.php
 */

require_once __DIR__ . '/../src/autoload.php';

use App\Core\Database;
use App\Core\Logger;

// Configuration
$systemDbPath = realpath(__DIR__ . '/../data/system.sqlite');
$backupDir = __DIR__ . '/../data/backups/system/';
$maxBackups = 30; // Keep last 30 automatic backups

// Ensure backup directory exists
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

try {
    // Create backup filename with timestamp
    $timestamp = date('Y-m-d_H-i-s');
    $backupFile = $backupDir . "system_auto_{$timestamp}.sqlite";

    // Copy the database file
    if (!copy($systemDbPath, $backupFile)) {
        throw new Exception('Failed to create backup file');
    }

    echo "[" . date('Y-m-d H:i:s') . "] Backup created successfully: " . basename($backupFile) . "\n";
    echo "Size: " . formatBytes(filesize($backupFile)) . "\n";

    // Log the backup creation
    try {
        Logger::log('SYSTEM_BACKUP_CREATED', [
            'type' => 'automatic',
            'file' => basename($backupFile),
            'size' => filesize($backupFile),
            'script' => 'auto_backup_system.php'
        ]);
    } catch (Exception $e) {
        echo "Warning: Could not log backup creation: " . $e->getMessage() . "\n";
    }

    // Clean up old automatic backups
    $files = glob($backupDir . 'system_auto_*.sqlite');

    // Sort by modification time (newest first)
    usort($files, function ($a, $b) {
        return filemtime($b) - filemtime($a);
    });

    // Delete old backups beyond the limit
    $deleted = 0;
    for ($i = $maxBackups; $i < count($files); $i++) {
        if (unlink($files[$i])) {
            $deleted++;
            echo "Deleted old backup: " . basename($files[$i]) . "\n";
        }
    }

    if ($deleted > 0) {
        echo "Cleaned up $deleted old backup(s)\n";
    }

    echo "Backup process completed successfully!\n";
    exit(0);

} catch (Exception $e) {
    echo "[ERROR] " . date('Y-m-d H:i:s') . " - " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Format bytes to human readable
 */
function formatBytes($bytes, $precision = 2)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }

    return round($bytes, $precision) . ' ' . $units[$i];
}
