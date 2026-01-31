<?php

namespace App\Modules\Backups;

use App\Core\Auth;
use App\Core\BaseController;
use App\Core\Config;
use App\Core\Database;
use App\Core\Logger;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;


/**
 * Backup Controller
 * 
 * Comprehensive backup management system with local and cloud storage support.
 * Supports SQLite, MySQL, and PostgreSQL databases.
 * 
 * Core Features:
 * - Create full system backups (ZIP format)
 * - Backup system database (SQLite, MySQL, or PostgreSQL)
 * - Backup all client databases from databases table
 * - List and manage existing backups
 * - Download backups locally
 * - Upload backups to cloud (Google Drive via Apps Script)
 * - Delete old backups
 * - Backup manifest generation with success/failure tracking
 * - Cloud URL configuration
 * 
 * Backup Contents:
 * - System database (system.sqlite or system.sql)
 * - All client databases ([database_name].sqlite or [database_name].sql)
 * - Manifest file with metadata and backup statistics
 * 
 * Database Backup Methods:
 * - SQLite: Direct file copy
 * - MySQL: mysqldump command
 * - PostgreSQL: pg_dump command
 * 
 * Cloud Integration:
 * - Google Drive via Apps Script webhook
 * - Base64 encoding for transfer
 * - File size limit: 20MB for cloud sync
 * - Configurable cloud URL
 * 
 * Security:
 * - Login required
 * - Permission check: module:system.backups
 * - Path traversal prevention
 * - Activity logging
 * 
 * @package App\Modules\Backups
 * @author DATA2REST Development Team
 * @version 2.0.0
 */
/**
 * BackupController Controller
 *
 * Core Features: TODO
 *
 * Security: Requires login, permission checks as implemented.
 *
 * @package App\Modules\
 * @author DATA2REST Development Team
 * @version 1.0.0
 */
class BackupController extends BaseController
{
    /**
     * @var string Backup directory path
     */
    private $backupDir;

