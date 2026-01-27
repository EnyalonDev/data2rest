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
 * 
 * Core Features:
 * - Create full system backups (ZIP format)
 * - List and manage existing backups
 * - Download backups locally
 * - Upload backups to cloud (Google Drive via Apps Script)
 * - Delete old backups
 * - Backup manifest generation
 * - Cloud URL configuration
 * 
 * Backup Contents:
 * - All SQLite database files (.sqlite)
 * - Manifest file with metadata
 * - Creation timestamp and creator info
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
 * @version 1.0.0
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
        $dataPath = dirname(Config::get('db_path')); // data/ folder

        $zip = new ZipArchive();
        if ($zip->open($filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {

            // Add all .sqlite files from data/
            foreach (glob($dataPath . '/*.sqlite') as $dbFile) {
                $zip->addFile($dbFile, basename($dbFile));
            }

            // Add uploads? Optional, might be too big. Let's just do databases for now as "Data Backup".
            // Adding a manifest
            $zip->addFromString('manifest.json', json_encode([
                'created_at' => date('c'),
                'version' => '1.0',
                'creator' => $_SESSION['username'] ?? 'system'
            ]));

            $zip->close();

            Logger::log('BACKUP_CREATED', ['file' => $filename]);
            $this->redirect('admin/backups'); // Redirect back with success
        } else {
            // Handle error
            die("Could not create zip");
        }
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
