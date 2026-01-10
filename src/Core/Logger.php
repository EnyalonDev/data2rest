<?php

namespace App\Core;

use App\Core\Database;
use App\Core\Auth;

/**
 * Global Activity Logger
 */
class Logger
{
    /**
     * Records an action in the activity logs.
     * 
     * @param string $action Short description of the action (e.g. 'CREATE_DATABASE')
     * @param string|array $details Detailed information about the action
     * @param int|null $projectId Optional project context
     */
    public static function log($action, $details = '', $projectId = null)
    {
        try {
            $db = Database::getInstance()->getConnection();
            $userId = $_SESSION['user_id'] ?? null;
            $projectId = $projectId ?? Auth::getActiveProject();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

            if (is_array($details)) {
                $details = json_encode($details);
            }

            $stmt = $db->prepare("INSERT INTO activity_logs (user_id, project_id, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $projectId, $action, $details, $ip]);
        } catch (\Exception $e) {
            // Silently fail logging if it fails, to avoid breaking main functionality
            error_log("Logging failed: " . $e->getMessage());
        }
    }
}
