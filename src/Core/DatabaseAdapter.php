<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Abstract Database Adapter
 * 
 * Provides a unified interface for different database engines (SQLite, MySQL, PostgreSQL, etc.)
 * Each specific adapter must implement the connection logic for its database type.
 * 
 * @package App\Core
 */
abstract class DatabaseAdapter
{
    /** @var PDO|null PDO connection instance */
    protected $connection = null;

    /** @var array Database configuration */
    protected $config = [];

    /** @var string Database type (sqlite, mysql, pgsql, etc.) */
    protected $type;

    /**
     * Constructor
     * 
     * @param array $config Database configuration array
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->type = $config['type'] ?? 'sqlite';
    }

    /**
     * Establish database connection
     * Must be implemented by each specific adapter
     * 
     * @return PDO
     * @throws PDOException
     */
    abstract protected function connect(): PDO;

    /**
     * Create a new database
     * 
     * @param string $databaseName Name of the database to create
     * @return bool True on success
     */
    abstract public function createDatabase(string $databaseName): bool;

    /**
     * Get the PDO connection, creating it if necessary
     * 
     * @return PDO
     */
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connection = $this->connect();
            $this->configureConnection();
        }
        return $this->connection;
    }

    /**
     * Configure PDO connection with common settings
     * 
     * @return void
     */
    protected function configureConnection(): void
    {
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    /**
     * Get database type
     * 
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get database configuration
     * 
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Test if connection is alive
     * 
     * @return bool
     */
    public function isConnected(): bool
    {
        try {
            if ($this->connection === null) {
                return false;
            }
            $this->connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Close the database connection
     * 
     * @return void
     */
    public function disconnect(): void
    {
        $this->connection = null;
    }

    /**
     * Get the last insert ID
     * 
     * @param string|null $name Sequence name (for PostgreSQL)
     * @return string
     */
    public function lastInsertId(?string $name = null): string
    {
        return $this->getConnection()->lastInsertId($name);
    }

    /**
     * Begin a transaction
     * 
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->getConnection()->beginTransaction();
    }

    /**
     * Commit a transaction
     * 
     * @return bool
     */
    public function commit(): bool
    {
        return $this->getConnection()->commit();
    }

    /**
     * Rollback a transaction
     * 
     * @return bool
     */
    public function rollback(): bool
    {
        return $this->getConnection()->rollBack();
    }

    /**
     * Get list of all tables in the database
     * 
     * @return array
     */
    public function getTables(): array
    {
        $sql = $this->getListTablesSQL();
        return $this->getConnection()->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get database-specific SQL for listing tables
     * 
     * @return string
     */
    abstract public function getListTablesSQL(): string;

    /**
     * Get database-specific SQL for getting table structure
     * 
     * @param string $tableName
     * @return string
     */
    abstract public function getTableStructureSQL(string $tableName): string;

    /**
     * Get database-specific SQL for checking if a table exists
     * 
     * @param string $tableName
     * @return string
     */
    abstract public function getTableExistsSQL(string $tableName): string;

    /**
     * Get database size in bytes
     * Must be implemented by each specific adapter
     * 
     * @return int Size in bytes
     */
    abstract public function getDatabaseSize(): int;

    /**
     * Optimize the database
     * Implementation depends on database type (VACUUM for SQLite, OPTIMIZE for MySQL, etc.)
     * 
     * @return bool Success status
     */
    abstract public function optimize(): bool;

    /**
     * Create a new table with standard fields
     * 
     * @param string $tableName Name of the table to create
     * @return bool True on success
     */
    abstract public function createTable(string $tableName): bool;

    /**
     * Delete a table from the database
     * 
     * @param string $tableName Name of the table to delete
     * @return bool True on success
     */
    abstract public function deleteTable(string $tableName): bool;

    /**
     * Add a new column to an existing table
     * 
     * @param string $tableName Table name
     * @param string $columnName Column name
     * @param string $columnType Data type (e.g., 'TEXT', 'INTEGER', 'DATETIME')
     * @return bool True on success
     */
    abstract public function addColumn(string $tableName, string $columnName, string $columnType): bool;

    /**
     * Delete a column from a table
     * 
     * @param string $tableName Table name
     * @param string $columnName Column name to delete
     * @return bool True on success
     */
    abstract public function deleteColumn(string $tableName, string $columnName): bool;

    /**
     * Quote a table or column name based on database type
     * 
     * @param string $name Name to quote
     * @return string Quoted name
     */
    abstract public function quoteName(string $name): string;

    /**
     * Generate UPSERT SQL (INSERT or UPDATE if exists)
     * Handles database-specific syntax for REPLACE/UPSERT operations
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @param string|array $conflictColumns Column(s) that determine uniqueness
     * @return string SQL statement
     */
    public function getUpsertSQL(string $table, array $data, $conflictColumns): string
    {
        $quotedTable = $this->quoteName($table);
        $columns = array_keys($data);
        $quotedColumns = array_map([$this, 'quoteName'], $columns);
        $placeholders = array_fill(0, count($columns), '?');

        if ($this->type === 'pgsql' || $this->type === 'postgresql') {
            // PostgreSQL: INSERT ... ON CONFLICT ... DO UPDATE
            $conflictCols = is_array($conflictColumns) ? $conflictColumns : [$conflictColumns];
            $quotedConflictCols = array_map([$this, 'quoteName'], $conflictCols);
            $conflictStr = implode(', ', $quotedConflictCols);

            $updateParts = [];
            foreach ($columns as $col) {
                if (!in_array($col, $conflictCols)) {
                    $qCol = $this->quoteName($col);
                    $updateParts[] = "$qCol = EXCLUDED.$qCol";
                }
            }

            $sql = "INSERT INTO $quotedTable (" . implode(', ', $quotedColumns) . ") " .
                "VALUES (" . implode(', ', $placeholders) . ") " .
                "ON CONFLICT ($conflictStr) DO UPDATE SET " . implode(', ', $updateParts);

        } else {
            // MySQL and SQLite: REPLACE INTO
            $sql = "REPLACE INTO $quotedTable (" . implode(', ', $quotedColumns) . ") " .
                "VALUES (" . implode(', ', $placeholders) . ")";
        }

        return $sql;
    }

    /**
     * Quote a value for use in a query
     * 
     * @param mixed $value Value to quote
     * @return string Quoted value
     */
    public function quote($value): string
    {
        return $this->getConnection()->quote($value);
    }

    /**
     * Get SQL for date difference in days
     * 
     * @param string $date1 Later date (e.g. 'now', 'column_name')
     * @param string $date2 Earlier date
     * @return string SQL snippet
     */
    abstract public function getDateDiffSQL(string $date1, string $date2): string;

    /**
     * Get SQL for formatting a date column
     * 
     * @param string $column Column name
     * @param string $format Format type ('Y-m', 'Y', 'm', 'Y-m-d')
     * @return string SQL snippet
     */
    abstract public function getDateFormatSQL(string $column, string $format): string;

    /**
     * Get SQL for the start of the current month
     * 
     * @return string SQL snippet
     */
    abstract public function getStartOfMonthSQL(): string;

    /**
     * Get SQL for subtracting an interval from a date
     * 
     * @param string $date Base date ('now', 'column_name', or 'YYYY-MM-DD')
     * @param int $amount Amount to subtract
     * @param string $unit Unit ('day', 'month', 'year')
     * @return string SQL snippet
     */
    abstract public function getDateSubSQL(string $date, int $amount, string $unit): string;

    /**
     * Get SQL for current date/timestamp
     * 
     * @param bool $includeTime Whether to include time
     * @return string SQL snippet
     */
    abstract public function getCurrentDateSQL(bool $includeTime = false): string;

    /**
     * Get SQL for string concatenation
     * 
     * @param array $parts Array of SQL snippets/columns to concatenate
     * @return string SQL snippet
     */
    abstract public function getConcatSQL(array $parts): string;
}
