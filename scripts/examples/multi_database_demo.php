<?php

/**
 * Multi-Database Support - Example Usage
 * 
 * This script demonstrates how to use the new multi-database support
 * to create and work with both SQLite and MySQL databases.
 */

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

require_once __DIR__ . '/../../src/autoload.php';

use App\Core\Config;
use App\Core\DatabaseFactory;
use App\Core\DatabaseManager;

// Load environment variables
Config::loadEnv();

echo "=== Multi-Database Support Demo ===\n\n";

// Example 1: Create and test SQLite connection
echo "1. Testing SQLite Connection\n";
echo str_repeat("-", 50) . "\n";

$sqliteConfig = [
    'type' => 'sqlite',
    'path' => __DIR__ . '/../../data/demo_sqlite.sqlite'
];

$result = DatabaseManager::testConnection($sqliteConfig);
if ($result['success']) {
    echo "✓ SQLite connection successful\n";

    $adapter = DatabaseFactory::create($sqliteConfig);
    $pdo = $adapter->getConnection();

    // Create a test table
    $pdo->exec("CREATE TABLE IF NOT EXISTS demo_users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Insert test data
    $stmt = $pdo->prepare("INSERT INTO demo_users (name, email) VALUES (?, ?)");
    $stmt->execute(['John Doe', 'john@example.com']);
    $stmt->execute(['Jane Smith', 'jane@example.com']);

    // Query data
    $users = $pdo->query("SELECT * FROM demo_users")->fetchAll();
    echo "  Created table with " . count($users) . " users\n";

    // Show adapter-specific features
    echo "  Database size: " . round($adapter->getDatabaseSize() / 1024, 2) . " KB\n";
    echo "  Database type: " . $adapter->getType() . "\n";

} else {
    echo "✗ SQLite connection failed: " . $result['message'] . "\n";
}

echo "\n";

// Example 2: Test MySQL connection (if configured)
echo "2. Testing MySQL Connection\n";
echo str_repeat("-", 50) . "\n";

$mysqlConfig = [
    'type' => 'mysql',
    'host' => getenv('MYSQL_HOST') ?: 'localhost',
    'port' => getenv('MYSQL_PORT') ?: 3306,
    'database' => 'data2rest_demo',
    'username' => getenv('MYSQL_USERNAME') ?: 'root',
    'password' => getenv('MYSQL_PASSWORD') ?: '',
    'charset' => getenv('MYSQL_CHARSET') ?: 'utf8mb4'
];

$result = DatabaseManager::testConnection($mysqlConfig);
if ($result['success']) {
    echo "✓ MySQL connection successful\n";

    $adapter = DatabaseFactory::create($mysqlConfig);

    // Try to create database if it doesn't exist
    if (method_exists($adapter, 'createDatabase')) {
        $adapter->createDatabase('data2rest_demo');
        echo "  Database 'data2rest_demo' ready\n";
    }

    $pdo = $adapter->getConnection();

    // Create a test table
    $pdo->exec("CREATE TABLE IF NOT EXISTS demo_products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Insert test data
    $stmt = $pdo->prepare("INSERT INTO demo_products (name, price) VALUES (?, ?)");
    $stmt->execute(['Product A', 19.99]);
    $stmt->execute(['Product B', 29.99]);

    // Query data
    $products = $pdo->query("SELECT * FROM demo_products")->fetchAll();
    echo "  Created table with " . count($products) . " products\n";

    // Show adapter-specific features
    echo "  Database size: " . round($adapter->getDatabaseSize() / 1024 / 1024, 2) . " MB\n";
    echo "  Database type: " . $adapter->getType() . "\n";

} else {
    echo "✗ MySQL connection failed: " . $result['message'] . "\n";
    echo "  Configure MySQL credentials in .env file to test MySQL support\n";
}

echo "\n";

// Example 3: Using DatabaseManager to create a database record
echo "3. Creating Database Record with DatabaseManager\n";
echo str_repeat("-", 50) . "\n";

try {
    // Create a SQLite database record
    $database = DatabaseManager::createDatabase(
        'Demo Project Database',
        [
            'type' => 'sqlite',
            'path' => Config::get('db_storage_path') . 'demo_project.sqlite'
        ],
        null // No project ID for this demo
    );

    if ($database) {
        echo "✓ Database record created (ID: {$database['id']})\n";
        echo "  Name: {$database['name']}\n";
        echo "  Type: {$database['type']}\n";
        echo "  Path: {$database['path']}\n";

        // Get connection using the ID
        $pdo = DatabaseManager::getConnectionById($database['id']);
        if ($pdo) {
            echo "✓ Successfully retrieved connection by ID\n";
        }

        // Clean up - delete the demo record
        $db = \App\Core\Database::getInstance()->getConnection();
        $db->prepare("DELETE FROM databases WHERE id = ?")->execute([$database['id']]);
        echo "✓ Demo record cleaned up\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Example 4: Demonstrate adapter-specific SQL queries
echo "4. Adapter-Specific SQL Queries\n";
echo str_repeat("-", 50) . "\n";

$sqliteAdapter = DatabaseFactory::create($sqliteConfig);
echo "SQLite - List Tables SQL:\n";
echo "  " . $sqliteAdapter->getListTablesSQL() . "\n\n";

if ($result['success']) {
    $mysqlAdapter = DatabaseFactory::create($mysqlConfig);
    echo "MySQL - List Tables SQL:\n";
    echo "  " . $mysqlAdapter->getListTablesSQL() . "\n";
}

echo "\n";

// Example 5: Working with transactions
echo "5. Transaction Support\n";
echo str_repeat("-", 50) . "\n";

$adapter = DatabaseFactory::create($sqliteConfig);
$pdo = $adapter->getConnection();

try {
    $adapter->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO demo_users (name, email) VALUES (?, ?)");
    $stmt->execute(['Transaction User 1', 'trans1@example.com']);
    $stmt->execute(['Transaction User 2', 'trans2@example.com']);

    $adapter->commit();
    echo "✓ Transaction committed successfully\n";

} catch (Exception $e) {
    $adapter->rollback();
    echo "✗ Transaction rolled back: " . $e->getMessage() . "\n";
}

echo "\n";

echo "=== Demo Complete ===\n";
echo "\nNext steps:\n";
echo "- Check docs/MULTI_DATABASE.md for full documentation\n";
echo "- Configure MySQL in .env to test MySQL support\n";
echo "- Use DatabaseManager in your controllers for database operations\n";
