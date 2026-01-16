<?php

/**
 * Database Connection Helper
 * 
 * This helper provides backward-compatible functions to gradually migrate
 * existing code to use the new DatabaseManager.
 * 
 * Usage:
 * Instead of: $targetDb = new PDO('sqlite:' . $database['path']);
 * Use: $targetDb = getProjectDatabase($database);
 */

use App\Core\DatabaseManager;
use App\Core\DatabaseFactory;

if (!function_exists('getProjectDatabase')) {
    /**
     * Get PDO connection for a project database
     * 
     * @param array $database Database record from databases table
     * @return PDO
     */
    function getProjectDatabase(array $database): PDO
    {
        return DatabaseManager::getConnection($database);
    }
}

if (!function_exists('getProjectDatabaseAdapter')) {
    /**
     * Get database adapter for a project database
     * 
     * @param array $database Database record from databases table
     * @return \App\Core\DatabaseAdapter
     */
    function getProjectDatabaseAdapter(array $database): \App\Core\DatabaseAdapter
    {
        return DatabaseManager::getAdapter($database);
    }
}

if (!function_exists('getDatabaseById')) {
    /**
     * Get PDO connection by database ID
     * 
     * @param int $databaseId Database ID
     * @return PDO|null
     */
    function getDatabaseById(int $databaseId): ?PDO
    {
        return DatabaseManager::getConnectionById($databaseId);
    }
}

if (!function_exists('testDatabaseConnection')) {
    /**
     * Test a database connection
     * 
     * @param array $config Database configuration
     * @return array ['success' => bool, 'message' => string, 'type' => string]
     */
    function testDatabaseConnection(array $config): array
    {
        return DatabaseManager::testConnection($config);
    }
}

if (!function_exists('createProjectDatabase')) {
    /**
     * Create a new project database
     * 
     * @param string $name Database name
     * @param array $config Database configuration
     * @param int|null $projectId Associated project ID
     * @return array|null Created database record or null on failure
     */
    function createProjectDatabase(string $name, array $config, ?int $projectId = null): ?array
    {
        return DatabaseManager::createDatabase($name, $config, $projectId);
    }
}

if (!function_exists('getDatabaseType')) {
    /**
     * Get the type of a database
     * 
     * @param array $database Database record
     * @return string Database type ('sqlite', 'mysql', etc.)
     */
    function getDatabaseType(array $database): string
    {
        $adapter = DatabaseManager::getAdapter($database);
        return $adapter->getType();
    }
}

if (!function_exists('getDatabaseSize')) {
    /**
     * Get the size of a database in bytes
     * 
     * @param array $database Database record
     * @return int Size in bytes
     */
    function getDatabaseSize(array $database): int
    {
        $adapter = DatabaseManager::getAdapter($database);

        if (method_exists($adapter, 'getDatabaseSize')) {
            return $adapter->getDatabaseSize();
        }

        return 0;
    }
}

if (!function_exists('optimizeDatabase')) {
    /**
     * Optimize a database (VACUUM for SQLite, OPTIMIZE for MySQL)
     * 
     * @param array $database Database record
     * @return bool Success status
     */
    function optimizeDatabase(array $database): bool
    {
        $adapter = DatabaseManager::getAdapter($database);

        if (method_exists($adapter, 'optimize')) {
            return $adapter->optimize();
        }

        return false;
    }
}

if (!function_exists('listDatabaseTables')) {
    /**
     * List all tables in a database
     * 
     * @param array $database Database record
     * @return array Array of table names
     */
    function listDatabaseTables(array $database): array
    {
        $adapter = DatabaseManager::getAdapter($database);
        $pdo = $adapter->getConnection();

        $sql = $adapter->getListTablesSQL();
        $stmt = $pdo->query($sql);

        $tables = [];
        while ($row = $stmt->fetch()) {
            $tables[] = $row['name'];
        }

        return $tables;
    }
}

if (!function_exists('tableExists')) {
    /**
     * Check if a table exists in a database
     * 
     * @param array $database Database record
     * @param string $tableName Table name to check
     * @return bool True if table exists
     */
    function tableExists(array $database, string $tableName): bool
    {
        $adapter = DatabaseManager::getAdapter($database);
        $pdo = $adapter->getConnection();

        $sql = $adapter->getTableExistsSQL($tableName);
        $stmt = $pdo->query($sql);

        return $stmt->fetch() !== false;
    }
}

if (!function_exists('getTableStructure')) {
    /**
     * Get the structure of a table
     * 
     * @param array $database Database record
     * @param string $tableName Table name
     * @return array Array of column information
     */
    function getTableStructure(array $database, string $tableName): array
    {
        $adapter = DatabaseManager::getAdapter($database);
        $pdo = $adapter->getConnection();

        $sql = $adapter->getTableStructureSQL($tableName);
        return $pdo->query($sql)->fetchAll();
    }
}
