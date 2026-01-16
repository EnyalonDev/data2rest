<?php

/**
 * Database Migration: Add Multi-Database Support
 * 
 * This migration updates the databases table to support multiple database engines.
 * It adds a 'type' column and ensures the 'config' column exists for storing
 * database-specific configuration (credentials, host, port, etc.)
 * 
 * Run this script once to migrate existing databases.
 */

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

// Load autoloader
require_once __DIR__ . '/../src/autoload.php';

use App\Core\Database;
use App\Core\Config;

// Load environment variables
Config::loadEnv();

try {
    $db = Database::getInstance()->getConnection();

    echo "Starting database migration for multi-database support...\n\n";

    // Check if 'type' column exists
    $columns = $db->query("PRAGMA table_info(databases)")->fetchAll();
    $hasTypeColumn = false;
    $hasConfigColumn = false;

    foreach ($columns as $column) {
        if ($column['name'] === 'type') {
            $hasTypeColumn = true;
        }
        if ($column['name'] === 'config') {
            $hasConfigColumn = true;
        }
    }

    // Add 'type' column if it doesn't exist
    if (!$hasTypeColumn) {
        echo "Adding 'type' column to databases table...\n";
        $db->exec("ALTER TABLE databases ADD COLUMN type TEXT DEFAULT 'sqlite'");
        echo "✓ 'type' column added successfully\n\n";
    } else {
        echo "✓ 'type' column already exists\n\n";
    }

    // The 'config' column should already exist based on the schema we saw
    if (!$hasConfigColumn) {
        echo "Adding 'config' column to databases table...\n";
        $db->exec("ALTER TABLE databases ADD COLUMN config TEXT");
        echo "✓ 'config' column added successfully\n\n";
    } else {
        echo "✓ 'config' column already exists\n\n";
    }

    // Update existing records to have proper type and config
    echo "Updating existing database records...\n";
    $databases = $db->query("SELECT id, path, config FROM databases")->fetchAll();

    $updateStmt = $db->prepare("UPDATE databases SET type = ?, config = ? WHERE id = ?");

    foreach ($databases as $database) {
        $config = [];

        // Parse existing config if any
        if (!empty($database['config'])) {
            $existingConfig = json_decode($database['config'], true);
            if (is_array($existingConfig)) {
                $config = $existingConfig;
            }
        }

        // Set type to sqlite if not already set
        if (!isset($config['type'])) {
            $config['type'] = 'sqlite';
        }

        // Set path in config
        if (!isset($config['path']) && !empty($database['path'])) {
            $config['path'] = $database['path'];
        }

        $updateStmt->execute([
            $config['type'],
            json_encode($config),
            $database['id']
        ]);
    }

    echo "✓ Updated " . count($databases) . " database record(s)\n\n";

    echo "Migration completed successfully!\n";
    echo "\nYou can now use multiple database engines:\n";
    echo "- SQLite (default)\n";
    echo "- MySQL/MariaDB\n";
    echo "- More engines can be added in the future\n";

} catch (\Exception $e) {
    echo "ERROR: Migration failed!\n";
    echo $e->getMessage() . "\n";
    exit(1);
}
