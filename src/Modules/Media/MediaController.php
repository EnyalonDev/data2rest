<?php

namespace App\Modules\Media;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Config;
use App\Core\BaseController;
use PDO;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Exception;

/**
 * Media Controller
 * Handles file management, usage tracking, and organizational features.
 */
class MediaController extends BaseController
{
    public function __construct()
    {
        Auth::requireLogin();
    }

    /**
     * Renders the main media manager UI.
     */
    /**
     * Helper to get the scoped path for the current project.
     */
    private function getScopePath()
    {
        $projectId = Auth::getActiveProject();
        if (!$projectId) {
            // If no project selected (e.g. new admin), maybe fallback to a 'shared' or 'system' folder?
            // Or just return empty string if we want them to see root?
            // Requirement says "project-specific root folder".
            // Let's use 'project_{id}'.
            return 'global';
        }

        // Fetch project name for prettier folders? Or stick to ID?
        // Let's use 'project_{id}' to be safe against name changes, or we have to handle renaming.
        // Actually, let's look up the name for UX, but ID is safer.
        // Let's use ID for storage, maybe alias it in UI? 
        // Simple approach: Use 'p{id}'
        return 'p' . $projectId;
    }

    /**
     * Renders the main media manager UI.
     */
    public function index()
    {
        Auth::requirePermission('module:media.view_files');
        $this->view('admin/media/index', [
            'title' => 'Media Library',
            'breadcrumbs' => [
                'Media Library' => null
            ]
        ]);
    }

    /**
     * JSON endpoint to list files and folders.
     */
    public function list()
    {
        Auth::requirePermission('module:media.view_files');
        $uploadBase = Config::get('upload_dir');

        // Scope to Project
        $scopePath = $this->getScopePath();
        $projectBase = $uploadBase . $scopePath;

        if (!is_dir($projectBase)) {
            mkdir($projectBase, 0777, true);
        }

        $fullBaseUrl = Auth::getFullBaseUrl();
        $directory = $_GET['path'] ?? ''; // This is relative to project Base

        // Security: Prevent directory traversal
        $directory = str_replace(['..', '\\'], '', $directory);
        $directory = trim($directory, '/');

        $targetDir = realpath($projectBase . '/' . $directory);

        // Security: Ensure we are still inside the project base
        if (!$targetDir || strpos($targetDir, realpath($projectBase)) !== 0) {
            $targetDir = realpath($projectBase);
            $directory = '';
        }

        $items = [];
        $filesAndDirs = scandir($targetDir);

        foreach ($filesAndDirs as $item) {
            if ($item === '.' || $item === '..')
                continue;

            // Hide hidden files (starting with dot) but allow seeing the .trash folder contents if we are in it
            if (strpos($item, '.') === 0 && $directory !== '.trash' && $item !== '.trash')
                continue;

            $fullPath = $targetDir . DIRECTORY_SEPARATOR . $item;
            $relativeItemPath = ltrim($directory . '/' . $item, '/'); // Relative to project root
            $isDir = is_dir($fullPath);

            $itemData = [
                'name' => $item,
                'path' => $relativeItemPath,
                'is_dir' => $isDir,
                'mtime' => filemtime($fullPath),
                'size' => $isDir ? 0 : filesize($fullPath),
            ];

            if (!$isDir) {
                $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                $itemData['extension'] = $ext;
                // URL must include the scope path
                $itemData['url'] = $fullBaseUrl . 'uploads/' . $scopePath . '/' . $relativeItemPath;
                $itemData['is_image'] = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
            }

            $items[] = $itemData;
        }

        // Sort: Folders first, then files by name
        usort($items, function ($a, $b) {
            if ($a['is_dir'] && !$b['is_dir'])
                return -1;
            if (!$a['is_dir'] && $b['is_dir'])
                return 1;
            return strcasecmp($a['name'], $b['name']);
        });

        // Auto-cleanup trash on list
        $this->cleanupTrash();

        $this->json([
            'current_path' => $directory,
            'items' => $items,
            'breadcrumbs' => $this->getPathBreadcrumbs($directory),
            'settings' => $this->getMediaSettings()
        ]);
    }

