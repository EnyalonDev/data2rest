<?php

namespace App\Core;

use PDO;

/**
 * Database Connection Manager
 * 
 * Manages connections to project databases using the appropriate adapter.
 * Provides a centralized way to get database connections throughout the application.
 * 
 * @package App\Core
 */
class DatabaseManager
{
    /** @var array Cache of database adapters indexed by database ID */
    private static $adapters = [];

    /**
     * Get a database adapter for a specific database record
     * 
     * @param array $database Database record from the databases table
     * @return DatabaseAdapter
     */
    public static function getAdapter(array $database): DatabaseAdapter
    {
        $id = $database['id'] ?? null;

        // Return cached adapter if available
        if ($id && isset(self::$adapters[$id])) {
            return self::$adapters[$id];
        }

        // Create new adapter
        $adapter = DatabaseFactory::createFromDatabaseRecord($database);

        // Cache it
        if ($id) {
            self::$adapters[$id] = $adapter;
        }

        return $adapter;
    }

    /**
     * Get a PDO connection for a specific database record
     * Convenience method for backward compatibility
     * 
     * @param array $database Database record from the databases table
     * @return PDO
     */
    public static function getConnection(array $database): PDO
    {
        return self::getAdapter($database)->getConnection();
    }

    /**
     * Get a database adapter by database ID
     * 
     * @param int $databaseId Database ID
     * @return DatabaseAdapter|null
     */
    public static function getAdapterById(int $databaseId): ?DatabaseAdapter
    {
        // Check cache first
        if (isset(self::$adapters[$databaseId])) {
            return self::$adapters[$databaseId];
        }

        // Fetch from system database
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM databases WHERE id = ?");
            $stmt->execute([$databaseId]);
            $database = $stmt->fetch();

            if (!$database) {
                return null;
            }

            return self::getAdapter($database);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get a PDO connection by database ID
     * 
     * @param int $databaseId Database ID
     * @return PDO|null
     */
    public static function getConnectionById(int $databaseId): ?PDO
    {
        $adapter = self::getAdapterById($databaseId);
        return $adapter ? $adapter->getConnection() : null;
    }

    /**
     * Clear cached adapter for a specific database
     * 
     * @param int $databaseId Database ID
     * @return void
     */
    public static function clearCache(int $databaseId): void
    {
        if (isset(self::$adapters[$databaseId])) {
            self::$adapters[$databaseId]->disconnect();
            unset(self::$adapters[$databaseId]);
        }
    }

    /**
     * Clear all cached adapters
     * 
     * @return void
     */
    public static function clearAllCaches(): void
    {
        foreach (self::$adapters as $adapter) {
            $adapter->disconnect();
        }
        self::$adapters = [];
    }

    /**
     * Create a new database based on configuration
     * 
     * @param string $name Database name
     * @param array $config Database configuration
     * @param int|null $projectId Associated project ID
     * @return array|null Created database record or null on failure
     */
    public static function createDatabase(string $name, array $config, ?int $projectId = null): ?array
    {
        try {
            $db = Database::getInstance()->getConnection();

            // For SQLite, generate path if not provided
            if (($config['type'] ?? 'sqlite') === 'sqlite' && empty($config['path'])) {
                $dbStoragePath = Config::get('db_storage_path');
                $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
                $config['path'] = $dbStoragePath . $safeName . '.sqlite';
            }

            // For MySQL, create the database if it doesn't exist
            if (($config['type'] ?? '') === 'mysql') {
                $adapter = DatabaseFactory::create($config);
                // Note: This requires appropriate permissions
                if (method_exists($adapter, 'createDatabase')) {
                    $adapter->createDatabase($config['database']);
                }
            }

            // For PostgreSQL, create the database if it doesn't exist
            if (($config['type'] ?? '') === 'pgsql') {
                $adapter = DatabaseFactory::create($config);
                if (method_exists($adapter, 'createDatabase')) {
                    $adapter->createDatabase($config['database']);
                }
            }

            // Insert into databases table
            $now = date('Y-m-d H:i:s');
            $adapter = Database::getInstance()->getAdapter();
            $qDatabases = $adapter->quoteName('databases');
            $stmt = $db->prepare(
                "INSERT INTO $qDatabases (name, path, config, project_id, created_at, last_edit_at) 
                 VALUES (?, ?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $name,
                $config['path'] ?? null,
                json_encode($config),
                $projectId,
                $now,
                $now
            ]);

            $id = $db->lastInsertId();

            // Fetch and return the created record
            $stmt = $db->prepare("SELECT * FROM databases WHERE id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            return $result ?: null;

        } catch (\Exception $e) {
            error_log("Failed to create database: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Test database connection
     * 
     * @param array $config Database configuration
     * @return array Result with 'success' boolean and 'message' string
     */
    public static function testConnection(array $config): array
    {
        try {
            $adapter = DatabaseFactory::create($config);
            $connection = $adapter->getConnection();

            // Try a simple query
            $connection->query('SELECT 1');

            return [
                'success' => true,
                'message' => 'Connection successful',
                'type' => $adapter->getType()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'type' => $config['type'] ?? 'unknown'
            ];
        }
    }
}
