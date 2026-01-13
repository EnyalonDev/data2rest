<?php

namespace App\Core;

use App\Core\Database;
use App\Core\Logger;
use PDO;

/**
 * System Maintenance Utility
 */
class Maintenance
{
    /**
     * Performs a full system cleanup.
     * - Deletes data versions older than X days.
     * - Deletes activity logs older than X days.
     * - Optimizes SQLite databases (VACUUM).
     */
    public static function run($force = false)
    {
        // Prevent running too often in web context
        if (!$force && php_sapi_name() !== 'cli') {
            // Only run with 1% probability on web requests
            if (mt_rand(1, 100) !== 1) {
                return;
            }
        }

        try {
            $db = Database::getInstance()->getConnection();

            // 1. Retention Policy: Data Versions (Audit Trail)
            $stmtConf = $db->prepare("SELECT value FROM system_settings WHERE key = 'audit_retention_days'");
            $stmtConf->execute();
            $retentionDays = $stmtConf->fetchColumn() ?: 30;

            $stmt = $db->prepare("DELETE FROM data_versions WHERE created_at < date('now', ?)");
            $stmt->execute(["-$retentionDays days"]);
            $deletedVersions = $stmt->rowCount();

            // 2. Retention Policy: Activity Logs
            $stmtLogConf = $db->prepare("SELECT value FROM system_settings WHERE key = 'log_retention_days'");
            $stmtLogConf->execute();
            $logRetention = $stmtLogConf->fetchColumn() ?: 60;

            $stmtLogs = $db->prepare("DELETE FROM activity_logs WHERE created_at < date('now', ?)");
            $stmtLogs->execute(["-$logRetention days"]);
            $deletedLogs = $stmtLogs->rowCount();

            // 3. Database Optimization
            // VACUUM cleans up unused space after large deletes
            $db->exec("VACUUM");

            if ($deletedVersions > 0 || $deletedLogs > 0) {
                error_log("Maintenance: Deleted $deletedVersions old versions and $deletedLogs logs.");
                // We don't use Logger::log here to avoid infinite loops if it were called during a log action
            }

            return [
                'success' => true,
                'deleted_versions' => $deletedVersions,
                'deleted_logs' => $deletedLogs
            ];

        } catch (\Exception $e) {
            error_log("Maintenance Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
