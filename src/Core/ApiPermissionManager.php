<?php

namespace App\Core;

use PDO;

/**
 * API Permission Manager
 * 
 * Manages granular permissions for API keys including:
 * - Database-level access control
 * - Table-level CRUD permissions
 * - IP whitelisting
 * 
 * @package App\Core
 * @version 1.0.0
 */
class ApiPermissionManager
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Check if API key has permission for an operation
     * 
     * @param int $apiKeyId API key ID
     * @param int $databaseId Database ID
     * @param string $tableName Table name
     * @param string $operation Operation: 'read', 'create', 'update', 'delete'
     * @return bool True if allowed
     */
    public function hasPermission($apiKeyId, $databaseId, $tableName, $operation)
    {
        // Map operation to column name
        $permissionColumn = match ($operation) {
            'read', 'get' => 'can_read',
            'create', 'post' => 'can_create',
            'update', 'put', 'patch' => 'can_update',
            'delete' => 'can_delete',
            default => null
        };

        if (!$permissionColumn) {
            return false;
        }

        $adapter = Database::getInstance()->getAdapter();
        $table = $adapter->quoteName('api_key_permissions');

        // Check for specific table permission
        $stmt = $this->db->prepare("
            SELECT $permissionColumn 
            FROM $table 
            WHERE api_key_id = ? 
            AND database_id = ? 
            AND (table_name = ? OR table_name IS NULL)
            ORDER BY table_name DESC
            LIMIT 1
        ");
        $stmt->execute([$apiKeyId, $databaseId, $tableName]);
        $result = $stmt->fetchColumn();

        // If no specific permission found, check for wildcard (table_name IS NULL)
        if ($result === false) {
            $stmt = $this->db->prepare("
                SELECT $permissionColumn 
                FROM $table 
                WHERE api_key_id = ? 
                AND database_id = ? 
                AND table_name IS NULL
                LIMIT 1
            ");
            $stmt->execute([$apiKeyId, $databaseId]);
            $result = $stmt->fetchColumn();
        }

        return (bool) $result;
    }

    /**
     * Check if request IP is whitelisted for this API key
     * 
     * @param int $apiKeyId API key ID
     * @param string $ip Client IP address
     * @return bool True if allowed (or no whitelist configured)
     */
    public function checkIpWhitelist($apiKeyId, $ip)
    {
        $adapter = Database::getInstance()->getAdapter();
        $table = $adapter->quoteName('api_key_permissions');

        $stmt = $this->db->prepare("
            SELECT allowed_ips 
            FROM $table 
            WHERE api_key_id = ? 
            AND allowed_ips IS NOT NULL 
            AND allowed_ips != ''
            LIMIT 1
        ");
        $stmt->execute([$apiKeyId]);
        $allowedIps = $stmt->fetchColumn();

        // No whitelist = allow all
        if (!$allowedIps) {
            return true;
        }

        // Parse comma-separated IPs
        $whitelist = array_map('trim', explode(',', $allowedIps));

        // Check for exact match or CIDR range
        foreach ($whitelist as $allowed) {
            if ($allowed === $ip) {
                return true;
            }

            // Check CIDR notation (e.g., 192.168.1.0/24)
            if (strpos($allowed, '/') !== false && $this->ipInRange($ip, $allowed)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set permissions for an API key
     * 
     * @param int $apiKeyId API key ID
     * @param int $databaseId Database ID
     * @param string|null $tableName Table name (null for all tables)
     * @param array $permissions ['read' => true, 'create' => false, ...]
     * @param string|null $allowedIps Comma-separated IPs
     * @return bool Success
     */
    public function setPermissions($apiKeyId, $databaseId, $tableName, $permissions, $allowedIps = null)
    {
        $adapter = Database::getInstance()->getAdapter();
        $table = $adapter->quoteName('api_key_permissions');

        // Check if permission already exists
        // Use COALESCE to handle NULL comparison properly for PostgreSQL
        $stmt = $this->db->prepare("
            SELECT id FROM $table 
            WHERE api_key_id = ? AND database_id = ? 
            AND COALESCE(table_name, '') = COALESCE(?, '')
        ");
        $stmt->execute([$apiKeyId, $databaseId, $tableName]);
        $existing = $stmt->fetchColumn();

        $canRead = (int) ($permissions['read'] ?? 0);
        $canCreate = (int) ($permissions['create'] ?? 0);
        $canUpdate = (int) ($permissions['update'] ?? 0);
        $canDelete = (int) ($permissions['delete'] ?? 0);

        if ($existing) {
            // Update existing
            $stmt = $this->db->prepare("
                UPDATE $table 
                SET can_read = ?, can_create = ?, can_update = ?, can_delete = ?, allowed_ips = ?
                WHERE id = ?
            ");
            return $stmt->execute([$canRead, $canCreate, $canUpdate, $canDelete, $allowedIps, $existing]);
        } else {
            // Insert new
            $stmt = $this->db->prepare("
                INSERT INTO $table 
                (api_key_id, database_id, table_name, can_read, can_create, can_update, can_delete, allowed_ips) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$apiKeyId, $databaseId, $tableName, $canRead, $canCreate, $canUpdate, $canDelete, $allowedIps]);
        }
    }

    /**
     * Get all permissions for an API key
     * 
     * @param int $apiKeyId API key ID
     * @return array Permissions grouped by database and table
     */
    public function getPermissions($apiKeyId)
    {
        $adapter = Database::getInstance()->getAdapter();
        $apiKeyPermissionsTable = $adapter->quoteName('api_key_permissions');
        $databasesTable = $adapter->quoteName('databases');

        $stmt = $this->db->prepare("
            SELECT 
                p.*,
                d.name as database_name
            FROM $apiKeyPermissionsTable p
            LEFT JOIN $databasesTable d ON p.database_id = d.id
            WHERE p.api_key_id = ?
            ORDER BY d.name, p.table_name
        ");
        $stmt->execute([$apiKeyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Delete all permissions for an API key
     * 
     * @param int $apiKeyId API key ID
     * @return int Number of deleted records
     */
    public function deletePermissions($apiKeyId)
    {
        $adapter = Database::getInstance()->getAdapter();
        $table = $adapter->quoteName('api_key_permissions');

        $stmt = $this->db->prepare("DELETE FROM $table WHERE api_key_id = ?");
        $stmt->execute([$apiKeyId]);
        return $stmt->rowCount();
    }

    /**
     * Check if IP is in CIDR range
     * 
     * @param string $ip IP to check
     * @param string $range CIDR notation (e.g., 192.168.1.0/24)
     * @return bool
     */
    private function ipInRange($ip, $range)
    {
        list($subnet, $mask) = explode('/', $range);

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - (int) $mask);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
}
