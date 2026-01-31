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
    public function quoteName(string $name): string
    {
        return '`' . str_replace('`', '``', $name) . '`';
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
     * Create a new table with standard fields for MySQL
     * 
     * @param string $tableName Name of the table to create
     * @return bool True on success
     */
    public function createTable(string $tableName): bool
    {
        $connection = $this->getConnection();
        $connection->exec("CREATE TABLE " . $this->quoteName($tableName) . " (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fecha_de_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
            fecha_edicion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
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
        $connection->exec("ALTER TABLE " . $this->quoteName($tableName) . " DROP COLUMN " . $this->quoteName($columnName));
        return true;
    }

    /**
     * Create a new database
     * 
     * @param string $databaseName Name of the database to create
     * @return bool True on success
     */
    public function createDatabase(string $databaseName): bool
    {
        // Connect to the server without selecting a database first
        $host = $this->config['host'] ?? 'localhost';
        $port = $this->config['port'] ?? 3306;
        $username = $this->config['username'] ?? 'root';
        $password = $this->config['password'] ?? '';
        $charset = $this->config['charset'] ?? 'utf8mb4';

        $dsn = sprintf(
            'mysql:host=%s;port=%d;charset=%s',
            $host,
            $port,
            $charset
        );

        try {
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            $pdo->exec("CREATE DATABASE IF NOT EXISTS " . $this->quoteName($databaseName) . " CHARACTER SET $charset COLLATE {$charset}_unicode_ci");
            return true;
        } catch (PDOException $e) {
            throw new PDOException("MySQL Database Creation Failed: " . $e->getMessage());
        }
    }

    /**
     * Get SQL for date difference in days for MySQL
     */
    public function getDateDiffSQL(string $date1, string $date2): string
    {
        $d1 = ($date1 === 'now') ? 'NOW()' : $date1;
        $d2 = ($date2 === 'now') ? 'NOW()' : $date2;
        return "DATEDIFF($d1, $d2)";
    }

    /**
     * Get SQL for formatting a date column in MySQL
     */
    public function getDateFormatSQL(string $column, string $format): string
    {
        $sqlFormat = match ($format) {
            'Y-m' => '%Y-%m',
            'Y' => '%Y',
            'm' => '%m',
            'H:i' => '%H:%i',
            'Y-m-d' => '%Y-%m-%d',
            'Y-m-d H:i' => '%Y-%m-%d %H:%i',
            'Y-m-d H:00' => '%Y-%m-%d %H:00',
            default => $format
        };
        return "DATE_FORMAT($column, '$sqlFormat')";
    }

    /**
     * Get SQL for subtracting an interval from a date in MySQL
     */
    public function getDateSubSQL(string $date, int $amount, string $unit): string
    {
        $d = ($date === 'now') ? 'NOW()' : $date;
        $unit = strtoupper($unit);
        return "DATE_SUB($d, INTERVAL $amount $unit)";
    }

    /**
     * Get SQL for the start of the current month in MySQL
     */
    public function getStartOfMonthSQL(): string
    {
        return "DATE_FORMAT(NOW(), '%Y-%m-01')";
    }

    /**
     * Get SQL for current date/timestamp in MySQL
     */
    public function getCurrentDateSQL(bool $includeTime = false): string
    {
        return $includeTime ? "NOW()" : "CURDATE()";
    }

    /**
     * Get SQL for string concatenation in MySQL
     */
    public function getConcatSQL(array $parts): string
    {
        return "CONCAT(" . implode(', ', $parts) . ")";
    }
}