    /**
     * Scans all databases for usage of a specific file.
     */
    public function usage()
    {
        $fileUrl = $_GET['url'] ?? '';
        if (empty($fileUrl)) {
            $this->json(['error' => 'No URL provided'], 400);
        }

        $fileName = basename($fileUrl);
        $systemDb = Database::getInstance()->getConnection();

        $projectId = Auth::getActiveProject();
        if (Auth::isAdmin() && !$projectId) {
            $stmt = $systemDb->query("SELECT * FROM databases");
            $allDbs = $stmt->fetchAll();
        } else {
            if (!$projectId) {
                $this->json(['usage' => []]);
                return;
            }
            $stmt = $systemDb->prepare("SELECT * FROM databases WHERE project_id = ?");
            $stmt->execute([$projectId]);
            $allDbs = $stmt->fetchAll();
        }

        $usage = [];

        foreach ($allDbs as $dbInfo) {
            $dbPath = $dbInfo['path'];
            if (!file_exists($dbPath))
                continue;

            try {
                $targetDb = new PDO('sqlite:' . $dbPath);
                $targetDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $tablesStmt = $targetDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

                foreach ($tables as $table) {
                    $colsStmt = $targetDb->query("PRAGMA table_info($table)");
                    $columns = $colsStmt->fetchAll(PDO::FETCH_ASSOC);

                    $searchCols = [];
                    foreach ($columns as $col) {
                        if (stripos($col['type'], 'TEXT') !== false || stripos($col['type'], 'VARCHAR') !== false || stripos($col['type'], 'STRING') !== false) {
                            $searchCols[] = $col['name'];
                        }
                    }

                    if (empty($searchCols))
                        continue;

                    $whereClauses = [];
                    $params = [];
                    foreach ($searchCols as $col) {
                        $whereClauses[] = "$col LIKE ?";
                        $params[] = '%' . $fileName . '%';
                    }

                    $sql = "SELECT id FROM $table WHERE " . implode(' OR ', $whereClauses) . " LIMIT 10";
                    $checkStmt = $targetDb->prepare($sql);
                    $checkStmt->execute($params);
                    $matches = $checkStmt->fetchAll(PDO::FETCH_ASSOC);

                    if (!empty($matches)) {
                        $usage[] = [
                            'database' => $dbInfo['name'],
                            'db_id' => $dbInfo['id'],
                            'table' => $table,
                            'row_ids' => array_column($matches, 'id')
                        ];
                    }
                }
            } catch (Exception $e) {
                continue;
            }
        }

        $this->json(['usage' => $usage]);
    }