    /**
     * Constructor - Requires backup permissions
     * 
     * Initializes backup directory and ensures it exists.
     * Requires system.backups permission.
     */
    /**
     * __construct method
     *
     * @return void
     */
    public function __construct()
    {
        Auth::requireLogin();
        Auth::requirePermission('module:system.backups'); // Need to ensure this permission exists or use admin check
        $this->backupDir = dirname(Config::get('db_path')) . '/backups';

        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    /**
     * Display list of backups
     * 
     * Shows all available backups with metadata (name, size, date).
     * Sorted from newest to oldest.
     * 
     * Features:
     * - Lists all ZIP backups
     * - Shows file size and creation date
     * - Displays cloud URL configuration
     * 
     * @return void Renders backup list view
     * 
     * @example
     * GET /admin/backups
     */
    /**
     * index method
     *
     * @return void
     */
    public function index()
    {
        // Get list of backups
        $backups = [];
        foreach (glob($this->backupDir . '/*.zip') as $file) {
            $backups[] = [
                'name' => basename($file),
                'size' => filesize($file),
                'date' => filemtime($file),
                'path' => $file
            ];
        }

        // Sort new to old
        usort($backups, function ($a, $b) {
            return $b['date'] - $a['date'];
        });

        $this->view('admin/backups/index', [
            'title' => 'System Backups',
            'backups' => $backups,
            'cloud_url' => $this->getCloudUrl()
        ]);
    }

    /**
     * Create new backup
     * 
     * Creates a ZIP archive containing all SQLite databases
     * and a manifest file with metadata.
     * 
     * Backup Contents:
     * - All .sqlite files from data/ directory
     * - manifest.json with creation info
     * 
     * Naming: backup_YYYY-MM-DD_HH-mm-ss.zip
     * 
     * @return void Redirects to backup list
     * 
     * @example
     * POST /admin/backups/create
     */
    /**
     * create method
     *
     * @return void
     */
    public function create()
    {
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.zip';
        $filepath = $this->backupDir . '/' . $filename;
        $tempDir = sys_get_temp_dir() . '/backup_' . uniqid();

        // Create temporary directory for backup files
        if (!mkdir($tempDir, 0755, true)) {
            die("Could not create temporary directory");
        }

        $backedUpDatabases = 0;
        $failedDatabases = [];

        try {
            $zip = new ZipArchive();
            if ($zip->open($filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                throw new \Exception("Could not create zip file");
            }

            // 1. Backup system database
            try {
                $systemAdapter = Database::getInstance()->getAdapter();
                $systemType = $systemAdapter->getType();
                $extension = ($systemType === 'sqlite') ? 'sqlite' : 'sql';
                $systemBackupFile = $tempDir . '/system.' . $extension;

                if ($systemAdapter->createBackup($systemBackupFile)) {
                    $zip->addFile($systemBackupFile, basename($systemBackupFile));
                    $backedUpDatabases++;
                } else {
                    $failedDatabases[] = 'system';
                    error_log("Failed to backup system database");
                }
            } catch (\Exception $e) {
                $failedDatabases[] = 'system';
                error_log("System database backup error: " . $e->getMessage());
            }

            // 2. Backup all client databases
            $db = Database::getInstance()->getConnection();
            $adapter = Database::getInstance()->getAdapter();
            $qDatabases = $adapter->quoteName('databases');

            $stmt = $db->query("SELECT * FROM $qDatabases");
            $databases = $stmt->fetchAll();

            foreach ($databases as $database) {
                try {
                    $dbAdapter = \App\Core\DatabaseManager::getAdapter($database);
                    $dbType = $dbAdapter->getType();
                    $extension = ($dbType === 'sqlite') ? 'sqlite' : 'sql';

                    // Create safe filename
                    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $database['name']);
                    $backupFile = $tempDir . '/' . $safeName . '.' . $extension;

                    if ($dbAdapter->createBackup($backupFile)) {
                        $zip->addFile($backupFile, basename($backupFile));
                        $backedUpDatabases++;
                    } else {
                        $failedDatabases[] = $database['name'];
                        error_log("Failed to backup database: {$database['name']}");
                    }
                } catch (\Exception $e) {
                    $failedDatabases[] = $database['name'];
                    error_log("Database backup error for {$database['name']}: " . $e->getMessage());
                }
            }

            // 3. Add manifest with backup metadata
            $manifest = [
                'created_at' => date('c'),
                'version' => '2.0',
                'creator' => $_SESSION['username'] ?? 'system',
                'total_databases' => count($databases) + 1, // +1 for system
                'backed_up' => $backedUpDatabases,
                'failed' => count($failedDatabases),
                'failed_databases' => $failedDatabases
            ];

            $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
            $zip->close();

            // Cleanup temporary files
            $this->cleanupTempDir($tempDir);

            // Log the backup creation
            Logger::log('BACKUP_CREATED', [
                'file' => $filename,
                'databases' => $backedUpDatabases,
                'failed' => count($failedDatabases)
            ]);

            $this->redirect('admin/backups');

        } catch (\Exception $e) {
            // Cleanup on error
            if (is_dir($tempDir)) {
                $this->cleanupTempDir($tempDir);
            }
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            die("Backup failed: " . $e->getMessage());
        }
    }

    /**
     * Clean up temporary directory and its contents
     * 
     * @param string $dir Directory path to clean up
     * @return void
     */
    private function cleanupTempDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = glob($dir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($dir);
    }

    /**
     * Create backup with real-time progress updates
     * 
     * Uses Server-Sent Events (SSE) to stream progress to the client.
     * Shows which database is currently being backed up and overall progress.
     * 
     * @return void Streams SSE events
     * 
     * @example
     * GET /admin/backups/createWithProgress
     */
    public function createWithProgress()
    {
        // Set headers for Server-Sent Events
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // Disable nginx buffering

        // Disable output buffering
        if (ob_get_level())
            ob_end_clean();

        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.zip';
        $filepath = $this->backupDir . '/' . $filename;
        $tempDir = sys_get_temp_dir() . '/backup_' . uniqid();

        $this->sendSSE('progress', ['message' => 'Iniciando proceso de respaldo...', 'percent' => 0]);

        // Create temporary directory
        if (!mkdir($tempDir, 0755, true)) {
            $this->sendSSE('error', ['message' => 'No se pudo crear el directorio temporal']);
            return;
        }

        $backedUpDatabases = 0;
        $failedDatabases = [];
        $totalDatabases = 0;

        try {
            // Count total databases first
            $db = Database::getInstance()->getConnection();
            $adapter = Database::getInstance()->getAdapter();
            $qDatabases = $adapter->quoteName('databases');
            $stmt = $db->query("SELECT COUNT(*) FROM $qDatabases");
            $totalDatabases = $stmt->fetchColumn() + 1; // +1 for system database

            $this->sendSSE('progress', ['message' => "Total de bases de datos: {$totalDatabases}", 'percent' => 5]);

            $zip = new ZipArchive();
            if ($zip->open($filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                throw new \Exception("No se pudo crear el archivo ZIP");
            }

            // 1. Backup system database
            $this->sendSSE('progress', ['message' => 'Respaldando base de datos del sistema...', 'percent' => 10]);

            try {
                $systemAdapter = Database::getInstance()->getAdapter();
                $systemType = $systemAdapter->getType();
                $extension = ($systemType === 'sqlite') ? 'sqlite' : 'sql';
                $systemBackupFile = $tempDir . '/system.' . $extension;

                if ($systemAdapter->createBackup($systemBackupFile)) {
                    $zip->addFile($systemBackupFile, basename($systemBackupFile));
                    $backedUpDatabases++;
                    $this->sendSSE('success', ['message' => '✓ Sistema respaldado correctamente']);
                } else {
                    $failedDatabases[] = 'system';
                    $this->sendSSE('warning', ['message' => '✗ Falló el respaldo del sistema']);
                }
            } catch (\Exception $e) {
                $failedDatabases[] = 'system';
                $this->sendSSE('warning', ['message' => '✗ Error en sistema: ' . $e->getMessage()]);
            }

            // 2. Backup all client databases
            $stmt = $db->query("SELECT * FROM $qDatabases");
            $databases = $stmt->fetchAll();
            $currentDb = 1;

            foreach ($databases as $database) {
                $percentComplete = 10 + (($currentDb / $totalDatabases) * 80);
                $this->sendSSE('progress', [
                    'message' => "Respaldando: {$database['name']}...",
                    'percent' => round($percentComplete),
                    'current' => $currentDb,
                    'total' => $totalDatabases
                ]);

                try {
                    $dbAdapter = \App\Core\DatabaseManager::getAdapter($database);
                    $dbType = $dbAdapter->getType();
                    $extension = ($dbType === 'sqlite') ? 'sqlite' : 'sql';

                    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $database['name']);
                    $backupFile = $tempDir . '/' . $safeName . '.' . $extension;

                    if ($dbAdapter->createBackup($backupFile)) {
                        $zip->addFile($backupFile, basename($backupFile));
                        $backedUpDatabases++;
                        $this->sendSSE('success', ['message' => "✓ {$database['name']} ({$dbType})"]);
                    } else {
                        $failedDatabases[] = $database['name'];
                        $this->sendSSE('warning', ['message' => "✗ Falló: {$database['name']}"]);
                    }
                } catch (\Exception $e) {
                    $failedDatabases[] = $database['name'];
                    $this->sendSSE('warning', ['message' => "✗ Error en {$database['name']}: " . $e->getMessage()]);
                }

                $currentDb++;
            }

            // 3. Add manifest
            $this->sendSSE('progress', ['message' => 'Generando manifiesto...', 'percent' => 90]);

            $manifest = [
                'created_at' => date('c'),
                'version' => '2.0',
                'creator' => $_SESSION['username'] ?? 'system',
                'total_databases' => $totalDatabases,
                'backed_up' => $backedUpDatabases,
                'failed' => count($failedDatabases),
                'failed_databases' => $failedDatabases
            ];

            $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
            $zip->close();

            // Cleanup
            $this->sendSSE('progress', ['message' => 'Limpiando archivos temporales...', 'percent' => 95]);
            $this->cleanupTempDir($tempDir);

            // Log
            Logger::log('BACKUP_CREATED', [
                'file' => $filename,
                'databases' => $backedUpDatabases,
                'failed' => count($failedDatabases)
            ]);

            // Send completion
            $this->sendSSE('complete', [
                'message' => "Respaldo completado: {$backedUpDatabases}/{$totalDatabases} bases de datos",
                'percent' => 100,
                'filename' => $filename,
                'backed_up' => $backedUpDatabases,
                'failed' => count($failedDatabases)
            ]);

        } catch (\Exception $e) {
            if (is_dir($tempDir)) {
                $this->cleanupTempDir($tempDir);
            }
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            $this->sendSSE('error', ['message' => 'Error fatal: ' . $e->getMessage()]);
        }
    }

    /**
     * Send Server-Sent Event to client
     * 
     * @param string $event Event type (progress, success, warning, error, complete)
     * @param array $data Event data
     * @return void
     */
    private function sendSSE(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo "data: " . json_encode($data) . "\n\n";
        if (ob_get_level())
            ob_flush();
        flush();
    }

    /**
     * Download backup file
     * 
     * Serves a backup ZIP file for download.
     * 
     * Security:
     * - Path traversal prevention via basename()
     * - File existence validation
     * 
     * @return void Sends file download or redirects
     * 
     * @example
     * GET /admin/backups/download?file=backup_2026-01-16_06-30-00.zip
     */
    /**
     * download method
     *
     * @return void
     */
    public function download()
    {
        $file = $_GET['file'] ?? '';
        $path = $this->backupDir . '/' . basename($file);

        if (file_exists($path)) {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($path) . '"');
            header('Content-Length: ' . filesize($path));
            readfile($path);
            exit;
        }
        $this->redirect('admin/backups');
    }

