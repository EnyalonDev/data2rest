<?php

namespace App\Modules\Api;

use App\Core\Auth;
use App\Core\Database;
use App\Core\BaseController;
use App\Core\Logger;
use App\Core\ApiPermissionManager;
use PDO;

/**
 * API Permissions Controller
 * 
 * Manages granular permissions for API keys
 * 
 * @package App\Modules\Api
 * @version 1.0.0
 */
class ApiPermissionsController extends BaseController
{
    public function __construct()
    {
        Auth::requireLogin();
        Auth::requirePermission('module:api.manage_permissions');
    }

    /**
     * Show permissions management page for an API key
     */
    public function manage()
    {
        $apiKeyId = $_GET['key_id'] ?? null;

        if (!$apiKeyId) {
            Auth::setFlashError('API Key ID required');
            $this->redirect('admin/api');
        }

        $db = Database::getInstance()->getConnection();

        // Get API key details
        $stmt = $db->prepare("SELECT * FROM api_keys WHERE id = ?");
        $stmt->execute([$apiKeyId]);
        $apiKey = $stmt->fetch();

        if (!$apiKey) {
            Auth::setFlashError('API Key not found');
            $this->redirect('admin/api');
        }


        // Get all databases scoped to project
        $projectId = Auth::getActiveProject();
        $adapter = Database::getInstance()->getAdapter();
        $tableDatabases = $adapter->quoteName('databases');

        if ($projectId) {
            $stmtDb = $db->prepare("SELECT id, name FROM $tableDatabases WHERE project_id = ? ORDER BY name");
            $stmtDb->execute([$projectId]);
            $databases = $stmtDb->fetchAll();
        } else if (Auth::isAdmin()) {
            // Logic for superadmin seeing all DBs (or force project selection)
            $databases = $db->query("SELECT id, name FROM $tableDatabases ORDER BY name")->fetchAll();
        } else {
            $databases = [];
        }

        // Get current permissions
        $permManager = new ApiPermissionManager();
        $permissions = $permManager->getPermissions($apiKeyId);

        // Get rate limit stats
        $rateLimiter = new \App\Core\RateLimiter();
        $rawStats = $rateLimiter->getStats($apiKeyId);

        // Ensure defaults to prevent view errors
        $stats = array_merge([
            'requests' => 0,
            'remaining' => $apiKey['rate_limit'],
            'remaining_time' => 'N/A'
        ], $rawStats ?: []);

        $this->view('admin/api/permissions', [
            'title' => 'API Permissions - ' . $apiKey['name'],
            'apiKey' => $apiKey,
            'databases' => $databases,
            'permissions' => $permissions,
            'stats' => $stats
        ]);
    }

    /**
     * Save permissions for an API key
     */
    public function save()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/api');
        }

        $apiKeyId = $_POST['api_key_id'] ?? null;
        $databaseId = $_POST['database_id'] ?? null;
        $tableName = $_POST['table_name'] ?? null;
        $allowedIps = $_POST['allowed_ips'] ?? null;

        if (!$apiKeyId || !$databaseId) {
            Auth::setFlashError('Missing required fields');
            $this->redirect('admin/api');
        }

        // Security: Verify database belongs to active project
        $projectId = Auth::getActiveProject();
        $db = Database::getInstance()->getConnection();
        $adapter = Database::getInstance()->getAdapter();
        $tableDatabases = $adapter->quoteName('databases');

        if ($projectId) {
            $stmtCheck = $db->prepare("SELECT id FROM $tableDatabases WHERE id = ? AND project_id = ?");
            $stmtCheck->execute([$databaseId, $projectId]);
            if (!$stmtCheck->fetch()) {
                Auth::setFlashError('Access Denied: Invalid Database for this Project');
                $this->redirect('admin/api/permissions?key_id=' . $apiKeyId);
            }
        } else if (!Auth::isAdmin()) {
            // Non-admin without project context cannot assign permissions
            Auth::setFlashError('Access Denied: No active project context');
            $this->redirect('admin/api/permissions?key_id=' . $apiKeyId);
        }

        $permissions = [
            'read' => isset($_POST['can_read']),
            'create' => isset($_POST['can_create']),
            'update' => isset($_POST['can_update']),
            'delete' => isset($_POST['can_delete'])
        ];

        $permManager = new ApiPermissionManager();
        $success = $permManager->setPermissions(
            $apiKeyId,
            $databaseId,
            $tableName ?: null,
            $permissions,
            $allowedIps
        );

        if ($success) {
            Logger::log('API_PERMISSION_UPDATED', [
                'api_key_id' => $apiKeyId,
                'database_id' => $databaseId,
                'table' => $tableName,
                'permissions' => $permissions
            ]);
            Auth::setFlashError('Permissions saved successfully', 'success');
        } else {
            Auth::setFlashError('Failed to save permissions');
        }

        $this->redirect('admin/api/permissions?key_id=' . $apiKeyId);
    }

    /**
     * Delete a permission rule
     */
    public function delete()
    {
        $permissionId = $_POST['permission_id'] ?? null;

        if (!$permissionId) {
            $this->json(['error' => 'Permission ID required'], 400);
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM api_key_permissions WHERE id = ?");
        $success = $stmt->execute([$permissionId]);

        if ($success) {
            Logger::log('API_PERMISSION_DELETED', ['permission_id' => $permissionId]);
            $this->json(['success' => true]);
        } else {
            $this->json(['error' => 'Failed to delete permission'], 500);
        }
    }

    /**
     * Update API key rate limit
     */
    public function updateRateLimit()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'POST required'], 405);
        }

        $apiKeyId = $_POST['api_key_id'] ?? null;
        $rateLimit = (int) ($_POST['rate_limit'] ?? 1000);

        if (!$apiKeyId) {
            $this->json(['error' => 'API Key ID required'], 400);
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE api_keys SET rate_limit = ? WHERE id = ?");
        $success = $stmt->execute([$rateLimit, $apiKeyId]);

        if ($success) {
            Logger::log('API_RATE_LIMIT_UPDATED', [
                'api_key_id' => $apiKeyId,
                'new_limit' => $rateLimit
            ]);
            $this->json(['success' => true, 'rate_limit' => $rateLimit]);
        } else {
            $this->json(['error' => 'Failed to update rate limit'], 500);
        }
    }
}
