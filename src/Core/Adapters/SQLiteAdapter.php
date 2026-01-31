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
    public function quoteName(string $name): string
    {
        return '"' . str_replace('"', '""', $name) . '"';
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

    /**
     * Create a new table with standard fields for SQLite
     * 
     * @param string $tableName Name of the table to create
     * @return bool True on success
     */
    public function createTable(string $tableName): bool
    {
        $connection = $this->getConnection();
        $connection->exec("CREATE TABLE " . $this->quoteName($tableName) . " (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            fecha_de_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
            fecha_edicion DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        return true;
    }

    /**
     * Delete a table from the database
     * 
     * @param string $tableName Name of the table to delete
     * @return bool True on success
     */
    public function deleteTable(string $tableName): bool
    {
        $connection = $this->getConnection();
        $connection->exec("DROP TABLE IF EXISTS " . $this->quoteName($tableName));
        return true;
    }

    /**
     * Add a new column to an existing table
     * 
     * @param string $tableName Table name
     * @param string $columnName Column name
     * @param string $columnType Data type (e.g., 'TEXT', 'INTEGER', 'DATETIME')
     * @return bool True on success
     */
    public function addColumn(string $tableName, string $columnName, string $columnType): bool
    {
        $connection = $this->getConnection();
        $connection->exec("ALTER TABLE " . $this->quoteName($tableName) . " ADD COLUMN " . $this->quoteName($columnName) . " $columnType");
        return true;
    }

    /**
     * Delete a column from a table
     * 
     * @param string $tableName Table name
     * @param string $columnName Column name to delete
     * @return bool True on success
     */
    public function deleteColumn(string $tableName, string $columnName): bool
    {
        $connection = $this->getConnection();
        // SQLite supports DROP COLUMN in version 3.35.0+
        $connection->exec("ALTER TABLE " . $this->quoteName($tableName) . " DROP COLUMN " . $this->quoteName($columnName));
        return true;
    }

    /**
     * Create a new database
     * For SQLite, connecting to it creates it, so this checks if we can write.
     * 
     * @param string $databaseName Not used for SQLite file
     * @return bool True on success
     */
    public function createDatabase(string $databaseName): bool
    {
        try {
            $this->getConnection();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get SQL for date difference in days for SQLite
     */
    public function getDateDiffSQL(string $date1, string $date2): string
    {
        $d1 = ($date1 === 'now') ? "'now'" : $date1;
        $d2 = ($date2 === 'now') ? "'now'" : $date2;
        return "JULIANDAY($d1) - JULIANDAY($d2)";
    }

    /**
     * Get SQL for formatting a date column in SQLite
     */
    public function getDateFormatSQL(string $column, string $format): string
    {
        $sqlFormat = match ($format) {
            'Y-m' => '%Y-%m',
            'Y' => '%Y',
            'm' => '%m',
            'H:i' => '%H:%M',
            'Y-m-d' => '%Y-%m-%d',
            'Y-m-d H:i' => '%Y-%m-%d %H:%M',
            'Y-m-d H:00' => '%Y-%m-%d %H:00',
            default => $format
        };
        return "strftime('$sqlFormat', $column)";
    }

    /**
     * Get SQL for subtracting an interval from a date in SQLite
     */
    public function getDateSubSQL(string $date, int $amount, string $unit): string
    {
        $d = ($date === 'now') ? "'now'" : $date;
        $interval = "-$amount $unit" . ($amount > 1 ? 's' : '');
        return "DATE($d, '$interval')";
    }

    /**
     * Get SQL for the start of the current month in SQLite
     */
    public function getStartOfMonthSQL(): string
    {
        return "DATE('now', 'start of month')";
    }

    /**
     * Get SQL for current date/timestamp in SQLite
     */
    public function getCurrentDateSQL(bool $includeTime = false): string
    {
        return $includeTime ? "CURRENT_TIMESTAMP" : "CURRENT_DATE";
    }

    /**
     * Get SQL for string concatenation in SQLite
     */
    public function getConcatSQL(array $parts): string
    {
        return implode(' || ', $parts);
    }
}