    /**
     * Delete backup file
     * 
     * Removes a backup file from the system.
     * 
     * Security:
     * - Path traversal prevention
     * - Activity logging
     * 
     * @return void Redirects to backup list
     * 
     * @example
     * GET /admin/backups/delete?file=backup_2026-01-16_06-30-00.zip
     */
    /**
     * delete method
     *
     * @return void
     */
    public function delete()
    {
        $file = $_GET['file'] ?? '';
        $path = $this->backupDir . '/' . basename($file);

        if (file_exists($path)) {
            unlink($path);
            Logger::log('BACKUP_DELETED', ['file' => $file]);
        }
        $this->redirect('admin/backups');
    }

    /**
     * Save cloud configuration
     * 
     * Stores the Google Drive Apps Script webhook URL
     * for cloud backup uploads.
     * 
     * @return void Redirects to backup list
     * 
     * @example
     * POST /admin/backups/saveConfig
     * Body: cloud_url=https://script.google.com/...
     */
    /**
     * saveConfig method
     *
     * @return void
     */
    public function saveConfig()
    {
        $url = $_POST['cloud_url'] ?? '';
        $db = Database::getInstance()->getConnection();
        $adapter = Database::getInstance()->getAdapter();

        $sql = $adapter->getUpsertSQL('system_settings', ['key_name' => 'backup_cloud_url', 'value' => $url], 'key_name');
        $stmt = $db->prepare($sql);
        $stmt->execute(['backup_cloud_url', $url]);

        $this->redirect('admin/backups');
    }

