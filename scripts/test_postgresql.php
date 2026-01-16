<?php
/**
 * PostgreSQL Integration Test Script
 * 
 * This script tests all PostgreSQL functionality:
 * - Connection
 * - Table creation
 * - CRUD operations
 * - Triggers
 * - Transactions
 * - API compatibility
 * 
 * Usage: php scripts/test_postgresql.php
 */

require_once __DIR__ . '/../src/autoload.php';

use App\Core\Adapters\PostgreSQLAdapter;
use App\Core\DatabaseFactory;

// ANSI color codes for terminal output
class Colors
{
    const GREEN = "\033[32m";
    const RED = "\033[31m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const RESET = "\033[0m";
    const BOLD = "\033[1m";
}

function printHeader($text)
{
    echo "\n" . Colors::BOLD . Colors::BLUE . "=== $text ===" . Colors::RESET . "\n";
}

function printSuccess($text)
{
    echo Colors::GREEN . "✓ $text" . Colors::RESET . "\n";
}

function printError($text)
{
    echo Colors::RED . "✗ $text" . Colors::RESET . "\n";
}

function printInfo($text)
{
    echo Colors::YELLOW . "ℹ $text" . Colors::RESET . "\n";
}

// Test configuration - MODIFY THESE VALUES
$testConfig = [
    'type' => 'pgsql',
    'host' => 'localhost',
    'port' => 5432,
    'database' => 'test_data2rest',  // We'll create this database
    'username' => 'postgres',         // Default Postgres.app user
    'password' => 'Mede2020',        // Your password
    'schema' => 'public',
    'charset' => 'utf8'
];

$testsPassed = 0;
$testsFailed = 0;
$testsTotal = 0;

printHeader("PostgreSQL Integration Tests");
echo "Database: {$testConfig['database']}\n";
echo "Host: {$testConfig['host']}:{$testConfig['port']}\n";
echo "Schema: {$testConfig['schema']}\n\n";

// Test 1: Adapter Creation
printHeader("Test 1: Adapter Creation");
$testsTotal++;
try {
    $adapter = new PostgreSQLAdapter($testConfig);
    printSuccess("PostgreSQLAdapter created successfully");
    $testsPassed++;
} catch (Exception $e) {
    printError("Failed to create adapter: " . $e->getMessage());
    $testsFailed++;
    exit(1);
}

// Test 2: Database Connection
printHeader("Test 2: Database Connection");
$testsTotal++;
try {
    $connection = $adapter->getConnection();
    printSuccess("Connected to PostgreSQL server");
    printInfo("Server version: " . $connection->getAttribute(PDO::ATTR_SERVER_VERSION));
    $testsPassed++;
} catch (Exception $e) {
    printError("Connection failed: " . $e->getMessage());
    printInfo("Make sure PostgreSQL is running and credentials are correct");
    $testsFailed++;
    exit(1);
}

// Test 3: Test Connection Method
printHeader("Test 3: Test Connection Method");
$testsTotal++;
try {
    $result = $adapter->testConnection();
    if ($result) {
        printSuccess("testConnection() returned true");
        $testsPassed++;
    } else {
        printError("testConnection() returned false");
        $testsFailed++;
    }
} catch (Exception $e) {
    printError("testConnection() threw exception: " . $e->getMessage());
    $testsFailed++;
}

// Test 4: Get Database Type
printHeader("Test 4: Get Database Type");
$testsTotal++;
$type = $adapter->getType();
if ($type === 'pgsql') {
    printSuccess("getType() returned 'pgsql'");
    $testsPassed++;
} else {
    printError("getType() returned '$type' instead of 'pgsql'");
    $testsFailed++;
}

// Test 5: Create Test Table
printHeader("Test 5: Create Test Table");
$testsTotal++;
$testTable = 'test_table_' . time();
try {
    // Drop table if exists
    try {
        $adapter->deleteTable($testTable);
    } catch (Exception $e) {
        // Table doesn't exist, that's fine
    }

    $adapter->createTable($testTable);
    printSuccess("Table '$testTable' created successfully");
    printInfo("Table includes: id (SERIAL), fecha_de_creacion, fecha_edicion");
    $testsPassed++;
} catch (Exception $e) {
    printError("Failed to create table: " . $e->getMessage());
    $testsFailed++;
}

// Test 6: Get Tables List
printHeader("Test 6: Get Tables List");
$testsTotal++;
try {
    $tables = $adapter->getTables();
    if (in_array($testTable, $tables)) {
        printSuccess("getTables() includes our test table");
        printInfo("Total tables in schema: " . count($tables));
        $testsPassed++;
    } else {
        printError("getTables() doesn't include our test table");
        $testsFailed++;
    }
} catch (Exception $e) {
    printError("Failed to get tables: " . $e->getMessage());
    $testsFailed++;
}

// Test 7: Get Columns
printHeader("Test 7: Get Columns");
$testsTotal++;
try {
    $columns = $adapter->getColumns($testTable);
    $expectedColumns = ['id', 'fecha_de_creacion', 'fecha_edicion'];
    $foundColumns = array_column($columns, 'name');

    $allFound = true;
    foreach ($expectedColumns as $expected) {
        if (!in_array($expected, $foundColumns)) {
            $allFound = false;
            printError("Missing column: $expected");
        }
    }

    if ($allFound) {
        printSuccess("All expected columns found");
        printInfo("Columns: " . implode(', ', $foundColumns));
        $testsPassed++;
    } else {
        $testsFailed++;
    }
} catch (Exception $e) {
    printError("Failed to get columns: " . $e->getMessage());
    $testsFailed++;
}

// Test 8: Add Column
printHeader("Test 8: Add Column");
$testsTotal++;
try {
    $adapter->addColumn($testTable, 'test_field', 'VARCHAR(255)');
    $columns = $adapter->getColumns($testTable);
    $foundColumns = array_column($columns, 'name');

    if (in_array('test_field', $foundColumns)) {
        printSuccess("Column 'test_field' added successfully");
        $testsPassed++;
    } else {
        printError("Column 'test_field' not found after adding");
        $testsFailed++;
    }
} catch (Exception $e) {
    printError("Failed to add column: " . $e->getMessage());
    $testsFailed++;
}

// Test 9: Insert Data (CRUD - Create)
printHeader("Test 9: Insert Data (CRUD - Create)");
$testsTotal++;
try {
    $stmt = $connection->prepare("INSERT INTO \"$testTable\" (test_field) VALUES (?) RETURNING id");
    $stmt->execute(['Test Value 1']);
    $insertedId = $stmt->fetchColumn();

    if ($insertedId) {
        printSuccess("Record inserted with ID: $insertedId");
        $testsPassed++;
    } else {
        printError("Failed to get inserted ID");
        $testsFailed++;
    }
} catch (Exception $e) {
    printError("Failed to insert data: " . $e->getMessage());
    $testsFailed++;
}

// Test 10: Read Data (CRUD - Read)
printHeader("Test 10: Read Data (CRUD - Read)");
$testsTotal++;
try {
    $stmt = $connection->query("SELECT * FROM \"$testTable\"");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($records) > 0) {
        printSuccess("Retrieved " . count($records) . " record(s)");
        printInfo("First record: " . json_encode($records[0]));
        $testsPassed++;
    } else {
        printError("No records found");
        $testsFailed++;
    }
} catch (Exception $e) {
    printError("Failed to read data: " . $e->getMessage());
    $testsFailed++;
}

