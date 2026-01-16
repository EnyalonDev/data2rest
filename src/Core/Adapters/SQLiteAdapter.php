<?php

namespace App\Core\Adapters;

use App\Core\DatabaseAdapter;
use PDO;
use PDOException;

/**
 * SQLite Database Adapter
 * 
 * Handles connections to SQLite databases
 * 
 * Configuration array should contain:
 * - type: 'sqlite'
 * - path: Full path to the SQLite database file
 * 
 * @package App\Core\Adapters
 */
class SQLiteAdapter extends DatabaseAdapter
{
    /**
     * Establish SQLite database connection
     * 
     * @return PDO
     * @throws PDOException
     */
    protected function connect(): PDO
    {
        $path = $this->config['path'] ?? null;

        if (!$path) {
            throw new PDOException("SQLite database path is required");
        }

        // Create directory if it doesn't exist
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        try {
            $dsn = 'sqlite:' . $path;
            $pdo = new PDO($dsn);

            // Enable foreign keys for SQLite
            $pdo->exec('PRAGMA foreign_keys = ON');

            return $pdo;
        } catch (PDOException $e) {
            throw new PDOException("SQLite connection failed: " . $e->getMessage());
        }
    }

    /**
     * Get SQL for listing all tables in SQLite
     * 
     * @return string
     */
    public function getListTablesSQL(): string
    {
        return "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name";
    }

    /**
     * Get SQL for getting table structure in SQLite
     * 
     * @param string $tableName
     * @return string
     */
    public function getTableStructureSQL(string $tableName): string
    {
        return "PRAGMA table_info(" . $this->quoteName($tableName) . ")";
    }

    /**
     * Get SQL for checking if a table exists in SQLite
     * 
     * @param string $tableName
     * @return string
     */
    public function getTableExistsSQL(string $tableName): string
    {
        return "SELECT name FROM sqlite_master WHERE type='table' AND name = " . $this->quote($tableName);
    }

    /**
     * Quote a table or column name for SQLite
     * 
     * @param string $name
     * @return string
     */
    protected function quoteName(string $name): string
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }

    /**
     * Quote a value for SQLite
     * 
     * @param string $value
     * @return string
     */
    protected function quote(string $value): string
    {
        return $this->getConnection()->quote($value);
    }

    /**
     * Get database file size in bytes
     * 
     * @return int
     */
    public function getDatabaseSize(): int
    {
        $path = $this->config['path'] ?? null;
        if ($path && file_exists($path)) {
            return filesize($path);
        }
        return 0;
    }

    /**
     * Optimize (VACUUM) the SQLite database
     * 
     * @return bool
     */
    public function optimize(): bool
    {
        try {
            $this->getConnection()->exec('VACUUM');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}
