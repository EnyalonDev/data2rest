<?php

namespace App\Modules\Api;

use App\Core\BaseController;
use App\Core\Config;
use App\Core\Database;
use App\Core\Auth;
use App\Modules\Media\ImageService;
use Exception;

class StorageApiController extends BaseController
{
    /**
     * Authenticate API request
     * Copied/Adapted from RestController to ensure consistent API Key usage.
     */
    private function authenticate()
    {
        Auth::init();

        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $apiKey = $headers['X-API-KEY'] ?? $headers['X-API-Key'] ?? $headers['x-api-key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? null;

        // Internal bypass (same logic as RestController)
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
        $isLocalhost = in_array($clientIp, ['127.0.0.1', '::1', '0:0:0:0:0:0:0:1']);

        // Allow session-based auth if no API key is present (Hybrid support)
        if (!$apiKey && (Auth::check() || $isLocalhost)) {
            return ['name' => 'Internal-Session', 'key_value' => 'internal'];
        }

        if (!$apiKey) {
            $this->json(['error' => 'API Key required'], 401);
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM api_keys WHERE key_value = ? AND status = 1");
        $stmt->execute([$apiKey]);
        $keyData = $stmt->fetch();

        if (!$keyData) {
            $this->json(['error' => 'Invalid API Key'], 403);
        }

        return $keyData;
    }

    public function upload()
    {
        $this->authenticate();

        if (empty($_FILES['file'])) {
            $this->json(['error' => 'No file uploaded'], 400);
        }

        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'Upload error code: ' . $file['error']], 400);
        }

        $uploadBase = Config::get('upload_dir');

        // Default to a 'public/storage' folder structure
        $dateFolder = date('Y-m-d');
        $uploadSubPath = 'storage/' . $dateFolder . '/';

        // If DB ID Provided, scope it like RestController does
        // But for generic storage, we might just want to be simple.
        // Let's use 'storage' as the "table" equivalent.

        $targetDir = $uploadBase . $uploadSubPath;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $safeName = $this->sanitizeFilename($file['name']);

        // Collision handling
        if (file_exists($targetDir . $safeName)) {
            $info = pathinfo($safeName);
            $safeName = $info['filename'] . '-' . substr(uniqid(), -5) . '.' . $info['extension'];
        }

        // Use ImageService for implementation consistency (optimization, etc.)
        $imageService = new ImageService();
        // ImageService::process args: $tmpPath, $targetDir, $targetName, $keepOriginal
        try {
            $finalName = $imageService->process($file['tmp_name'], $targetDir, $safeName);

            if (file_exists($targetDir . $finalName)) {
                $publicUrl = Auth::getFullBaseUrl() . 'uploads/' . $uploadSubPath . $finalName;

                $this->json([
                    'success' => true,
                    'url' => $publicUrl,
                    'filename' => $finalName
                ]);
            } else {
                $this->json(['error' => 'Failed to save file'], 500);
            }
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}
