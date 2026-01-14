<?php

namespace App\Modules\SystemDatabase;

use App\Core\Database;
use App\Core\BaseController;
use App\Core\Logger;
use App\Core\Auth;
use PDO;

/**
 * API Controller for System Database Operations
 * 
 * All endpoints require a valid API Key from a Super Admin user.
 * This ensures only authorized administrators can perform system-level operations via API.
 */
class SystemDatabaseApiController extends BaseController
{
    private $apiKeyData;
    private $systemDbPath;
    private $backupDir;

    public function __construct()
    {
        $this->systemDbPath = realpath(__DIR__ . '/../../../data/system.sqlite') ?: __DIR__ . '/../../../data/system.sqlite';
        $this->backupDir = __DIR__ . '/../../../data/backups/system/';

        // Ensure backup directory exists
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0777, true);
        }
    }

    /**
     * Authenticate and verify Super Admin API Key
     * 
     * @return array API Key data if valid
     */
    private function authenticateSuperAdmin()
    {
        // Get API Key from header or query parameter
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $apiKey = $headers['X-API-KEY'] ?? $headers['X-API-Key'] ?? $headers['x-api-key'] ??
            $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? null;

        if (!$apiKey) {
            $this->json(['error' => 'API Key required (X-API-KEY header or api_key param)'], 401);
        }

        // Validate API Key
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT ak.*, u.id as user_id, u.role_id 
                              FROM api_keys ak 
                              LEFT JOIN users u ON ak.user_id = u.id 
                              WHERE ak.key_value = ? AND ak.status = 1");
        $stmt->execute([$apiKey]);
        $keyData = $stmt->fetch();

        if (!$keyData) {
            $this->json(['error' => 'Invalid or inactive API Key'], 403);
        }

        // Check if user is Super Admin
        if (!empty($keyData['role_id'])) {
            $stmtRole = $db->prepare("SELECT permissions FROM roles WHERE id = ?");
            $stmtRole->execute([$keyData['role_id']]);
            $role = $stmtRole->fetch();

            if ($role) {
                $permissions = json_decode($role['permissions'], true);
                if (!isset($permissions['all']) || $permissions['all'] !== true) {
                    $this->json(['error' => 'Access Denied: Super Admin privileges required'], 403);
                }
            } else {
                $this->json(['error' => 'Access Denied: Invalid role'], 403);
            }
        } else {
            $this->json(['error' => 'Access Denied: No role assigned'], 403);
        }

        header('X-Data2Rest-Auth: API-Key-SuperAdmin');
        return $keyData;
    }

    /**
     * GET /api/system/info
     * 
     * Get system database information
     */
    public function getInfo()
    {
        $this->apiKeyData = $this->authenticateSuperAdmin();

        try {
            $db = Database::getInstance()->getConnection();

            // Get database size
            $dbSize = file_exists($this->systemDbPath) ? filesize($this->systemDbPath) : 0;

            // Get table count
            $stmt = $db->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            $tableCount = $stmt->fetchColumn();

            // Get total records (approximate)
            $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $totalRecords = 0;

            foreach ($tables as $table) {
                try {
                    $countStmt = $db->query("SELECT COUNT(*) FROM `$table`");
                    $totalRecords += $countStmt->fetchColumn();
                } catch (\Exception $e) {
                    // Skip if table can't be counted
                }
            }

            // Get last backup info
            $backups = $this->getBackupsList();
            $lastBackup = !empty($backups) ? $backups[0] : null;

            // Get disk space
            $diskTotal = disk_total_space(dirname($this->systemDbPath));
            $diskFree = disk_free_space(dirname($this->systemDbPath));
            $diskUsed = $diskTotal - $diskFree;

            Logger::log('SYSTEM_API_INFO', [
                'api_key' => $this->apiKeyData['name'] ?? 'Unknown'
            ]);

            $usedPercent = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 2) : 0;

            $this->json([
                'success' => true,
                'data' => [
                    'database_size' => $this->formatBytes($dbSize),
                    'database_size_bytes' => $dbSize,
                    'total_tables' => (int) $tableCount,
                    'total_records' => $totalRecords,
                    'last_backup' => $lastBackup,
                    'disk_space' => [
                        'total' => $this->formatBytes($diskTotal),
                        'used' => $this->formatBytes($diskUsed),
                        'free' => $this->formatBytes($diskFree),
                        'used_percent' => $usedPercent
                    ]
                ]
            ]);

        } catch (\Throwable $e) {
            $this->json(['error' => 'Failed to get system info: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/system/backup
     * 
     * Create a new backup of the system database
     */
    public function createBackup()
    {
        $this->apiKeyData = $this->authenticateSuperAdmin();

        try {
            $timestamp = date('Y-m-d_H-i-s');
            $backupFile = $this->backupDir . "system_api_{$timestamp}.sqlite";

            if (!copy($this->systemDbPath, $backupFile)) {
                throw new \Exception('Failed to create backup file');
            }

            $backupSize = filesize($backupFile);

            Logger::log('SYSTEM_BACKUP_CREATED', [
                'type' => 'api',
                'file' => basename($backupFile),
                'size' => $backupSize,
                'api_key' => $this->apiKeyData['name'] ?? 'Unknown'
            ]);

            $this->json([
                'success' => true,
                'message' => 'Backup created successfully',
                'data' => [
                    'filename' => basename($backupFile),
                    'size' => $this->formatBytes($backupSize),
                    'size_bytes' => $backupSize,
                    'created_at' => date('Y-m-d H:i:s'),
                    'type' => 'api'
                ]
            ], 201);

        } catch (\Throwable $e) {
            $this->json(['error' => 'Failed to create backup: ' . $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/system/backups
     * 
     * List all available backups
     */
    public function listBackups()
    {
        $this->apiKeyData = $this->authenticateSuperAdmin();

        try {
            $backups = $this->getBackupsList();

            $this->json([
                'success' => true,
                'data' => $backups,
                'count' => count($backups)
            ]);

        } catch (\Throwable $e) {
            $this->json(['error' => 'Failed to list backups: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/system/optimize
     * 
     * Optimize the system database (VACUUM + ANALYZE)
     */
    public function optimize()
    {
        $this->apiKeyData = $this->authenticateSuperAdmin();

        try {
            $db = Database::getInstance()->getConnection();

            // Execute VACUUM
            $db->exec('VACUUM');

            // Execute ANALYZE
            $db->exec('ANALYZE');

            Logger::log('SYSTEM_DATABASE_OPTIMIZED', [
                'api_key' => $this->apiKeyData['name'] ?? 'Unknown'
            ]);

            $this->json([
                'success' => true,
                'message' => 'Database optimized successfully',
                'operations' => ['VACUUM', 'ANALYZE']
            ]);

        } catch (\Throwable $e) {
            $this->json(['error' => 'Failed to optimize database: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/system/query
     * 
     * Execute a SELECT query on the system database
     * Only SELECT queries are allowed for security
     */
    public function executeQuery()
    {
        $this->apiKeyData = $this->authenticateSuperAdmin();

        $input = json_decode(file_get_contents('php://input'), true);
        $query = $input['query'] ?? '';

        if (empty($query)) {
            $this->json(['error' => 'Query is required'], 400);
        }

        // Only allow SELECT queries
        $queryUpper = strtoupper(trim($query));
        if (!preg_match('/^SELECT\s+/i', $queryUpper)) {
            $this->json(['error' => 'Only SELECT queries are allowed via API'], 403);
        }

        // Check for dangerous patterns
        $dangerousPatterns = ['/DROP\s+/i', '/TRUNCATE\s+/i', '/DELETE\s+/i', '/UPDATE\s+/i', '/INSERT\s+/i', '/ALTER\s+/i'];
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $query)) {
                $this->json(['error' => 'Query contains forbidden operations'], 403);
            }
        }

        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare($query);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Logger::log('SYSTEM_QUERY_EXECUTED', [
                'query' => substr($query, 0, 500),
                'rows_returned' => count($results),
                'api_key' => $this->apiKeyData['name'] ?? 'Unknown'
            ]);

            $this->json([
                'success' => true,
                'data' => $results,
                'count' => count($results)
            ]);

        } catch (\Throwable $e) {
            $this->json(['error' => 'Query execution failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/system/tables
     * 
     * List all system tables with statistics
     */
    public function listTables()
    {
        $this->apiKeyData = $this->authenticateSuperAdmin();

        try {
            $db = Database::getInstance()->getConnection();

            $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
            $tableNames = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $tables = [];
            foreach ($tableNames as $tableName) {
                try {
                    $countStmt = $db->query("SELECT COUNT(*) FROM `$tableName`");
                    $recordCount = $countStmt->fetchColumn();

                    $tables[] = [
                        'name' => $tableName,
                        'records' => (int) $recordCount
                    ];
                } catch (\Exception $e) {
                    $tables[] = [
                        'name' => $tableName,
                        'records' => 0,
                        'error' => 'Could not count records'
                    ];
                }
            }

            $this->json([
                'success' => true,
                'data' => $tables,
                'count' => count($tables)
            ]);

        } catch (\Throwable $e) {
            $this->json(['error' => 'Failed to list tables: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Helper: Get list of backups with metadata
     */
    private function getBackupsList()
    {
        $files = glob($this->backupDir . 'system_*.sqlite');
        $backups = [];

        foreach ($files as $file) {
            $filename = basename($file);
            $backups[] = [
                'filename' => $filename,
                'size' => $this->formatBytes(filesize($file)),
                'size_bytes' => filesize($file),
                'date' => date('Y-m-d H:i:s', filemtime($file)),
                'timestamp' => filemtime($file),
                'type' => $this->getBackupType($filename)
            ];
        }

        // Sort by date (newest first)
        usort($backups, function ($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });

        return $backups;
    }

    /**
     * Helper: Determine backup type from filename
     */
    private function getBackupType($filename)
    {
        if (strpos($filename, 'system_manual_') === 0)
            return 'manual';
        if (strpos($filename, 'system_auto_') === 0)
            return 'automatic';
        if (strpos($filename, 'system_api_') === 0)
            return 'api';
        if (strpos($filename, 'system_before_restore_') === 0)
            return 'pre-restore';
        return 'unknown';
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
