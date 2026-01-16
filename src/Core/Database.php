<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Database Management Class
 * 
 * Implements the Singleton pattern to provide a single connection to the system database.
 * Now uses DatabaseAdapter for flexible multi-database support while maintaining backward compatibility.
 * 
 * @package App\Core
 */
class Database
{
    /** @var Database|null Singleton instance */
    private static $instance = null;

    /** @var DatabaseAdapter Database adapter instance */
    private $adapter;

    /**
     * Private constructor to prevent direct instantiation.
     * Initializes the database adapter using DatabaseFactory.
     */
    private function __construct()
    {
        try {
            $this->adapter = DatabaseFactory::createSystemDatabase();
        } catch (\Exception $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Get the singleton instance of the Database class.
     * 
     * @return Database
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the actual PDO connection object.
     * Maintains backward compatibility with existing code.
     * 
     * @return PDO
     */
    public function getConnection()
    {
        return $this->adapter->getConnection();
    }

    /**
     * Get the database adapter instance.
     * Provides access to adapter-specific features.
     * 
     * @return DatabaseAdapter
     */
    public function getAdapter()
    {
        return $this->adapter;
    }
}

