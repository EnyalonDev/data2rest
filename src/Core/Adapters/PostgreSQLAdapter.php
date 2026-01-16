<?php

namespace App\Core\Adapters;

use App\Core\DatabaseAdapter;
use PDO;
use PDOException;

/**
 * PostgreSQL Database Adapter
 * 
 * Provides PostgreSQL-specific implementation for database operations.
 * Handles connection management, schema operations, and query execution
 * optimized for PostgreSQL databases.
 * 
 * @package App\Core\Adapters
 * @author DATA2REST Development Team
 * @version 1.0.0
 */
class PostgreSQLAdapter extends DatabaseAdapter
{
    /**
     * Constructor
     * 
     * @param array $config Database configuration array with keys:
     *                      - host: PostgreSQL server host
     *                      - port: PostgreSQL server port (default: 5432)
     *                      - database: Database name
     *                      - username: Database user
     *                      - password: Database password
     *                      - charset: Character set (default: utf8)
     *                      - schema: Database schema (default: public)
     */
    public function __construct(array $config)
    {
        parent::__construct($config);
    }

    /**
     * Establish connection to PostgreSQL database
     * 
     * @return PDO PostgreSQL PDO connection instance
     * @throws PDOException If connection fails
     */
    public function connect(): PDO
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        $host = $this->config['host'] ?? 'localhost';
        $port = $this->config['port'] ?? 5432;
        $database = $this->config['database'] ?? '';
        $username = $this->config['username'] ?? '';
        $password = $this->config['password'] ?? '';
        $charset = $this->config['charset'] ?? 'utf8';

        $dsn = "pgsql:host={$host};port={$port};dbname={$database};options='--client_encoding={$charset}'";

