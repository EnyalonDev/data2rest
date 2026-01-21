<?php

namespace App\Modules\SystemDatabase;

use App\Core\Auth;
use App\Core\Database;
use App\Core\BaseController;
use App\Core\Logger;
use App\Core\Lang;
use PDO;
use Exception;

/**
 * System Database Administration Controller
 * SUPER ADMIN ONLY - Manages the system database (system.sqlite)
 * Includes backups, SQL execution, optimization, and comprehensive logging
 */
/**
 * SystemDatabaseController Controller
 *
 * Core Features: TODO
 *
 * Security: Requires login, permission checks as implemented.
 *
 * @package App\Modules\
 * @author DATA2REST Development Team
 * @version 1.0.0
 */
class SystemDatabaseController extends BaseController
{
    private $systemDbPath;
    private $backupDir;

    /**
     * __construct method
     *
     * @return void
     */
    public function __construct()
    {
        Auth::requireLogin();
        Auth::requireAdmin(); // CRITICAL: Only Super Admin can access this module

        // Get system database path
        $this->systemDbPath = realpath(__DIR__ . '/../../../data/system.sqlite');
        $this->backupDir = __DIR__ . '/../../../data/backups/system/';

        // Ensure backup directory exists
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0777, true);
        }
    }

    /**
     * Dashboard - System Database Overview
     */
    /**
     * index method
     *
     * @return void
     */
    public function index()
    {
        $db = Database::getInstance()->getConnection();
        $adapter = Database::getInstance()->getAdapter();
        $type = $adapter->getType();

        // 1. Get Database Size
        $dbSizeFormatted = 'N/A';
        try {
            if ($type === 'sqlite') {
                $dbSize = file_exists($this->systemDbPath) ? filesize($this->systemDbPath) : 0;
                $dbSizeFormatted = $this->formatBytes($dbSize);
            } elseif ($type === 'pgsql' || $type === 'postgresql') {
                $dbSize = $db->query("SELECT pg_database_size(current_database())")->fetchColumn();
                $dbSizeFormatted = $this->formatBytes($dbSize);
            } elseif ($type === 'mysql') {
                $stmt = $db->query("SELECT SUM(data_length + index_length) FROM information_schema.tables WHERE table_schema = DATABASE()");
                $dbSize = $stmt->fetchColumn();
                $dbSizeFormatted = $this->formatBytes($dbSize);
            }
        } catch (\Exception $e) { /* Ignore size error */
        }

        // 2. Get Tables
        $tables = [];
        try {
            if ($type === 'sqlite') {
                $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } elseif ($type === 'pgsql' || $type === 'postgresql') {
                $stmt = $db->query("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = 'public'");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } elseif ($type === 'mysql') {
                $stmt = $db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE()");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }
        } catch (\Exception $e) { /* Ignore table list error */
        }

        $totalTables = count($tables);
        $totalRecords = 0;

        foreach ($tables as $table) {
            try {
                $qTable = $adapter->quoteName($table);
                $count = $db->query("SELECT COUNT(*) FROM $qTable")->fetchColumn();
                $totalRecords += $count;
            } catch (Exception $e) {
                // Skip tables with errors
            }
        }

        // Get last backup info
        $backups = $this->getBackupsList();
        $lastBackup = !empty($backups) ? $backups[0] : null;

        // Get disk space (Only relevant if local)
        $diskFree = 0;
        $diskTotal = 0;
        $diskUsedPercent = 0;
        if (file_exists(dirname($this->systemDbPath))) {
            $diskFree = disk_free_space(dirname($this->systemDbPath));
            $diskTotal = disk_total_space(dirname($this->systemDbPath));
            $diskUsed = $diskTotal - $diskFree;
            $diskUsedPercent = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 2) : 0;
        }

        $this->view('admin/system_database/index', [
            'title' => Lang::get('system_database.title'),
            'dbSize' => $dbSizeFormatted,
            'totalTables' => $totalTables,
            'totalRecords' => number_format($totalRecords),
            'lastBackup' => $lastBackup,
            'diskFree' => $this->formatBytes($diskFree),
            'diskTotal' => $this->formatBytes($diskTotal),
            'diskUsedPercent' => $diskUsedPercent,
            'breadcrumbs' => [
                Lang::get('system_database.title') => null
            ]
        ]);
    }

    /**
     * List all system tables with statistics
     */
    /**
     * tables method
     *
     * @return void
     */
    public function tables()
    {
        $db = Database::getInstance()->getConnection();
        $adapter = Database::getInstance()->getAdapter();
        $type = $adapter->getType();

        $tableNames = [];
        if ($type === 'sqlite') {
            $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
            $tableNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } elseif ($type === 'pgsql' || $type === 'postgresql') {
            $stmt = $db->query("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = 'public' ORDER BY tablename");
            $tableNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } elseif ($type === 'mysql') {
            $stmt = $db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() ORDER BY table_name");
            $tableNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        $tables = [];
        foreach ($tableNames as $tableName) {
            try {
                $qTable = $adapter->quoteName($tableName);
                $count = $db->query("SELECT COUNT(*) FROM $qTable")->fetchColumn();

                // Get table size (approximate)
                $size = 0;
                if ($type === 'sqlite') {
                    // dbstat might not be enabled
                    try {
                        $stmt = $db->prepare("SELECT SUM(pgsize) FROM dbstat WHERE name = ?");
                        $stmt->execute([$tableName]);
                        $size = $stmt->fetchColumn() ?: 0;
                    } catch (Exception $e) {
                    }
                }

                $tables[] = [
                    'name' => $tableName,
                    'records' => $count,
                    'size' => $size ? $this->formatBytes($size) : 'N/A'
                ];
            } catch (Exception $e) {
                $tables[] = [
                    'name' => $tableName,
                    'records' => 'Error',
                    'size' => 'N/A'
                ];
            }
        }

        $this->view('admin/system_database/tables', [
            'title' => Lang::get('system_database.tables'),
            'tables' => $tables,
            'breadcrumbs' => [
                Lang::get('system_database.title') => 'admin/system-database',
                Lang::get('system_database.tables') => null
            ]
        ]);
    }

    /**
     * Show table details (structure, indexes, sample data)
     */
    /**
     * tableDetails method
     *
     * @return void
     */
    public function tableDetails()
    {
        $tableName = $_GET['table'] ?? null;
        if (!$tableName) {
            Auth::setFlashError('Table name is required.', 'error');
            $this->redirect('admin/system-database/tables');
        }

        // Sanitize table name (basic)
        $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', $tableName);

        $db = Database::getInstance()->getConnection();
        $adapter = Database::getInstance()->getAdapter();
        $type = $adapter->getType();

        // Get table structure & indexes abstraction
        $columns = [];
        $indexes = [];

        try {
            if ($type === 'sqlite') {
                $columns = $db->query("PRAGMA table_info($tableName)")->fetchAll(PDO::FETCH_ASSOC);
                $indexes = $db->query("PRAGMA index_list($tableName)")->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($type === 'pgsql' || $type === 'postgresql') {
                // Postgres Columns
                $stmt = $db->prepare("SELECT column_name as name, data_type as type, 
                                       CASE WHEN is_nullable='YES' THEN 0 ELSE 1 END as notnull, 
                                       column_default as dflt_value 
                                       FROM information_schema.columns WHERE table_name = ?");
                $stmt->execute([$tableName]);
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Postgres Indexes
                $stmt = $db->prepare("SELECT indexname as name FROM pg_indexes WHERE tablename = ?");
                $stmt->execute([$tableName]);
                $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($type === 'mysql') {
                // MySQL Columns
                $stmt = $db->query("DESCRIBE `$tableName`");
                $rawCols = $stmt->fetchAll(PDO::FETCH_ASSOC);
                // Normalize to SQLite-ish structure for View compatibility
                foreach ($rawCols as $c) {
                    $columns[] = [
                        'name' => $c['Field'],
                        'type' => $c['Type'],
                        'notnull' => ($c['Null'] === 'NO') ? 1 : 0,
                        'dflt_value' => $c['Default'],
                        'pk' => ($c['Key'] === 'PRI') ? 1 : 0
                    ];
                }

                // MySQL Indexes
                $stmt = $db->query("SHOW INDEX FROM `$tableName`");
                $rawIdx = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $seen = [];
                foreach ($rawIdx as $idx) {
                    if (!in_array($idx['Key_name'], $seen)) {
                        $indexes[] = ['name' => $idx['Key_name']];
                        $seen[] = $idx['Key_name'];
                    }
                }
            }
        } catch (\Exception $e) {
            Auth::setFlashError("Could not inspect table: " . $e->getMessage());
        }

        // Get sample data (first 10 records)
        try {
            $stmt = $db->query("SELECT * FROM $tableName LIMIT 10");
            $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $sampleData = [];
        }

        // Get record count
        $count = $db->query("SELECT COUNT(*) FROM $tableName")->fetchColumn();

        $this->view('admin/system_database/table_details', [
            'title' => Lang::get('system_database.tables') . ' - ' . $tableName,
            'tableName' => $tableName,
            'columns' => $columns,
            'indexes' => $indexes,
            'sampleData' => $sampleData,
            'recordCount' => $count,
            'breadcrumbs' => [
                Lang::get('system_database.title') => 'admin/system-database',
                Lang::get('system_database.tables') => 'admin/system-database/tables',
                $tableName => null
            ]
        ]);
    }

    /**
     * SQL Query Executor
     */
    /**
     * queryExecutor method
     *
     * @return void
     */
    public function queryExecutor()
    {
        $this->view('admin/system_database/query_executor', [
            'title' => Lang::get('system_database.query_executor'),
            'breadcrumbs' => [
                Lang::get('system_database.title') => 'admin/system-database',
                Lang::get('system_database.query_executor') => null
            ]
        ]);
    }

    /**
     * Execute SQL Query
     */
    /**
     * executeQuery method
     *
     * @return void
     */
    public function executeQuery()
    {
        $query = $_POST['query'] ?? '';

        if (empty($query)) {
            Auth::setFlashError('Query cannot be empty.', 'error');
            $this->redirect('admin/system-database/query-executor');
        }

        $db = Database::getInstance()->getConnection();

        // Check for dangerous queries
        $dangerousPatterns = ['/DROP\s+TABLE/i', '/DROP\s+DATABASE/i', '/TRUNCATE/i'];
        $isDangerous = false;
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $query)) {
                $isDangerous = true;
                break;
            }
        }

        // If dangerous and no confirmation, ask for confirmation
        if ($isDangerous && !isset($_POST['confirmed'])) {
            $this->view('admin/system_database/query_executor', [
                'title' => Lang::get('system_database.query_executor'),
                'query' => $query,
                'needsConfirmation' => true,
                'breadcrumbs' => [
                    Lang::get('system_database.title') => 'admin/system-database',
                    Lang::get('system_database.query_executor') => null
                ]
            ]);
            return;
        }

        try {
            // Determine if it's a SELECT query
            $isSelect = preg_match('/^\s*SELECT/i', $query);

            if ($isSelect) {
                $stmt = $db->query($query);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $affectedRows = count($results);
            } else {
                $affectedRows = $db->exec($query);
                $results = [];
            }

            // Log the query execution
            Logger::log('SYSTEM_QUERY_EXECUTED', [
                'query' => substr($query, 0, 500), // Limit query length in log
                'affected_rows' => $affectedRows
            ]);

            $this->view('admin/system_database/query_executor', [
                'title' => Lang::get('system_database.query_executor'),
                'query' => $query,
                'results' => $results,
                'affectedRows' => $affectedRows,
                'success' => true,
                'breadcrumbs' => [
                    Lang::get('system_database.title') => 'admin/system-database',
                    Lang::get('system_database.query_executor') => null
                ]
            ]);

        } catch (Exception $e) {
            $this->view('admin/system_database/query_executor', [
                'title' => Lang::get('system_database.query_executor'),
                'query' => $query,
                'error' => $e->getMessage(),
                'breadcrumbs' => [
                    Lang::get('system_database.title') => 'admin/system-database',
                    Lang::get('system_database.query_executor') => null
                ]
            ]);
        }
    }

    /**
     * Optimize System Database (VACUUM + ANALYZE)
     */
    /**
     * optimize method
     *
     * @return void
     */
    public function optimize()
    {
        $db = Database::getInstance()->getConnection();

        try {
            $db->exec('VACUUM');
            $db->exec('ANALYZE');

            Logger::log('SYSTEM_DATABASE_OPTIMIZED', [
                'operation' => 'VACUUM + ANALYZE'
            ]);

            Auth::setFlashError(Lang::get('system_database.database_optimized'), 'success');
        } catch (Exception $e) {
            Auth::setFlashError('Error optimizing database: ' . $e->getMessage(), 'error');
        }

        $this->redirect('admin/system-database');
    }

    /**
     * Clean old data (logs, audit trail, recycle bin)
     */
    /**
     * cleanOldData method
     *
     * @return void
     */
    public function cleanOldData()
    {
        $db = Database::getInstance()->getConnection();
        $adapter = Database::getInstance()->getAdapter();
        $keyCol = $adapter->quoteName('key');

        // Get retention settings
        $stmt = $db->query("SELECT value FROM system_settings WHERE $keyCol = 'log_retention_days'");
        $logRetention = (int) ($stmt->fetchColumn() ?: 90);

        $stmt = $db->query("SELECT value FROM system_settings WHERE $keyCol = 'audit_retention_days'");
        $auditRetention = (int) ($stmt->fetchColumn() ?: 365);

        $cutoffLog = date('Y-m-d H:i:s', strtotime("-$logRetention days"));
        $cutoffAudit = date('Y-m-d H:i:s', strtotime("-$auditRetention days"));

        try {
            // Clean old logs
            $stmt = $db->prepare("DELETE FROM logs WHERE created_at < ?");
            $stmt->execute([$cutoffLog]);
            $logsDeleted = $stmt->rowCount();

            // Clean old audit trail
            $stmt = $db->prepare("DELETE FROM audit_trail WHERE created_at < ?");
            $stmt->execute([$cutoffAudit]);
            $auditDeleted = $stmt->rowCount();

            // Clean recycle bin (older than 30 days)
            $cutoffRecycle = date('Y-m-d H:i:s', strtotime('-30 days'));
            $stmt = $db->prepare("DELETE FROM recycle_bin WHERE deleted_at < ?");
            $stmt->execute([$cutoffRecycle]);
            $recycleDeleted = $stmt->rowCount();

            Logger::log('SYSTEM_DATA_CLEANED', [
                'logs_deleted' => $logsDeleted,
                'audit_deleted' => $auditDeleted,
                'recycle_deleted' => $recycleDeleted
            ]);

            Auth::setFlashError(
                Lang::get('system_database.data_cleaned') . " (Logs: $logsDeleted, Audit: $auditDeleted, Recycle: $recycleDeleted)",
                'success'
            );
        } catch (Exception $e) {
            Auth::setFlashError('Error cleaning data: ' . $e->getMessage(), 'error');
        }

        $this->redirect('admin/system-database');
    }

    /**
     * List all backups
     */
    /**
     * backupsList method
     *
     * @return void
     */
    public function backupsList()
    {
        $backups = $this->getBackupsList();

        $this->view('admin/system_database/backups', [
            'title' => Lang::get('system_database.backups'),
            'backups' => $backups,
            'breadcrumbs' => [
                Lang::get('system_database.title') => 'admin/system-database',
                Lang::get('system_database.backups') => null
            ]
        ]);
    }

    /**
     * Create manual backup
     */
    /**
     * createBackup method
     *
     * @return void
     */
    public function createBackup()
    {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $backupFile = $this->backupDir . "system_manual_{$timestamp}.sqlite";

            // Copy the database file
            if (!copy($this->systemDbPath, $backupFile)) {
                throw new Exception('Failed to create backup file');
            }

            Logger::log('SYSTEM_BACKUP_CREATED', [
                'type' => 'manual',
                'file' => basename($backupFile),
                'size' => filesize($backupFile)
            ]);

            Auth::setFlashError(Lang::get('system_database.backup_created'), 'success');
        } catch (Exception $e) {
            Auth::setFlashError('Error creating backup: ' . $e->getMessage(), 'error');
        }

        $this->redirect('admin/system-database/backups');
    }

    /**
     * Restore from backup
     */
    /**
     * restoreBackup method
     *
     * @return void
     */
    public function restoreBackup()
    {
        $backupFile = $_POST['backup_file'] ?? '';

        if (empty($backupFile)) {
            Auth::setFlashError('Backup file not specified.', 'error');
            $this->redirect('admin/system-database/backups');
        }

        $backupPath = $this->backupDir . basename($backupFile);

        if (!file_exists($backupPath)) {
            Auth::setFlashError('Backup file not found.', 'error');
            $this->redirect('admin/system-database/backups');
        }

        try {
            // Create a safety backup before restoring
            $safetyBackup = $this->backupDir . "system_before_restore_" . date('Y-m-d_H-i-s') . ".sqlite";
            copy($this->systemDbPath, $safetyBackup);

            // Restore the backup
            if (!copy($backupPath, $this->systemDbPath)) {
                throw new Exception('Failed to restore backup');
            }

            Logger::log('SYSTEM_BACKUP_RESTORED', [
                'restored_from' => basename($backupFile),
                'safety_backup' => basename($safetyBackup)
            ]);

            Auth::setFlashError(Lang::get('system_database.backup_restored'), 'success');
        } catch (Exception $e) {
            Auth::setFlashError('Error restoring backup: ' . $e->getMessage(), 'error');
        }

        $this->redirect('admin/system-database/backups');
    }

    /**
     * Delete a backup
     */
    /**
     * deleteBackup method
     *
     * @return void
     */
    public function deleteBackup()
    {
        $backupFile = $_GET['file'] ?? '';

        if (empty($backupFile)) {
            Auth::setFlashError('Backup file not specified.', 'error');
            $this->redirect('admin/system-database/backups');
        }

        $backupPath = $this->backupDir . basename($backupFile);

        if (!file_exists($backupPath)) {
            Auth::setFlashError('Backup file not found.', 'error');
            $this->redirect('admin/system-database/backups');
        }

        try {
            unlink($backupPath);

            Logger::log('SYSTEM_BACKUP_DELETED', [
                'file' => basename($backupFile)
            ]);

            Auth::setFlashError('Backup deleted successfully.', 'success');
        } catch (Exception $e) {
            Auth::setFlashError('Error deleting backup: ' . $e->getMessage(), 'error');
        }

        $this->redirect('admin/system-database/backups');
    }

    /**
     * Download a backup file
     */
    /**
     * downloadBackup method
     *
     * @return void
     */
    public function downloadBackup()
    {
        $backupFile = $_GET['file'] ?? '';

        if (empty($backupFile)) {
            Auth::setFlashError('Backup file not specified.', 'error');
            $this->redirect('admin/system-database/backups');
        }

        $backupPath = $this->backupDir . basename($backupFile);

        if (!file_exists($backupPath)) {
            Auth::setFlashError('Backup file not found.', 'error');
            $this->redirect('admin/system-database/backups');
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($backupFile) . '"');
        header('Content-Length: ' . filesize($backupPath));
        readfile($backupPath);
        exit;
    }

    /**
     * View system logs with filters
     */
    /**
     * viewLogs method
     *
     * @return void
     */
    public function viewLogs()
    {
        $db = Database::getInstance()->getConnection();

        // Get filter parameters
        $action = $_GET['action'] ?? '';
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';
        $search = $_GET['search'] ?? '';

        // Build query
        $sql = "SELECT * FROM logs WHERE 1=1";
        $params = [];

        if (!empty($action)) {
            $sql .= " AND action LIKE ?";
            $params[] = "SYSTEM_%";
        }

        if (!empty($dateFrom)) {
            $sql .= " AND created_at >= ?";
            $params[] = $dateFrom . ' 00:00:00';
        }

        if (!empty($dateTo)) {
            $sql .= " AND created_at <= ?";
            $params[] = $dateTo . ' 23:59:59';
        }

        if (!empty($search)) {
            $sql .= " AND (action LIKE ? OR details LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " ORDER BY created_at DESC LIMIT 500";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/system_database/logs', [
            'title' => Lang::get('system_database.logs'),
            'logs' => $logs,
            'filters' => [
                'action' => $action,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'search' => $search
            ],
            'breadcrumbs' => [
                Lang::get('system_database.title') => 'admin/system-database',
                Lang::get('system_database.logs') => null
            ]
        ]);
    }

    /**
     * Export logs to CSV
     */
    /**
     * exportLogs method
     *
     * @return void
     */
    public function exportLogs()
    {
        $db = Database::getInstance()->getConnection();

        $stmt = $db->query("SELECT * FROM logs WHERE action LIKE 'SYSTEM_%' ORDER BY created_at DESC");
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        Logger::log('SYSTEM_LOGS_EXPORTED', [
            'count' => count($logs)
        ]);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="system_logs_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        // Header
        if (!empty($logs)) {
            fputcsv($output, array_keys($logs[0]));
        }

        // Data
        foreach ($logs as $log) {
            fputcsv($output, $log);
        }

        fclose($output);
        exit;
    }

    /**
     * Clear old logs
     */
    /**
     * clearLogs method
     *
     * @return void
     */
    public function clearLogs()
    {
        $db = Database::getInstance()->getConnection();

        $days = (int) ($_POST['days'] ?? 90);
        $cutoff = date('Y-m-d H:i:s', strtotime("-$days days"));

        try {
            $stmt = $db->prepare("DELETE FROM logs WHERE action LIKE 'SYSTEM_%' AND created_at < ?");
            $stmt->execute([$cutoff]);
            $deleted = $stmt->rowCount();

            Logger::log('SYSTEM_LOGS_CLEARED', [
                'deleted' => $deleted,
                'older_than_days' => $days
            ]);

            Auth::setFlashError("Cleared $deleted old log entries.", 'success');
        } catch (Exception $e) {
            Auth::setFlashError('Error clearing logs: ' . $e->getMessage(), 'error');
        }

        $this->redirect('admin/system-database/logs');
    }

    /**
     * API Explorer - Visual interface for testing System API endpoints
     */
    /**
     * apiExplorer method
     *
     * @return void
     */
    public function apiExplorer()
    {
        $db = Database::getInstance()->getConnection();

        // Get available API keys for the explorer
        // Since this is for Super Admin, we show all active keys
        $stmt = $db->query("SELECT * FROM api_keys WHERE status = 1");
        $apiKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Check if user is Super Admin
        $isSuperAdmin = Auth::isAdmin();

        $this->view('admin/system_database/api_explorer', [
            'title' => 'System API Explorer',
            'apiKeys' => $apiKeys,
            'isSuperAdmin' => $isSuperAdmin,
            'baseUrl' => Auth::getBaseUrl(),
            'breadcrumbs' => [
                Lang::get('system_database.title') => 'admin/system-database',
                'API Explorer' => null
            ]
        ]);
    }

    /**
     * Helper: Get list of backups with metadata
     */
    private function getBackupsList()
    {
        $backups = [];

        if (!is_dir($this->backupDir)) {
            return $backups;
        }

        $files = glob($this->backupDir . '*.sqlite');

        foreach ($files as $file) {
            $filename = basename($file);
            $type = strpos($filename, 'manual') !== false ? 'manual' : 'automatic';

            $backups[] = [
                'filename' => $filename,
                'path' => $file,
                'size' => $this->formatBytes(filesize($file)),
                'date' => date('Y-m-d H:i:s', filemtime($file)),
                'type' => $type
            ];
        }

        // Sort by date (newest first)
        usort($backups, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return $backups;
    }

    /**
     * Helper: Format bytes to human readable
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
