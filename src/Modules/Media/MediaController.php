<?php

namespace App\Modules\Media;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Config;
use App\Core\BaseController;
use App\Core\Logger;
use PDO;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Exception;
use App\Modules\Webhooks\WebhookDispatcher;

/**
 * Media Controller
 * 
 * Comprehensive file and media management system with advanced features
 * including usage tracking, trash management, and image editing.
 * 
 * Core Features:
 * - File upload with automatic optimization
 * - Folder management and organization
 * - Usage tracking across all databases
 * - Trash system with restoration
 * - Bulk operations (delete, move)
 * - Image editing (crop, resize, filters)
 * - AI-powered background removal support
 * - Storage quota enforcement
 * - Webhook integration
 * 
 * Image Editing:
 * - Crop and resize
 * - Filters (grayscale, sepia, vintage, dramatic, etc.)
 * - Quality optimization
 * - Format conversion (JPEG, PNG, WebP, AVIF)
 * - Client-side AI processing support
 * 
 * Security:
 * - Permission-based access control
 * - Path traversal prevention
 * - File type validation
 * - Storage quota limits
 * 
 * Organization:
 * - Project-scoped storage
 * - Automatic folder structure
 * - Breadcrumb navigation
 * - File search and filtering
 * 
 * @package App\Modules\Media
 * @author DATA2REST Development Team
 * @version 1.0.0
 */
/**
 * MediaController Controller
 *
 * Core Features: TODO
 *
 * Security: Requires login, permission checks as implemented.
 *
 * @package App\Modules\
 * @author DATA2REST Development Team
 * @version 1.0.0
 */
class MediaController extends BaseController
{
    /**
     * Constructor - Requires user authentication
     * 
     * Ensures that only authenticated users can access
     * media management functionality.
     */
    /**
     * __construct method
     *
     * @return void
     */
    public function __construct()
    {
        Auth::requireLogin();
    }

    /**
     * Validates that the requested database belongs to the current active project.
     * Prevents cross-project file access tampering.
     */
    private function validateProjectScope($db_id)
    {
        if (!$db_id)
            return;

        $activeProject = Auth::getActiveProject();
        if (!$activeProject && !Auth::isAdmin())
            return; // Should not happen if logged in
        if (Auth::isAdmin() && !$activeProject)
            return; // Admin global view

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT project_id FROM databases WHERE id = ?");
        $stmt->execute([$db_id]);
        $dbProjectId = $stmt->fetchColumn();

        if ($dbProjectId && $dbProjectId != $activeProject) {
            // Tampering detected or unauthorized access
            Auth::setFlashError("Security Warning: Cross-project access denied.", 'error');
            $this->json(['error' => 'Access Denied: Database does not belong to active project.'], 403);
        }
    }

    /**
     * Resolves the storage prefix strictly based on db_id if provided.
     * Prevents fallback to global scope for Admins when specific DB context is requested.
     */
    private function getStrictProjectPrefix($db_id)
    {
        if ($db_id) {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT project_id FROM databases WHERE id = ?");
            $stmt->execute([$db_id]);
            $projectId = $stmt->fetchColumn();
            return $projectId ? 'p' . $projectId : 'global';
        }
        return $this->getStoragePrefix(null);
    }

    /**
     * Display media library interface
     * 
     * Renders the main media manager UI with file browser,
     * upload interface, and management tools.
     * 
     * @return void Renders media library view
     */
    // Local getStoragePrefix removed: using getStoragePrefix from BaseController