        try {
            $this->connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            return $this->connection;
        } catch (PDOException $e) {
            throw new PDOException("PostgreSQL Connection Error: " . $e->getMessage(), (int) $e->getCode());
        }
    }

    /**
     * Get existing PDO connection or create new one
     * 
     * @return PDO Active database connection
     */
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }

    /**
     * Get database type identifier
     * 
     * @return string Returns 'pgsql'
     */
    public function getType(): string
    {
        return 'pgsql';
    }

    /**
     * Get list of all tables in the database
     * 
     * Uses PostgreSQL's information_schema to retrieve table names
     * from the public schema (default schema).
     * 
     * @return array Array of table names
     */
    public function getTables(): array
    {
        $connection = $this->getConnection();

        // Get tables from public schema (default)
        $schema = $this->config['schema'] ?? 'public';

        $stmt = $connection->prepare("
            SELECT table_name 
            FROM information_schema.tables 
            WHERE table_schema = ? 
            AND table_type = 'BASE TABLE'
            ORDER BY table_name
        ");
        $stmt->execute([$schema]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get columns information for a specific table
     * 
     * Returns detailed column metadata including name, type, nullable status,
     * default values, and primary key information.
     * 
     * @param string $table Table name
     * @return array Array of column information with keys:
     *               - name: Column name
     *               - type: PostgreSQL data type
     *               - notnull: Whether column is NOT NULL (1 or 0)
     *               - dflt_value: Default value
     *               - pk: Whether column is primary key (1 or 0)
     */
    public function getColumns(string $table): array
    {
        $connection = $this->getConnection();
        $schema = $this->config['schema'] ?? 'public';

        // Get column information
        $stmt = $connection->prepare("
            SELECT 
                c.column_name as name,
                c.data_type as type,
                CASE WHEN c.is_nullable = 'NO' THEN 1 ELSE 0 END as notnull,
                c.column_default as dflt_value,
                CASE WHEN pk.column_name IS NOT NULL THEN 1 ELSE 0 END as pk
            FROM information_schema.columns c
            LEFT JOIN (
                SELECT ku.column_name
                FROM information_schema.table_constraints tc
                JOIN information_schema.key_column_usage ku
                    ON tc.constraint_name = ku.constraint_name
                    AND tc.table_schema = ku.table_schema
                WHERE tc.constraint_type = 'PRIMARY KEY'
                    AND tc.table_name = ?
                    AND tc.table_schema = ?
            ) pk ON c.column_name = pk.column_name
            WHERE c.table_name = ?
                AND c.table_schema = ?
            ORDER BY c.ordinal_position
        ");

        $stmt->execute([$table, $schema, $table, $schema]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new table with standard fields
     * 
     * Creates a PostgreSQL table with:
     * - id: SERIAL PRIMARY KEY (auto-increment)
     * - fecha_de_creacion: TIMESTAMP DEFAULT CURRENT_TIMESTAMP
     * - fecha_edicion: TIMESTAMP DEFAULT CURRENT_TIMESTAMP
     * 
     * @param string $tableName Name of the table to create
     * @return bool True on success
     * @throws PDOException If table creation fails
     */
    public function createTable(string $tableName): bool
    {
        $connection = $this->getConnection();

        $sql = "CREATE TABLE \"{$tableName}\" (
            id SERIAL PRIMARY KEY,
            fecha_de_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_edicion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        $connection->exec($sql);

        // Create trigger for auto-updating fecha_edicion
        $triggerSql = "
            CREATE OR REPLACE FUNCTION update_fecha_edicion()
            RETURNS TRIGGER AS $$
            BEGIN
                NEW.fecha_edicion = CURRENT_TIMESTAMP;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            DROP TRIGGER IF EXISTS trigger_update_fecha_edicion ON \"{$tableName}\";
            
            CREATE TRIGGER trigger_update_fecha_edicion
            BEFORE UPDATE ON \"{$tableName}\"
            FOR EACH ROW
            EXECUTE FUNCTION update_fecha_edicion();
        ";

        $connection->exec($triggerSql);

        return true;
    }

    /**
     * Delete a table from the database
     * 
     * @param string $tableName Name of the table to delete
     * @return bool True on success
     * @throws PDOException If table deletion fails
     */
    public function deleteTable(string $tableName): bool
    {
        $connection = $this->getConnection();
        $connection->exec("DROP TABLE IF EXISTS \"{$tableName}\" CASCADE");
        return true;
    }

    /**
     * Add a new column to an existing table
     * 
     * @param string $tableName Table name
     * @param string $columnName Column name
     * @param string $columnType PostgreSQL data type (e.g., 'VARCHAR(255)', 'INTEGER', 'TEXT')
     * @return bool True on success
     * @throws PDOException If column addition fails
     */
    public function addColumn(string $tableName, string $columnName, string $columnType): bool
    {
        $connection = $this->getConnection();

        // Map common types to PostgreSQL types
        $typeMap = [
            'TEXT' => 'TEXT',
            'INTEGER' => 'INTEGER',
            'REAL' => 'REAL',
            'BLOB' => 'BYTEA',
            'VARCHAR' => 'VARCHAR(255)',
        ];

        $pgType = $typeMap[strtoupper($columnType)] ?? $columnType;

        $sql = "ALTER TABLE \"{$tableName}\" ADD COLUMN \"{$columnName}\" {$pgType}";
        $connection->exec($sql);

        return true;
    }

    /**
     * Delete a column from a table
     * 
     * @param string $tableName Table name
     * @param string $columnName Column name to delete
     * @return bool True on success
     * @throws PDOException If column deletion fails
     */
    public function deleteColumn(string $tableName, string $columnName): bool
    {
        $connection = $this->getConnection();
        $sql = "ALTER TABLE \"{$tableName}\" DROP COLUMN \"{$columnName}\"";
        $connection->exec($sql);

        return true;
    }

    /**
     * Execute raw SQL statement
     * 
     * @param string $sql SQL statement to execute
     * @return bool True on success
     * @throws PDOException If execution fails
     */
    public function executeRawSql(string $sql): bool
    {
        $connection = $this->getConnection();
        $connection->exec($sql);
        return true;
    }

    /**
     * Test database connection
     * 
     * @return bool True if connection successful
     */
    public function testConnection(): bool
    {
        try {
            $connection = $this->connect();
            $connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Close database connection
     * 
     * @return void
     */
    public function disconnect(): void
    {
        $this->connection = null;
    }

    /**
     * Get database configuration
     * 
     * @return array Configuration array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Begin transaction
     * 
     * @return bool True on success
     */
    public function beginTransaction(): bool
    {
        return $this->getConnection()->beginTransaction();
    }

    /**
     * Commit transaction
     * 
     * @return bool True on success
     */
    public function commit(): bool
    {
        return $this->getConnection()->commit();
    }

    /**
     * Rollback transaction
     * 
     * @return bool True on success
     */
    public function rollback(): bool
    {
        return $this->getConnection()->rollBack();
    }

    /**
     * Get database-specific SQL for listing tables
     * 
     * @return string SQL query to list all tables
     */
    public function getListTablesSQL(): string
    {
        $schema = $this->config['schema'] ?? 'public';
        return "SELECT table_name FROM information_schema.tables 
                WHERE table_schema = '{$schema}' 
                AND table_type = 'BASE TABLE' 
                ORDER BY table_name";
    }

    /**
     * Get database-specific SQL for getting table structure
     * 
     * @param string $tableName Table name
     * @return string SQL query to get table structure
     */
    public function getTableStructureSQL(string $tableName): string
    {
        $schema = $this->config['schema'] ?? 'public';
        return "SELECT 
                    c.column_name as name,
                    c.data_type as type,
                    c.is_nullable,
                    c.column_default as dflt_value
                FROM information_schema.columns c
                WHERE c.table_name = '{$tableName}'
                    AND c.table_schema = '{$schema}'
                ORDER BY c.ordinal_position";
    }

    /**
     * Get database-specific SQL for checking if a table exists
     * 
     * @param string $tableName Table name
     * @return string SQL query to check table existence
     */
    public function getTableExistsSQL(string $tableName): string
    {
        $schema = $this->config['schema'] ?? 'public';
        return "SELECT EXISTS (
                    SELECT 1 
                    FROM information_schema.tables 
                    WHERE table_schema = '{$schema}' 
                    AND table_name = '{$tableName}'
                )";
    }

    /**
     * Get database size in bytes
     * 
     * @return int Size in bytes
     */
    public function getDatabaseSize(): int
    {
        $connection = $this->getConnection();
        $database = $this->config['database'] ?? '';

        $stmt = $connection->query("SELECT pg_database_size('{$database}')");
        $size = $stmt->fetchColumn();

        return (int) $size;
    }

    /**
     * Optimize the database
     * Runs VACUUM ANALYZE on PostgreSQL
     * 
     * @return bool Success status
     */
    public function optimize(): bool
    {
        try {
            $connection = $this->getConnection();
            $connection->exec('VACUUM ANALYZE');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Quote a table or column name for PostgreSQL
     * 
     * @param string $name Name to quote
     * @return string Quoted name
     */
    public function quoteName(string $name): string
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }

    /**
     * Create a new database
     * 
     * Connects to the default 'postgres' database to execute
     * the CREATE DATABASE command.
     * 
     * @param string $dbName Name of the database to create
     * @return bool True on success
     */
    public function createDatabase(string $dbName): bool
    {
        try {
            // Connect to default 'postgres' database to create new DB
            $host = $this->config['host'] ?? 'localhost';
            $port = $this->config['port'] ?? 5432;
            $user = $this->config['username'] ?? 'postgres';
            $pass = $this->config['password'] ?? '';

            $dsn = "pgsql:host={$host};port={$port};dbname=postgres";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            // Check if exists
            $stmt = $pdo->prepare("SELECT 1 FROM pg_database WHERE datname = ?");
            $stmt->execute([$dbName]);
            if ($stmt->fetchColumn()) {
                return true; // Already exists
            }

            // Create (CREATE DATABASE cannot be prepared, and quoting needs care)
            // We use standard identifier quoting
            $safeName = str_replace('"', '""', $dbName);
            $pdo->exec("CREATE DATABASE \"{$safeName}\"");

            return true;
        } catch (PDOException $e) {
            // Log error if needed: error_log($e->getMessage());
            return false;
        }
    }
}