// Test 11: Update Data (CRUD - Update)
printHeader("Test 11: Update Data (CRUD - Update)");
$testsTotal++;
try {
    // Get timestamp before update
    $stmt = $connection->query("SELECT fecha_edicion FROM \"$testTable\" WHERE id = $insertedId");
    $oldTimestamp = $stmt->fetchColumn();

    sleep(1); // Wait to ensure timestamp changes

    $stmt = $connection->prepare("UPDATE \"$testTable\" SET test_field = ? WHERE id = ?");
    $stmt->execute(['Updated Value', $insertedId]);

    // Check if timestamp was auto-updated
    $stmt = $connection->query("SELECT fecha_edicion FROM \"$testTable\" WHERE id = $insertedId");
    $newTimestamp = $stmt->fetchColumn();

    if ($newTimestamp > $oldTimestamp) {
        printSuccess("Record updated and fecha_edicion auto-updated");
        printInfo("Old: $oldTimestamp | New: $newTimestamp");
        $testsPassed++;
    } else {
        printError("fecha_edicion was not auto-updated");
        $testsFailed++;
    }
} catch (Exception $e) {
    printError("Failed to update data: " . $e->getMessage());
    $testsFailed++;
}

// Test 12: Transactions
printHeader("Test 12: Transactions (Rollback)");
$testsTotal++;
try {
    $adapter->beginTransaction();

    $stmt = $connection->prepare("INSERT INTO \"$testTable\" (test_field) VALUES (?)");
    $stmt->execute(['Transaction Test']);

    $adapter->rollback();

    // Check if record was rolled back
    $stmt = $connection->query("SELECT COUNT(*) FROM \"$testTable\" WHERE test_field = 'Transaction Test'");
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        printSuccess("Transaction rolled back successfully");
        $testsPassed++;
    } else {
        printError("Transaction rollback failed");
        $testsFailed++;
    }
} catch (Exception $e) {
    printError("Transaction test failed: " . $e->getMessage());
    $testsFailed++;
}

