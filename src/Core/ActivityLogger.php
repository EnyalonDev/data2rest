<?php

namespace App\Core;

use PDO;

/**
 * Helper para registrar actividades de usuarios en sitios externos
 */
class ActivityLogger
{
    /**
     * Registrar actividad externa (CRUD)
     */
    public static function logExternal($userId, $projectId, $action, $resource, $resourceId, $details = [])
    {
        try {
            $db = Database::getInstance()->getConnection();

            $stmt = $db->prepare("
                INSERT INTO activity_logs 
                (user_id, project_id, action, details, ip_address, created_at)
                VALUES (?, ?, ?, ?, ?, datetime('now'))
            ");

            $stmt->execute([
                $userId,
                $projectId,
                $action,
                json_encode([
                    'resource' => $resource,
                    'resource_id' => $resourceId,
                    'details' => $details
                ]),
                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        } catch (\Exception $e) {
            // No fallar si el log falla
            error_log("Error logging activity: " . $e->getMessage());
        }
    }

    /**
     * Registrar actividad de autenticación
     */
    public static function logAuth($userId, $projectId, $action, $success, $reason = null)
    {
        try {
            $db = Database::getInstance()->getConnection();

            $stmt = $db->prepare("
                INSERT INTO activity_logs 
                (user_id, project_id, action, details, ip_address, created_at)
                VALUES (?, ?, ?, ?, ?, datetime('now'))
            ");

            $stmt->execute([
                $userId,
                $projectId,
                $action,
                json_encode([
                    'success' => $success,
                    'reason' => $reason
                ]),
                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        } catch (\Exception $e) {
            error_log("Error logging auth: " . $e->getMessage());
        }
    }
}