    /**
     * Renders the main media manager UI.
     */
    /**
     * index method
     *
     * @return void
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
     * List files and folders (JSON endpoint)
     * 
     * Returns a JSON list of files and folders in the specified path
     * with automatic trash cleanup and breadcrumb navigation.
     * 
     * Features:
     * - Project-scoped file listing
     * - Path traversal protection
     * - Automatic sorting (folders first)
     * - Image detection
     * - File metadata (size, mtime)
     * 
     * @return void Outputs JSON response with file list
     * 
     * @example
     * GET /admin/media/list?db_id=1&path=images/products
     * Response: {"current_path": "...", "items": [...], "breadcrumbs": [...]}
     */
    /**
     * list method
     *
     * @return void
     */
    public function list()
    {
        while (ob_get_level())
            ob_end_clean(); // Ensure clean output
        Auth::requirePermission('module:media.view_files');
        $uploadBase = Config::get('upload_dir');

        // Scope to Project
        $db_id = $_GET['db_id'] ?? null;
        $this->validateProjectScope($db_id);

        $scopePath = $this->getStrictProjectPrefix($db_id);
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
                $itemData['is_image'] = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif']);
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
     * Scan databases for file usage
     * 
     * Searches all accessible databases to find where a specific file
     * is being used. Useful for determining if a file can be safely deleted.
     * 
     * Features:
     * - Project-scoped or admin-wide search
     * - Searches all text/varchar columns
     * - Returns database, table, and record IDs
     * - Limited to 10 matches per table
     * 
     * @return void Outputs JSON response with usage information
     * 
     * @example
     * GET /admin/media/usage?url=https://example.com/uploads/image.jpg
     * Response: {"usage": [{"database": "...", "table": "...", "row_ids": [...]}]}
     */
    /**
     * usage method
     *
     * @return void
     */
    public function usage()
    {
        while (ob_get_level())
            ob_end_clean(); // Ensure clean output
        $fileUrl = $_GET['url'] ?? '';
        if (empty($fileUrl)) {
            $this->json(['error' => 'No URL provided'], 400);
        }

        $fileName = basename($fileUrl);
        $systemDb = Database::getInstance()->getConnection();

        $projectId = Auth::getActiveProject();
        if (Auth::isAdmin() && !$projectId) {
            $stmt = $systemDb->query("SELECT * FROM " . Database::getInstance()->getAdapter()->quoteName('databases') . "");
            $allDbs = $stmt->fetchAll();
        } else {
            if (!$projectId) {
                $this->json(['usage' => []]);
                return;
            }
            $stmt = $systemDb->prepare("SELECT * FROM " . Database::getInstance()->getAdapter()->quoteName('databases') . " WHERE project_id = ?");
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
     * Handle file upload
     * 
     * Processes file uploads with automatic optimization, quota enforcement,
     * and webhook notifications.
     * 
     * Features:
     * - Automatic image optimization via ImageService
     * - Storage quota validation
     * - Collision handling (auto-rename)
     * - Project-scoped storage
     * - Webhook trigger on success
     * 
     * @return void Outputs JSON response with upload result
     * 
     * @example
     * POST /admin/media/upload
     * Files: file=@image.jpg
     * Body: path=products&db_id=1
     * Response: {"success": true, "name": "image.jpg", "url": "..."}
     */
    /**
     * upload method
     *
     * @return void
     */
    public function upload()
    {
        while (ob_get_level())
            ob_end_clean(); // Ensure clean output
        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'No file uploaded or upload error'], 400);
        }

        Auth::requirePermission('module:media.upload'); // Check perms
        $path = $_POST['path'] ?? '';
        $db_id = $_POST['db_id'] ?? null;
        $this->validateProjectScope($db_id);
        $path = str_replace(['..', '\\'], '', $path);

        $uploadBase = Config::get('upload_dir');

        $scopePath = $this->getStrictProjectPrefix($db_id);
        $projectBase = $uploadBase . $scopePath . '/';

        // Final target is projectBase + requested relative path
        $targetDir = $projectBase . ($path ? $path . '/' : '');

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $file = $_FILES['file'];
        $fileSize = $file['size'];

        // Storage Quota Enforcement
        $storageInfo = $this->getProjectStorageInfo();
        if ($storageInfo) {
            $quotaBytes = $storageInfo['quota_mb'] * 1024 * 1024;
            if ($storageInfo['used_bytes'] + $fileSize > $quotaBytes) {
                $this->json([
                    'error' => \App\Core\Lang::get('projects.quota_exceeded', ['limit' => $storageInfo['quota_mb']])
                ], 403);
            }
        }

        $safeName = $this->sanitizeFilename($file['name']);
        if (file_exists($targetDir . $safeName)) {
            $info = pathinfo($safeName);
            $safeName = $info['filename'] . '-' . substr(uniqid(), -5) . '.' . $info['extension'];
        }

        // Check if user has permission to upload original files (without optimization)
        $canUploadOriginal = Auth::hasPermission('module:media.upload_original');

        $imageService = new ImageService();
        $safeName = $imageService->process($file['tmp_name'], $targetDir, $safeName, $canUploadOriginal);

        if (file_exists($targetDir . $safeName)) {
            $publicUrl = Auth::getFullBaseUrl() . 'uploads/' . $scopePath . '/' . ($path ? $path . '/' : '') . $safeName;

            $this->json([
                'success' => true,
                'name' => $safeName,
                'url' => $publicUrl
            ]);
            Logger::log('UPLOAD_FILE', ['name' => $safeName, 'path' => $path], $db_id);

            // Webhook Trigger
            $projectId = Auth::getActiveProject();
            if ($projectId) {
                WebhookDispatcher::dispatch($projectId, 'media.uploaded', [
                    'filename' => $safeName,
                    'url' => $publicUrl,
                    'path' => $path,
                    'size' => $fileSize
                ]);
            }
        }

        $this->json(['error' => 'Failed to move uploaded file'], 500);
    }

