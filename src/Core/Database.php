<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Database Management Class
 * Implements the Singleton pattern to provide a single PDO connection to the system SQLite database.
 */
class Database
{
    /** @var Database|null Singleton instance */
    private static $instance = null;

    /** @var PDO PDO database connection object */
    private $connection;

    /**
     * Private constructor to prevent direct instantiation.
     * Initializes the PDO connection using the path from Config.
     */
    private function __construct()
    {
        $dbPath = Config::get('db_path');
        try {
            $this->connection = new PDO('sqlite:' . $dbPath);
            // Configure Error Mode to Exceptions for better debugging
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Set Default Fetch Mode to Associative Array
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
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
     * 
     * @return PDO
     */
    public function getConnection()
    {
        return $this->connection;
    }
}

