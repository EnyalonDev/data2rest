<?php

namespace App\Core\Adapters;

use App\Core\DatabaseAdapter;
use PDO;
use PDOException;

/**
 * MySQL Database Adapter
 * 
 * Handles connections to MySQL/MariaDB databases
 * 
 * Configuration array should contain:
 * - type: 'mysql'
 * - host: Database host (default: localhost)
 * - port: Database port (default: 3306)
 * - database: Database name
 * - username: Database username
 * - password: Database password
 * - charset: Character set (default: utf8mb4)
 * 
 * @package App\Core\Adapters
 */
class MySQLAdapter extends DatabaseAdapter
{
    /**
     * Establish MySQL database connection
     * 
     * @return PDO
     * @throws PDOException
     */
    protected function connect(): PDO
    {
        $host = $this->config['host'] ?? 'localhost';
        $port = $this->config['port'] ?? 3306;
        $database = $this->config['database'] ?? null;
        $username = $this->config['username'] ?? 'root';
        $password = $this->config['password'] ?? '';
        $charset = $this->config['charset'] ?? 'utf8mb4';

        if (!$database) {
            throw new PDOException("MySQL database name is required");
        }

        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $host,
                $port,
                $database,
                $charset
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}"
            ];

            return new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            throw new PDOException("MySQL connection failed: " . $e->getMessage());
        }
    }

    /**
     * Get SQL for listing all tables in MySQL
     * 
     * @return string
     */
    public function getListTablesSQL(): string
    {
        $database = $this->config['database'] ?? '';
        return "SELECT TABLE_NAME as name FROM information_schema.TABLES 
                WHERE TABLE_SCHEMA = " . $this->quote($database) . " 
                ORDER BY TABLE_NAME";
    }

    /**
     * Get SQL for getting table structure in MySQL
     * 
     * @param string $tableName
     * @return string
     */
    public function getTableStructureSQL(string $tableName): string
    {
        return "DESCRIBE " . $this->quoteName($tableName);
    }

    /**
     * Get SQL for checking if a table exists in MySQL
     * 
     * @param string $tableName
     * @return string
     */
    public function getTableExistsSQL(string $tableName): string
    {
        $database = $this->config['database'] ?? '';
        return "SELECT TABLE_NAME as name FROM information_schema.TABLES 
                WHERE TABLE_SCHEMA = " . $this->quote($database) . " 
                AND TABLE_NAME = " . $this->quote($tableName);
    }

    /**
     * Quote a table or column name for MySQL
     * 
     * @param string $name
     * @return string
     */
    protected function quoteName(string $name): string
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }

    /**
     * Quote a value for MySQL
     * 
     * @param string $value
     * @return string
     */
    protected function quote(string $value): string
    {
        return $this->getConnection()->quote($value);
    }

    /**
     * Get database size in bytes
     * 
     * @return int
     */
    public function getDatabaseSize(): int
    {
        try {
            $database = $this->config['database'] ?? '';
            $sql = "SELECT SUM(data_length + index_length) as size 
                    FROM information_schema.TABLES 
                    WHERE TABLE_SCHEMA = " . $this->quote($database);

            $stmt = $this->getConnection()->query($sql);
            $result = $stmt->fetch();

            return (int) ($result['size'] ?? 0);
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Optimize all tables in the database
     * 
     * @return bool
     */
    public function optimize(): bool
    {
        try {
            $tables = $this->getConnection()->query($this->getListTablesSQL())->fetchAll();

            foreach ($tables as $table) {
                $tableName = $table['name'];
                $this->getConnection()->exec("OPTIMIZE TABLE " . $this->quoteName($tableName));
            }

            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Create database if it doesn't exist
     * Note: Requires connection without database specified
     * 
     * @param string $databaseName
     * @param string $charset
     * @param string $collation
     * @return bool
     */
    public function createDatabase(
        string $databaseName,
        string $charset = 'utf8mb4',
        string $collation = 'utf8mb4_unicode_ci'
    ): bool {
        try {
            $sql = sprintf(
                "CREATE DATABASE IF NOT EXISTS %s CHARACTER SET %s COLLATE %s",
                $this->quoteName($databaseName),
                $charset,
                $collation
            );

            $this->getConnection()->exec($sql);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}
