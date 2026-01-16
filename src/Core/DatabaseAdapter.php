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
     * Quote a value for use in a query
     * 
     * @param mixed $value Value to quote
     * @return string Quoted value
     */
    public function quote($value): string
    {
        return $this->getConnection()->quote($value);
    }
}
