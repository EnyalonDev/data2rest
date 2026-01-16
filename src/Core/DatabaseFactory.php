<?php

namespace App\Core;

use App\Core\Adapters\SQLiteAdapter;
use App\Core\Adapters\MySQLAdapter;
use InvalidArgumentException;

/**
 * Database Connection Factory
 * 
 * Creates appropriate database adapter instances based on configuration.
 * Supports multiple database types: SQLite, MySQL, PostgreSQL, etc.
 * 
 * @package App\Core
 */
class DatabaseFactory
{
    /** @var array Supported database types and their adapter classes */
    private static $adapters = [
        'sqlite' => SQLiteAdapter::class,
        'mysql' => MySQLAdapter::class,
        // Future support:
        // 'pgsql' => PostgreSQLAdapter::class,
        // 'sqlsrv' => SQLServerAdapter::class,
    ];

    /**
     * Create a database adapter instance based on configuration
     * 
     * @param array $config Database configuration array
     * @return DatabaseAdapter
     * @throws InvalidArgumentException
     */
    public static function create(array $config): DatabaseAdapter
    {
        $type = strtolower($config['type'] ?? 'sqlite');

        if (!isset(self::$adapters[$type])) {
            throw new InvalidArgumentException(
                "Unsupported database type: {$type}. Supported types: " .
                implode(', ', array_keys(self::$adapters))
            );
        }

        $adapterClass = self::$adapters[$type];
        return new $adapterClass($config);
    }

    /**
     * Create a database adapter from a database record
     * 
     * @param array $database Database record from the databases table
     * @return DatabaseAdapter
     */
    public static function createFromDatabaseRecord(array $database): DatabaseAdapter
    {
        // Parse config JSON if exists
        $config = [];
        if (!empty($database['config'])) {
            $config = json_decode($database['config'], true) ?? [];
        }

        // Determine database type
        $type = $config['type'] ?? 'sqlite';

        // Build configuration based on type
        if ($type === 'sqlite') {
            $config = array_merge($config, [
                'type' => 'sqlite',
                'path' => $database['path'] ?? null,
            ]);
        } else {
            // For other database types, config should already contain necessary info
            $config['type'] = $type;
        }

        return self::create($config);
    }

    /**
     * Create a system database adapter (for the main system.sqlite)
     * 
     * @return DatabaseAdapter
     */
    public static function createSystemDatabase(): DatabaseAdapter
    {
        $dbPath = Config::get('db_path');

        return self::create([
            'type' => 'sqlite',
            'path' => $dbPath,
        ]);
    }

    /**
     * Get list of supported database types
     * 
     * @return array
     */
    public static function getSupportedTypes(): array
    {
        return array_keys(self::$adapters);
    }

    /**
     * Check if a database type is supported
     * 
     * @param string $type
     * @return bool
     */
    public static function isTypeSupported(string $type): bool
    {
        return isset(self::$adapters[strtolower($type)]);
    }

    /**
     * Register a custom database adapter
     * 
     * @param string $type Database type identifier
     * @param string $adapterClass Fully qualified adapter class name
     * @return void
     * @throws InvalidArgumentException
     */
    public static function registerAdapter(string $type, string $adapterClass): void
    {
        if (!class_exists($adapterClass)) {
            throw new InvalidArgumentException("Adapter class does not exist: {$adapterClass}");
        }

        if (!is_subclass_of($adapterClass, DatabaseAdapter::class)) {
            throw new InvalidArgumentException(
                "Adapter class must extend " . DatabaseAdapter::class
            );
        }

        self::$adapters[strtolower($type)] = $adapterClass;
    }
}