    /**
     * Upload backup to cloud
     * 
     * Uploads a backup file to Google Drive via Apps Script webhook.
     * Uses Base64 encoding for file transfer.
     * 
     * Features:
     * - File size limit: 20MB
     * - Base64 encoding
     * - cURL with timeout (180s)
     * - Activity logging
     * 
     * Limitations:
     * - Google Apps Script has payload size limits
     * - For larger files, use rclone instead
     * 
     * @return void Outputs JSON response
     * 
     * @example
     * GET /admin/backups/uploadToCloud?file=backup_2026-01-16_06-30-00.zip
     * Response: {"success": true, "response": {...}}
     */
    /**
     * uploadToCloud method
     *
     * @return void
     */
    public function uploadToCloud()
    {
        // Increase limits for processing large files
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $file = $_GET['file'] ?? '';
        $path = $this->backupDir . '/' . basename($file);
        $url = $this->getCloudUrl();

        if (!$url) {
            return $this->json(['error' => 'Cloud URL not configured'], 400);
        }

        if (!file_exists($path)) {
            return $this->json(['error' => 'File not found'], 404);
        }

        // Read file and encode to Base64
        $fileContent = file_get_contents($path);

        // Ensure file is not too large (GAS has limits, e.g. 50MB)
        if (strlen($fileContent) > 20 * 1024 * 1024) {
            return $this->json(['error' => 'File too large for Google Apps Script sync (Max 20MB). Use rclone instead.'], 400);
        }

        $base64Data = base64_encode($fileContent);

        // Prepare JSON payload
        $payload = json_encode([
            'filename' => basename($path),
            'mimeType' => 'application/zip',
            'data' => $base64Data
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($code == 200 || $code == 302) {
            $json = json_decode($response, true);
            if (isset($json['status']) && $json['status'] === 'success') {
                Logger::log('BACKUP_UPLOADED', ['file' => $file, 'destination' => 'cloud']);
                return $this->json(['success' => true, 'response' => $json]);
            }
            // Fallback for HTML redirects
            return $this->json(['success' => true, 'msg' => 'Request sent, assume success if no error.'], 200);
        }

        return $this->json(['error' => 'Upload failed', 'code' => $code, 'curl_error' => $error, 'response' => $response], 500);
    }

    /**
     * Create backup from CLI (for cron jobs)
     * 
     * Similar to createWithProgress() but without SSE streaming.
     * Returns results array instead of streaming progress.
     * 
     * This method backs up:
     * - System database (SQLite, MySQL, or PostgreSQL)
     * - All client databases from databases table
     * - Creates manifest with metadata
     * 
     * @return array Backup results with filename, counts, and status
     * @throws \Exception If backup creation fails
     * 
     * @example
     * $controller = new BackupController();
     * $result = $controller->createBackupCLI();
     * echo "Backed up: {$result['backed_up']}/{$result['total']} databases\n";
     */
    public function createBackupCLI(): array
    {
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.zip';
        $filepath = $this->backupDir . '/' . $filename;
        $tempDir = sys_get_temp_dir() . '/backup_' . uniqid();

        // Create temporary directory
        if (!mkdir($tempDir, 0755, true)) {
            throw new \Exception('Could not create temporary directory');
        }

        $backedUpDatabases = 0;
        $failedDatabases = [];
        $totalDatabases = 0;

        try {
            // Count total databases first
            $db = Database::getInstance()->getConnection();
            $adapter = Database::getInstance()->getAdapter();
            $qDatabases = $adapter->quoteName('databases');
            $stmt = $db->query("SELECT COUNT(*) FROM $qDatabases");
            $totalDatabases = $stmt->fetchColumn() + 1; // +1 for system database

            $zip = new ZipArchive();
            if ($zip->open($filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                throw new \Exception("Could not create ZIP file");
            }

            // 1. Backup system database
            try {
                $systemAdapter = Database::getInstance()->getAdapter();
                $systemType = $systemAdapter->getType();
                $extension = ($systemType === 'sqlite') ? 'sqlite' : 'sql';
                $systemBackupFile = $tempDir . '/system.' . $extension;

                if ($systemAdapter->createBackup($systemBackupFile)) {
                    $zip->addFile($systemBackupFile, basename($systemBackupFile));
                    $backedUpDatabases++;
                } else {
                    $failedDatabases[] = 'system';
                }
            } catch (\Exception $e) {
                $failedDatabases[] = 'system';
                error_log("System database backup error: " . $e->getMessage());
            }

            // 2. Backup all client databases
            $stmt = $db->query("SELECT * FROM $qDatabases");
            $databases = $stmt->fetchAll();

            foreach ($databases as $database) {
                try {
                    $dbAdapter = \App\Core\DatabaseManager::getAdapter($database);
                    $dbType = $dbAdapter->getType();
                    $extension = ($dbType === 'sqlite') ? 'sqlite' : 'sql';

                    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $database['name']);
                    $backupFile = $tempDir . '/' . $safeName . '.' . $extension;

                    if ($dbAdapter->createBackup($backupFile)) {
                        $zip->addFile($backupFile, basename($backupFile));
                        $backedUpDatabases++;
                    } else {
                        $failedDatabases[] = $database['name'];
                    }
                } catch (\Exception $e) {
                    $failedDatabases[] = $database['name'];
                    error_log("Database backup error for {$database['name']}: " . $e->getMessage());
                }
            }

            // 3. Add manifest
            $manifest = [
                'created_at' => date('c'),
                'version' => '2.0',
                'creator' => 'cli',
                'total_databases' => $totalDatabases,
                'backed_up' => $backedUpDatabases,
                'failed' => count($failedDatabases),
                'failed_databases' => $failedDatabases
            ];

            $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
            $zip->close();

            // Cleanup
            $this->cleanupTempDir($tempDir);

            // Log
            try {
                Logger::log('BACKUP_CREATED_CLI', [
                    'file' => $filename,
                    'databases' => $backedUpDatabases,
                    'failed' => count($failedDatabases)
                ]);
            } catch (\Exception $e) {
                // Ignore logging errors in CLI
            }

            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'total' => $totalDatabases,
                'backed_up' => $backedUpDatabases,
                'failed' => count($failedDatabases),
                'failed_databases' => $failedDatabases,
                'size' => filesize($filepath)
            ];

        } catch (\Exception $e) {
            // Cleanup on error
            if (is_dir($tempDir)) {
                $this->cleanupTempDir($tempDir);
            }
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            throw $e;
        }
    }

    /**
     * Get configured cloud URL
     * 
     * Retrieves the Google Drive Apps Script webhook URL
     * from system settings.
     * 
     * @return string Cloud URL or empty string if not configured
     */
    private function getCloudUrl()
    {
        $db = Database::getInstance()->getConnection();
        // ensure table exists? configured in basic installer. But system_settings is standard.
        // Let's assume system_settings exists or handle it.
        try {
            $adapter = Database::getInstance()->getAdapter();
            $stmt = $db->prepare("SELECT value FROM system_settings WHERE key_name = 'backup_cloud_url'");
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (\Exception $e) {
            return '';
        }
    }
}