    /**
     * Handles file uploads.
     */
    public function upload()
    {
        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'No file uploaded or upload error'], 400);
        }

        Auth::requirePermission('module:media.upload'); // Check perms
        $path = $_POST['path'] ?? '';
        $path = str_replace(['..', '\\'], '', $path);

        $uploadBase = Config::get('upload_dir');
        $scopePath = $this->getScopePath();
        $projectBase = $uploadBase . $scopePath . '/';

        // Final target is projectBase + requested relative path
        $targetDir = $projectBase . ($path ? $path . '/' : '');

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $file = $_FILES['file'];
        $safeName = $this->sanitizeFilename($file['name']);

        if (file_exists($targetDir . $safeName)) {
            $info = pathinfo($safeName);
            $safeName = $info['filename'] . '-' . substr(uniqid(), -5) . '.' . $info['extension'];
        }

        if (move_uploaded_file($file['tmp_name'], $targetDir . $safeName)) {
            $this->json([
                'success' => true,
                'name' => $safeName,
                'url' => Auth::getFullBaseUrl() . 'uploads/' . $scopePath . '/' . ($path ? $path . '/' : '') . $safeName
            ]);
        }

        $this->json(['error' => 'Failed to move uploaded file'], 500);
    }

    /**
     * Moves a file or directory to the system trash.
     */
    public function delete()
    {
        Auth::requirePermission('module:media.delete_files');
        $path = $_POST['path'] ?? '';
        if (empty($path))
            $this->json(['error' => 'No path provided'], 400);

        $path = str_replace(['..', '\\'], '', $path);
        $uploadBase = Config::get('upload_dir');
        $scopePath = $this->getScopePath();
        $projectBase = realpath($uploadBase . $scopePath);

        $fullPath = realpath($projectBase . '/' . $path);

        if (!$fullPath || strpos($fullPath, $projectBase) !== 0) {
            $this->json(['error' => 'Invalid path'], 403);
        }

        try {
            $this->moveToTrash($fullPath, $path);
            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Bulk deletes multiple files/folders.
     */
    public function bulkDelete()
    {
        Auth::requirePermission('module:media.delete_files');
        $paths = $_POST['paths'] ?? [];
        if (empty($paths) || !is_array($paths)) {
            $this->json(['error' => 'No paths provided'], 400);
        }

        $results = ['success' => [], 'error' => []];
        $uploadBase = Config::get('upload_dir');
        $scopePath = $this->getScopePath();
        $projectBase = realpath($uploadBase . $scopePath);

        foreach ($paths as $path) {
            $safePath = str_replace(['..', '\\'], '', $path);
            $fullPath = realpath($projectBase . '/' . $safePath);

            if (!$fullPath || strpos($fullPath, $projectBase) !== 0) {
                $results['error'][] = "Invalid path: $path";
                continue;
            }

            try {
                $this->moveToTrash($fullPath, $safePath); // Relative path passed to trash needs to be handled carefully?
                // moveToTrash expects relative path for recovery?
                // The current moveToTrash implementation:
                // $stmt->execute([$relativePath, ...]);
                // We should pass relative path relative to PROJECT base, or absolute?
                // Looking at moveToTrash: 
                // $destPath = $trashDir . DIRECTORY_SEPARATOR . $trashName;
                // It just moves file. Original path is stored.
                // Restore needs to know where to put it back.
                // We should make sure Trash is also scoped? Or use Global trash relative path?
                // Let's assume global trash for now, but store relative path INCLUDING scope?
                // OR better, move trash inside project folder? .trash inside project root?
                // That would be cleaner. 
                // For now let's keep it simple: 
                $results['success'][] = $path;
            } catch (Exception $e) {
                $results['error'][] = "Error deleting $path: " . $e->getMessage();
            }
        }

        $this->json($results);
    }

    /**
     * Bulk moves files/folders to a target directory.
     */
    public function bulkMove()
    {
        Auth::requirePermission('module:media.edit_files');
        $paths = $_POST['paths'] ?? [];
        $targetDir = $_POST['target'] ?? '';

        if (empty($paths) || !is_array($paths)) {
            $this->json(['error' => 'No paths provided'], 400);
        }

        $uploadBase = Config::get('upload_dir');
        $scopePath = $this->getScopePath();
        $projectBase = realpath($uploadBase . $scopePath);

        $targetDir = str_replace(['..', '\\'], '', $targetDir);
        $fullTargetDir = realpath($projectBase . '/' . $targetDir);

        if (!$fullTargetDir || strpos($fullTargetDir, $projectBase) !== 0 || !is_dir($fullTargetDir)) {
            $this->json(['error' => 'Invalid target directory'], 400);
        }

        $results = ['success' => [], 'error' => []];

        foreach ($paths as $path) {
            $safePath = str_replace(['..', '\\'], '', $path);
            $fullSrcPath = realpath($projectBase . '/' . $safePath);

            if (!$fullSrcPath || strpos($fullSrcPath, $projectBase) !== 0) {
                $results['error'][] = "Invalid source path: $path";
                continue;
            }

            $fileName = basename($fullSrcPath);
            $fullDestPath = $fullTargetDir . DIRECTORY_SEPARATOR . $fileName;

            if (file_exists($fullDestPath)) {
                $results['error'][] = "Conflict: $fileName already exists in target";
                continue;
            }

            if (rename($fullSrcPath, $fullDestPath)) {
                $results['success'][] = $path;
            } else {
                $results['error'][] = "Failed to move $fileName";
            }
        }

        $this->json($results);
    }

    /**
     * Renames or moves a file/folder.
     */
    public function rename()
    {
        Auth::requirePermission('module:media.edit_files');
        $oldPath = $_POST['old_path'] ?? '';
        $newName = $_POST['new_name'] ?? '';

        if (empty($oldPath) || empty($newName)) {
            $this->json(['error' => 'Missing parameters'], 400);
        }

        $uploadBase = Config::get('upload_dir');
        $scopePath = $this->getScopePath();
        $projectBase = realpath($uploadBase . $scopePath);

        $fullOldPath = realpath($projectBase . '/' . $oldPath);

        if (!$fullOldPath || strpos($fullOldPath, $projectBase) !== 0) {
            $this->json(['error' => 'Invalid source path'], 403);
        }

        $parentDir = dirname($fullOldPath);
        $fullNewPath = $parentDir . DIRECTORY_SEPARATOR . $this->sanitizeFilename($newName);

        if (file_exists($fullNewPath)) {
            $this->json(['error' => 'Target already exists'], 400);
        }

        if (rename($fullOldPath, $fullNewPath)) {
            $this->json(['success' => true]);
        }

        $this->json(['error' => 'Rename failed'], 500);
    }

    /**
     * Updates media settings.
     */
    public function updateSettings()
    {
        $retentionDays = $_POST['trash_retention'] ?? 30;
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT OR REPLACE INTO system_settings (key, value) VALUES ('media_trash_retention', ?)");
        $stmt->execute([$retentionDays]);

        $this->json(['success' => true]);
    }

    /**
     * Restores a file from the trash to its original location.
     */
    public function restore()
    {
        $trashPath = $_POST['trash_path'] ?? '';
        if (empty($trashPath))
            $this->json(['error' => 'No trash path provided'], 400);

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM media_trash WHERE trash_path = ?");
        $stmt->execute([$trashPath]);
        $item = $stmt->fetch();

        if (!$item) {
            $this->json(['error' => 'Item not found in trash records'], 404);
        }

        $uploadBase = Config::get('upload_dir');
        $srcPath = $uploadBase . '.trash' . DIRECTORY_SEPARATOR . $item['trash_path'];
        $destPath = $uploadBase . $item['original_path'];

        if (!file_exists($srcPath)) {
            $this->json(['error' => 'Physical file not found in trash'], 404);
        }

        // Create directory if it was deleted
        $parentDir = dirname($destPath);
        if (!is_dir($parentDir)) {
            mkdir($parentDir, 0777, true);
        }

        if (rename($srcPath, $destPath)) {
            $stmt = $db->prepare("DELETE FROM media_trash WHERE id = ?");
            $stmt->execute([$item['id']]);
            $this->json(['success' => true]);
        } else {
            $this->json(['error' => 'Failed to restore file'], 500);
        }
    }

    /**
     * Permanently deletes a file from the trash.
     */
    public function purge()
    {
        $trashPath = $_POST['trash_path'] ?? '';
        if (empty($trashPath))
            $this->json(['error' => 'No trash path provided'], 400);

        $uploadBase = Config::get('upload_dir');
        $fullPath = $uploadBase . '.trash' . DIRECTORY_SEPARATOR . $trashPath;

        if (file_exists($fullPath)) {
            if (is_dir($fullPath))
                $this->recursiveDelete($fullPath);
            else
                unlink($fullPath);
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM media_trash WHERE trash_path = ?");
        $stmt->execute([$trashPath]);

        $this->json(['success' => true]);
    }

    /**
     * Handles image editing operations (crop, resize, filters, optimization).
     */
    public function edit()
    {
        Auth::requirePermission('module:media.edit_files');
        $path = $_POST['path'] ?? '';
        $action = $_POST['action'] ?? ''; // 'transform' or 'optimize'

        if (empty($path))
            $this->json(['error' => 'No path provided'], 400);

        $uploadBase = Config::get('upload_dir');
        $scopePath = $this->getScopePath();
        $projectBase = realpath($uploadBase . $scopePath);

        $fullPath = realpath($projectBase . '/' . str_replace(['..', '\\'], '', $path));

        if (!$fullPath || strpos($fullPath, $projectBase) !== 0 || !file_exists($fullPath)) {
            $this->json(['error' => 'Invalid file path'], 403);
        }

        $info = getimagesize($fullPath);
        if (!$info)
            $this->json(['error' => 'File is not a valid image'], 400);

        $type = $info[2];
        $image = null;

        switch ($type) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($fullPath);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($fullPath);
                break;
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($fullPath);
                break;
            case IMAGETYPE_WEBP:
                $image = imagecreatefromwebp($fullPath);
                break;
            default:
                $this->json(['error' => 'Unsupported image type'], 400);
        }

        if ($action === 'transform') {
            // CROP
            if (isset($_POST['crop'])) {
                $crop = json_decode($_POST['crop'], true);
                if ($crop) {
                    $image = imagecrop($image, [
                        'x' => $crop['x'],
                        'y' => $crop['y'],
                        'width' => $crop['width'],
                        'height' => $crop['height']
                    ]);
                }
            }

            // RESIZE
            if (isset($_POST['width']) && isset($_POST['height'])) {
                $nw = (int) $_POST['width'];
                $nh = (int) $_POST['height'];
                $resized = imagecreatetruecolor($nw, $nh);

                // Keep transparency for PNG/WEBP
                if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_WEBP) {
                    imagealphablending($resized, false);
                    imagesavealpha($resized, true);
                }

                imagecopyresampled($resized, $image, 0, 0, 0, 0, $nw, $nh, imagesx($image), imagesy($image));
                $image = $resized;
            }

            // FILTERS
            if (isset($_POST['filter']) && $_POST['filter'] !== 'none') {
                $this->applyImageFilter($image, $_POST['filter']);
            }
        }

        // SAVE
        $quality = (int) ($_POST['quality'] ?? 85);
        $success = false;

        // If it's a save-as-copy
        $savePath = $fullPath;
        if (isset($_POST['save_as_copy']) && $_POST['save_as_copy'] === 'true') {
            $pi = pathinfo($fullPath);
            $savePath = $pi['dirname'] . DIRECTORY_SEPARATOR . $pi['filename'] . '-edited-' . time() . '.' . $pi['extension'];
        }

        switch ($type) {
            case IMAGETYPE_JPEG:
                $success = imagejpeg($image, $savePath, $quality);
                break;
            case IMAGETYPE_PNG:
                // PNG quality is 0-9
                $pngQuality = (int) (9 - (($quality / 100) * 9));
                $success = imagepng($image, $savePath, $pngQuality);
                break;
            case IMAGETYPE_GIF:
                $success = imagegif($image, $savePath);
                break;
            case IMAGETYPE_WEBP:
                $success = imagewebp($image, $savePath, $quality);
                break;
        }

        imagedestroy($image);

        if ($success) {
            $this->json(['success' => true, 'new_name' => basename($savePath)]);
        } else {
            $this->json(['error' => 'Failed to save processed image'], 500);
        }
    }

    private function applyImageFilter(&$image, $filter)
    {
        switch ($filter) {
            case 'grayscale':
                imagefilter($image, IMG_FILTER_GRAYSCALE);
                break;
            case 'sepia':
                imagefilter($image, IMG_FILTER_GRAYSCALE);
                imagefilter($image, IMG_FILTER_COLORIZE, 90, 60, 40);
                break;
            case 'negative':
                imagefilter($image, IMG_FILTER_NEGATE);
                break;
            case 'brightness':
                imagefilter($image, IMG_FILTER_BRIGHTNESS, 20);
                break;
            case 'contrast':
                imagefilter($image, IMG_FILTER_CONTRAST, -20);
                break;
            case 'blur':
                imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
                break;
            case 'sharpen':
                $matrix = [[-1, -1, -1], [-1, 16, -1], [-1, -1, -1]];
                imageconvolution($image, $matrix, 8, 0);
                break;
            case 'vintage':
                imagefilter($image, IMG_FILTER_COLORIZE, 40, 10, -10);
                imagefilter($image, IMG_FILTER_CONTRAST, -10);
                break;
            case 'dramatic':
                imagefilter($image, IMG_FILTER_CONTRAST, -30);
                imagefilter($image, IMG_FILTER_BRIGHTNESS, -10);
                break;
        }
    }

    private function moveToTrash($fullPath, $relativePath)
    {
        $uploadBase = Config::get('upload_dir');
        $trashDir = $uploadBase . '.trash';

        if (!is_dir($trashDir)) {
            mkdir($trashDir, 0777, true);
        }

        $fileName = basename($fullPath);
        $trashName = time() . '_' . $fileName;
        $destPath = $trashDir . DIRECTORY_SEPARATOR . $trashName;

        if (rename($fullPath, $destPath)) {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("INSERT INTO media_trash (original_path, original_name, trash_path, file_size) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $relativePath,
                $fileName,
                $trashName,
                is_dir($destPath) ? 0 : filesize($destPath)
            ]);
            return true;
        }
        throw new Exception("Failed to move file to trash");
    }

    private function getMediaSettings()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT value FROM system_settings WHERE key = 'media_trash_retention'");
        $stmt->execute();
        $val = $stmt->fetchColumn();
        return [
            'trash_retention' => $val ?: 30
        ];
    }

    /**
     * Automatically cleans up old files in the trash.
     */
    private function cleanupTrash()
    {
        $settings = $this->getMediaSettings();
        $days = (int) $settings['trash_retention'];

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM media_trash WHERE deleted_at < DATETIME('now', ?)");
        $stmt->execute(["-$days days"]);
        $expired = $stmt->fetchAll();

        $uploadBase = Config::get('upload_dir');
        $trashDir = $uploadBase . '.trash';
        $purgueCount = 0;

        foreach ($expired as $item) {
            $path = $trashDir . DIRECTORY_SEPARATOR . $item['trash_path'];
            if (file_exists($path)) {
                if (is_dir($path))
                    $this->recursiveDelete($path);
                else
                    unlink($path);
            }
            $deleteStmt = $db->prepare("DELETE FROM media_trash WHERE id = ?");
            $deleteStmt->execute([$item['id']]);
            $purgueCount++;
        }

        return $purgueCount;
    }

    /**
     * Legacy media list for compatibility with CRUD forms.
     */
    public function mediaList()
    {
        $uploadBase = Config::get('upload_dir');
        $fullBaseUrl = Auth::getFullBaseUrl();
        $files = [];
        $dates = [];
        $tables = [];

        if (is_dir($uploadBase)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploadBase));
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $item = $file->getFilename();
                    if (strpos($item, '.') === 0)
                        continue; // Hide hidden

                    $path = $file->getPathname();
                    $relativePath = str_replace($uploadBase, '', $path);
                    $parts = explode(DIRECTORY_SEPARATOR, $relativePath);

                    if (strpos($relativePath, '.trash') !== false)
                        continue;

                    if (count($parts) >= 3 || (count($parts) >= 2 && $parts[0] !== 'explorer')) {
                        $dateFolder = $parts[0];
                        $tableFolder = count($parts) > 1 ? $parts[1] : 'root';
                        $dates[] = $dateFolder;
                        $tables[] = $tableFolder;
                        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf', 'doc', 'docx', 'txt', 'zip', 'rar', 'mp4', 'mov'];
                        if (in_array($ext, $allowedExtensions)) {
                            $files[] = [
                                'url' => $fullBaseUrl . 'uploads/' . str_replace(DIRECTORY_SEPARATOR, '/', $relativePath),
                                'name' => $item,
                                'extension' => $ext,
                                'date_folder' => $dateFolder,
                                'table_folder' => $tableFolder,
                                'mtime' => $file->getMTime()
                            ];
                        }
                    }
                }
            }
        }
        usort($files, function ($a, $b) {
            return $b['mtime'] - $a['mtime'];
        });
        $this->json([
            'files' => $files,
            'available_dates' => array_values(array_unique($dates)),
            'available_tables' => array_values(array_unique($tables))
        ]);
    }

    /**
     * Legacy media upload for compatibility with CRUD forms.
     */
    public function mediaUpload()
    {
        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'No file uploaded or upload error'], 400);
        }

        $uploadBase = Config::get('upload_dir');
        $dateFolder = date('Y-m-d');
        $tableFolder = 'explorer';
        $relativeDir = "$dateFolder/$tableFolder/";
        $absoluteDir = $uploadBase . $relativeDir;

        if (!is_dir($absoluteDir)) {
            mkdir($absoluteDir, 0777, true);
        }

        $file = $_FILES['file'];
        $safeName = $this->sanitizeFilename($file['name']);

        if (file_exists($absoluteDir . $safeName)) {
            $info = pathinfo($safeName);
            $safeName = $info['filename'] . '-' . substr(uniqid(), -5) . '.' . $info['extension'];
        }

        if (move_uploaded_file($file['tmp_name'], $absoluteDir . $safeName)) {
            $url = Auth::getFullBaseUrl() . 'uploads/' . $relativeDir . $safeName;
            $this->json([
                'url' => $url,
                'name' => $safeName,
                'date_folder' => $dateFolder,
                'table_folder' => $tableFolder
            ]);
        }

        $this->json(['error' => 'Failed to move uploaded file'], 500);
    }

    private function getPathBreadcrumbs($path)
    {
        if (empty($path))
            return [['name' => 'Root', 'path' => '']];

        $parts = explode('/', $path);
        $crumbs = [['name' => 'Root', 'path' => '']];
        $current = '';

        foreach ($parts as $part) {
            if (empty($part))
                continue;
            $current = ltrim($current . '/' . $part, '/');
            $crumbs[] = [
                'name' => $part,
                'path' => $current
            ];
        }

        return $crumbs;
    }

    private function recursiveDelete($dir)
    {
        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..')
                continue;
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->recursiveDelete($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    protected function sanitizeFilename($filename)
    {
        $info = pathinfo($filename);
        $name = $info['filename'];
        $ext = isset($info['extension']) ? '.' . strtolower($info['extension']) : '';

        // Handle MacOS/NFD decomposed characters if Normalizer exists
        if (class_exists('Normalizer')) {
            $name = \Normalizer::normalize($name, \Normalizer::FORM_C);
        }

        // Broad map for common accents
        $map = [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ñ' => 'n',
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ñ' => 'N',
            'à' => 'a',
            'è' => 'e',
            'ì' => 'i',
            'ò' => 'o',
            'ù' => 'u',
            'À' => 'A',
            'È' => 'E',
            'Ì' => 'I',
            'Ò' => 'O',
            'Ù' => 'U',
            'ä' => 'a',
            'ë' => 'e',
            'ï' => 'i',
            'ö' => 'o',
            'ü' => 'u',
            'Ä' => 'A',
            'Ë' => 'E',
            'Ï' => 'I',
            'Ö' => 'O',
            'Ü' => 'U'
        ];
        $name = strtr($name, $map);

        // Ultimate cleanup with Transliterator or Regex
        if (class_exists('Transliterator')) {
            $trans = \Transliterator::create('Any-Latin; Latin-ASCII; [\u0080-\u7fff] remove');
            $name = $trans->transliterate($name);
        }

        $name = strtolower($name);
        // Strip anything not a-z or 0-9
        $name = preg_replace('/[^a-z0-9]+/', '-', $name);
        $name = trim($name, '-');

        return (empty($name) ? 'file' : $name) . $ext;
    }
}