    /**
     * Move file or folder to trash
     * 
     * Performs a soft delete by moving the file to a project-scoped
     * trash directory with restoration capability.
     * 
     * Features:
     * - Soft delete (recoverable)
     * - Path traversal protection
     * - Trash metadata tracking
     * - Automatic cleanup based on retention settings
     * 
     * @return void Outputs JSON response with success status
     * 
     * @example
     * POST /admin/media/delete
     * Body: path=images/old-photo.jpg&db_id=1
     * Response: {"success": true}
     */
    /**
     * delete method
     *
     * @return void
     */
    public function delete()
    {
        while (ob_get_level())
            ob_end_clean(); // Ensure clean output
        Auth::requirePermission('module:media.delete_files');
        $path = $_POST['path'] ?? '';
        if (empty($path))
            $this->json(['error' => 'No path provided'], 400);

        $path = str_replace(['..', '\\'], '', $path);
        $db_id = $_POST['db_id'] ?? null;
        $this->validateProjectScope($db_id);
        $uploadBase = Config::get('upload_dir');
        $scopePath = $this->getStrictProjectPrefix($db_id);
        $projectBase = realpath($uploadBase . $scopePath);

        $fullPath = realpath($projectBase . '/' . $path);

        if (!$fullPath || strpos($fullPath, $projectBase) !== 0) {
            $this->json(['error' => 'Invalid path'], 403);
        }

        try {
            $this->moveToTrash($fullPath, $path, $scopePath);
            Logger::log('DELETE_FILE', ['path' => $path], $db_id);
            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Bulk deletes multiple files/folders.
     */
    /**
     * bulkDelete method
     *
     * @return void
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
        $scopePath = $this->getStoragePrefix();
        $projectBase = realpath($uploadBase . $scopePath);

        foreach ($paths as $path) {
            $safePath = str_replace(['..', '\\'], '', $path);
            $fullPath = realpath($projectBase . '/' . $safePath);

            if (!$fullPath || strpos($fullPath, $projectBase) !== 0) {
                $results['error'][] = "Invalid path: $path";
                continue;
            }

            try {
                $this->moveToTrash($fullPath, $safePath, $scopePath); // Relative path passed to trash needs to be handled carefully?
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

        if (!empty($results['success'])) {
            Logger::log('BULK_DELETE', ['count' => count($results['success']), 'paths' => $results['success']]);
        }
        $this->json($results);
    }

    /**
     * Bulk moves files/folders to a target directory.
     */
    /**
     * bulkMove method
     *
     * @return void
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
        $scopePath = $this->getStoragePrefix();
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
                Logger::log('MOVE_FILE', ['path' => $path, 'target' => $targetDir]);
            } else {
                $results['error'][] = "Failed to move $fileName";
            }
        }

        if (!empty($results['success'])) {
            Logger::log('BULK_MOVE', ['count' => count($results['success']), 'target' => $targetDir]);
        }
        $this->json($results);
    }

    /**
     * Renames or moves a file/folder.
     */
    /**
     * rename method
     *
     * @return void
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
        $scopePath = $this->getStoragePrefix();
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
            Logger::log('RENAME_FILE', ['old' => $oldPath, 'new' => $newName]);
            $this->json(['success' => true]);
        }

        $this->json(['error' => 'Rename failed'], 500);
    }

    /**
     * Updates media settings.
     */
    /**
     * updateSettings method
     *
     * @return void
     */
    public function updateSettings()
    {
        Auth::requirePermission('module:media.edit_files');
        $retentionDays = $_POST['trash_retention'] ?? 30;
        $maxDimension = $_POST['max_dimension'] ?? 1080;
        $priority = $_POST['optimize_priority'] ?? 'webp';
        $quality = $_POST['optimize_quality'] ?? 85;

        $db = Database::getInstance()->getConnection();

        $settings = [
            'media_trash_retention' => $retentionDays,
            'media_optimize_max_dimension' => $maxDimension,
            'media_optimize_priority' => $priority,
            'media_optimize_quality' => $quality
        ];

        $adapter = Database::getInstance()->getAdapter();

        foreach ($settings as $key => $value) {
            $sql = $adapter->getUpsertSQL('system_settings', ['key_name' => $key, 'value' => $value], 'key_name');
            $stmt = $db->prepare($sql);
            $stmt->execute([$key, $value]);
        }

        $this->json(['success' => true]);
    }

    /**
     * Restores a file from the trash to its original location.
     */
    /**
     * restore method
     *
     * @return void
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
        $scopePath = $this->getStoragePrefix(); // Project scope
        $srcPath = $uploadBase . $scopePath . '/.trash/' . $item['trash_path'];
        $destPath = $uploadBase . $scopePath . '/' . $item['original_path'];

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
            Logger::log('RESTORE_FILE', ['path' => $item['original_path']]);
            $this->json(['success' => true]);
        } else {
            $this->json(['error' => 'Failed to restore file'], 500);
        }
    }

    /**
     * Permanently deletes a file from the trash.
     */
    /**
     * purge method
     *
     * @return void
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
    /**
     * edit method
     *
     * @return void
     */
    public function edit()
    {
        Auth::requirePermission('module:media.edit_files');
        $path = $_POST['path'] ?? '';
        $action = $_POST['action'] ?? ''; // 'transform' or 'optimize'

        if (empty($path))
            $this->json(['error' => 'No path provided'], 400);

        $uploadBase = Config::get('upload_dir');
        $scopePath = $this->getStoragePrefix();
        $projectBase = realpath($uploadBase . $scopePath);

        $fullPath = realpath($projectBase . '/' . str_replace(['..', '\\'], '', $path));

        if (!$fullPath || strpos($fullPath, $projectBase) !== 0 || !file_exists($fullPath)) {
            $this->json(['error' => 'Invalid file path'], 403);
        }

        // Handle Client-Side Edits (e.g. AI Background Removal)
        if (isset($_FILES['client_file']) && $_FILES['client_file']['error'] === UPLOAD_ERR_OK) {
            $pi = pathinfo($fullPath);
            $saveExt = 'png'; // AI results are usually PNGs with transparency

            if (isset($_POST['save_as_copy']) && $_POST['save_as_copy'] === 'true') {
                $saveName = $pi['filename'] . '-ai-' . time() . '.' . $saveExt;
                $finalPath = $pi['dirname'] . DIRECTORY_SEPARATOR . $saveName;
            } else {
                $saveName = $pi['filename'] . '.' . $saveExt;
                $finalPath = $pi['dirname'] . DIRECTORY_SEPARATOR . $saveName;

                // If extension changed (e.g. jpg -> png), delete original
                if ($finalPath !== $fullPath) {
                    unlink($fullPath);
                }
            }

            if (move_uploaded_file($_FILES['client_file']['tmp_name'], $finalPath)) {
                Logger::log('EDIT_IMAGE_AI', ['path' => $path]);
                $this->json(['success' => true, 'new_name' => basename($finalPath)]);
                return;
            }
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
            case defined('IMAGETYPE_AVIF') ? IMAGETYPE_AVIF : -1:
                $image = imagecreatefromavif($fullPath);
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
                if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_WEBP || (defined('IMAGETYPE_AVIF') && $type == IMAGETYPE_AVIF)) {
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
        $format = $_POST['format'] ?? 'original';

        // Determine Target Type
        $targetType = $type;
        $targetExt = image_type_to_extension($type, false);

        if ($format === 'webp') {
            $targetType = IMAGETYPE_WEBP;
            $targetExt = 'webp';
        } elseif ($format === 'avif' && defined('IMAGETYPE_AVIF')) {
            $targetType = IMAGETYPE_AVIF;
            $targetExt = 'avif';
        } elseif ($format === 'jpeg') {
            $targetType = IMAGETYPE_JPEG;
            $targetExt = 'jpg';
        } elseif ($format === 'png') {
            $targetType = IMAGETYPE_PNG;
            $targetExt = 'png';
        }

        $success = false;

        // If it's a save-as-copy OR format changed (force copy logic efficiently)
        // Actually, if format changed, we treat it as a new file unless we overwrite (but we usually want to save as copy)
        // If "Save as Copy" is FALSE but format changed, we effectively delete original and create new one?
        // Let's stick to: If format changed, we force a filename change (extension).

        $savePath = $fullPath;
        $pi = pathinfo($fullPath);

        if ((isset($_POST['save_as_copy']) && $_POST['save_as_copy'] === 'true') || $targetType !== $type) {
            // If format changed, using the NEW extension
            $newFilename = $pi['filename'] . ($targetType !== $type ? '' : '-edited') . '-' . time() . '.' . $targetExt;
            $savePath = $pi['dirname'] . DIRECTORY_SEPARATOR . $newFilename;
        }

        switch ($targetType) {
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
            case defined('IMAGETYPE_AVIF') ? IMAGETYPE_AVIF : -1:
                $success = imageavif($image, $savePath, $quality);
                break;
        }

        imagedestroy($image);

        if ($success) {
            Logger::log('EDIT_IMAGE', ['path' => $path]);
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

    private function moveToTrash($fullPath, $relativePath, $scopePath)
    {
        $uploadBase = Config::get('upload_dir');
        // Use scoped trash directory
        $trashDir = $uploadBase . $scopePath . '/.trash';

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
        $adapter = Database::getInstance()->getAdapter();
        $stmt = $db->query("SELECT key_name, value FROM system_settings WHERE key_name LIKE 'media_%'");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        return [
            'trash_retention' => $settings['media_trash_retention'] ?? 30,
            'optimize_max_dimension' => $settings['media_optimize_max_dimension'] ?? 1080,
            'optimize_priority' => $settings['media_optimize_priority'] ?? 'webp',
            'optimize_quality' => $settings['media_optimize_quality'] ?? 85
        ];
    }

    /**
     * Automatically cleans up old files in the trash.
     */
    private function cleanupTrash()
    {
        $settings = $this->getMediaSettings();
        $days = (int) $settings['trash_retention'];

        $cutoff = date('Y-m-d H:i:s', strtotime("-$days days"));

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM media_trash WHERE deleted_at < ?");
        $stmt->execute([$cutoff]);
        $expired = $stmt->fetchAll();

        $uploadBase = Config::get('upload_dir');
        $scopePath = $this->getStoragePrefix(); // Current scope
        $trashDir = $uploadBase . $scopePath . '/.trash';
        $purgueCount = 0;

        foreach ($expired as $item) {
            $path = $trashDir . DIRECTORY_SEPARATOR . $item['trash_path'];

            // Only delete if it exists in CURRENT scope trash
            // This prevents deleting files from other project scopes if global trash table is shared
            if (file_exists($path)) {
                if (is_dir($path)) {
                    $this->recursiveDelete($path);
                } else {
                    unlink($path);
                }

                $delStmt = $db->prepare("DELETE FROM media_trash WHERE id = ?");
                $delStmt->execute([$item['id']]);
                $purgueCount++;
            }
        }
        return $purgueCount;
    }

    /**
     * Legacy media list for compatibility with CRUD forms.
     */
    /**
     * mediaList method
     *
     * @return void
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
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif', 'pdf', 'doc', 'docx', 'txt', 'zip', 'rar', 'mp4', 'mov'];
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
            'available_tables' => array_values(array_unique($tables)),
            'storage_info' => $this->getProjectStorageInfo()
        ]);
    }

    /**
     * Legacy media upload for compatibility with CRUD forms.
     */
    /**
     * mediaUpload method
     *
     * @return void
     */
    public function mediaUpload()
    {
        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'No file uploaded or upload error'], 400);
        }

        $uploadBase = Config::get('upload_dir');
        $db_id = $_POST['db_id'] ?? null;
        $scopePath = $this->getStoragePrefix($db_id);
        $dateFolder = date('Y-m-d');
        $tableFolder = 'explorer';
        $relativeDir = "$scopePath/$dateFolder/$tableFolder/";
        $absoluteDir = $uploadBase . $relativeDir;

        if (!is_dir($absoluteDir)) {
            mkdir($absoluteDir, 0777, true);
        }

        $file = $_FILES['file'];
        $fileSize = $file['size'];

        // Storage Quota Enforcement
        $storageInfo = $this->getProjectStorageInfo();
        if ($storageInfo) {
            $quotaBytes = $storageInfo['quota_mb'] * 1024 * 1024;
            if ($storageInfo['used_bytes'] + $fileSize > $quotaBytes) {
                $this->json([
                    'error' => \App\Core\Lang::get('projects.quota_exceeded', ['limit' => $storageInfo['quota_mb']])
                ], 403);
            }
        }

        $safeName = $this->sanitizeFilename($file['name']);
        if (file_exists($absoluteDir . $safeName)) {
            $info = pathinfo($safeName);
            $safeName = $info['filename'] . '-' . substr(uniqid(), -5) . '.' . $info['extension'];
        }

        // Check if user has permission to upload original files (without optimization)
        $canUploadOriginal = Auth::hasPermission('module:media.upload_original');

        $imageService = new ImageService();
        $safeName = $imageService->process($file['tmp_name'], $absoluteDir, $safeName, $canUploadOriginal);

        if (file_exists($absoluteDir . $safeName)) {
            $url = Auth::getFullBaseUrl() . 'uploads/' . str_replace('//', '/', $relativeDir . $safeName);
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

    /**
     * Creates a new folder.
     */
    /**
     * createFolder method
     *
     * @return void
     */
    public function createFolder()
    {
        $name = $_POST['name'] ?? null;
        $path = $_POST['path'] ?? ''; // Relative to project root
        $db_id = $_POST['db_id'] ?? null;

        if (!$name) {
            $this->json(['error' => 'Folder name is required'], 400);
        }

        // Clean name and path (sanitize)
        $name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name);
        $path = str_replace(['..', '\\'], '', $path);

        $uploadBase = Config::get('upload_dir');
        $scopePath = $this->getStoragePrefix($db_id);
        $targetDir = $uploadBase . $scopePath . DIRECTORY_SEPARATOR . $path . DIRECTORY_SEPARATOR . $name;
        $targetDir = str_replace([DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR], [DIRECTORY_SEPARATOR], $targetDir);

        if (file_exists($targetDir)) {
            $this->json(['error' => 'Folder already exists'], 400);
        }

        if (mkdir($targetDir, 0777, true)) {
            $this->json(['success' => true]);
        } else {
            $this->json(['error' => 'Failed to create folder'], 500);
        }
    }
}