// Test 13: Transactions (Commit)
printHeader("Test 13: Transactions (Commit)");
$testsTotal++;
try {
    $adapter->beginTransaction();

    $stmt = $connection->prepare("INSERT INTO \"$testTable\" (test_field) VALUES (?)");
    $stmt->execute(['Committed Transaction']);

    $adapter->commit();

    // Check if record was committed
    $stmt = $connection->query("SELECT COUNT(*) FROM \"$testTable\" WHERE test_field = 'Committed Transaction'");
    $count = $stmt->fetchColumn();

    if ($count == 1) {
        printSuccess("Transaction committed successfully");
        $testsPassed++;
    } else {
        printError("Transaction commit failed");
        $testsFailed++;
    }
} catch (Exception $e) {
    printError("Transaction commit test failed: " . $e->getMessage());
    $testsFailed++;
}

// Test 14: Delete Column
printHeader("Test 14: Delete Column");
$testsTotal++;
try {
    $adapter->deleteColumn($testTable, 'test_field');
    $columns = $adapter->getColumns($testTable);
    $foundColumns = array_column($columns, 'name');

    if (!in_array('test_field', $foundColumns)) {
        printSuccess("Column 'test_field' deleted successfully");
        $testsPassed++;
    } else {
        printError("Column 'test_field' still exists after deletion");
        $testsFailed++;
    }
} catch (Exception $e) {
    printError("Failed to delete column: " . $e->getMessage());
    $testsFailed++;
}

// Test 15: Database Size
printHeader("Test 15: Get Database Size");
$testsTotal++;
try {
    $size = $adapter->getDatabaseSize();
    $sizeMB = round($size / 1024 / 1024, 2);
    printSuccess("Database size: $sizeMB MB");
    $testsPassed++;
} catch (Exception $e) {
    printError("Failed to get database size: " . $e->getMessage());
    $testsFailed++;
}

// Test 16: Optimize Database
printHeader("Test 16: Optimize Database (VACUUM)");
$testsTotal++;
try {
    $result = $adapter->optimize();
    if ($result) {
        printSuccess("Database optimized (VACUUM ANALYZE executed)");
        $testsPassed++;
    } else {
        printError("Optimization returned false");
        $testsFailed++;
    }
} catch (Exception $e) {
    printError("Failed to optimize database: " . $e->getMessage());
    $testsFailed++;
}

// Test 17: Delete Table
printHeader("Test 17: Delete Table");
$testsTotal++;
try {
    $adapter->deleteTable($testTable);
    $tables = $adapter->getTables();

    if (!in_array($testTable, $tables)) {
        printSuccess("Table '$testTable' deleted successfully");
        $testsPassed++;
    } else {
        printError("Table '$testTable' still exists after deletion");
        $testsFailed++;
    }
} catch (Exception $e) {
    printError("Failed to delete table: " . $e->getMessage());
    $testsFailed++;
}

// Test 18: DatabaseFactory Integration
printHeader("Test 18: DatabaseFactory Integration");
$testsTotal++;
try {
    $factoryAdapter = DatabaseFactory::create($testConfig);
    if ($factoryAdapter instanceof PostgreSQLAdapter) {
        printSuccess("DatabaseFactory created PostgreSQLAdapter correctly");
        $testsPassed++;
    } else {
        printError("DatabaseFactory created wrong adapter type");
        $testsFailed++;
    }
} catch (Exception $e) {
    printError("DatabaseFactory test failed: " . $e->getMessage());
    $testsFailed++;
}

// Final Summary
printHeader("Test Summary");
echo "\n";
echo Colors::BOLD . "Total Tests: $testsTotal" . Colors::RESET . "\n";
echo Colors::GREEN . "Passed: $testsPassed" . Colors::RESET . "\n";
echo Colors::RED . "Failed: $testsFailed" . Colors::RESET . "\n";
echo "\n";

$percentage = round(($testsPassed / $testsTotal) * 100, 1);
echo Colors::BOLD;
if ($percentage == 100) {
    echo Colors::GREEN . "✓ ALL TESTS PASSED! ($percentage%)" . Colors::RESET . "\n";
} elseif ($percentage >= 80) {
    echo Colors::YELLOW . "⚠ MOST TESTS PASSED ($percentage%)" . Colors::RESET . "\n";
} else {
    echo Colors::RED . "✗ MANY TESTS FAILED ($percentage%)" . Colors::RESET . "\n";
}
echo "\n";

exit($testsFailed > 0 ? 1 : 0);
