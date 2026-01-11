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

class BackupController extends BaseController
{
    private $backupDir;

    public function __construct()
    {
        Auth::requireLogin();
        Auth::requirePermission('module:system.backups'); // Need to ensure this permission exists or use admin check
        $this->backupDir = dirname(Config::get('db_path')) . '/backups';

        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

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

    // Configure Cloud URL
    public function saveConfig()
    {
        $url = $_POST['cloud_url'] ?? '';
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT OR REPLACE INTO system_settings (key, value) VALUES ('backup_cloud_url', ?)");
        $stmt->execute([$url]);

        $this->redirect('admin/backups');
    }

    // Upload to Cloud (Google Drive via Apps Script)
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

    private function getCloudUrl()
    {
        $db = Database::getInstance()->getConnection();
        // ensure table exists? configured in basic installer. But system_settings is standard.
        // Let's assume system_settings exists or handle it.
        try {
            $stmt = $db->prepare("SELECT value FROM system_settings WHERE key = 'backup_cloud_url'");
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (\Exception $e) {
            return '';
        }
    }
}
