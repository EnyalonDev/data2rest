<?php
// Diagnostic script for PostgreSQL PDO support

header('Content-Type: text/plain; charset=utf-8');

echo "=== PHP PostgreSQL Diagnostic ===\n\n";

// PHP Version
echo "PHP Version: " . PHP_VERSION . "\n";
echo "PHP SAPI: " . php_sapi_name() . "\n\n";

// PDO Drivers
echo "=== PDO Drivers Available ===\n";
if (extension_loaded('PDO')) {
    echo "✓ PDO extension is loaded\n";
    $drivers = PDO::getAvailableDrivers();
    echo "Available drivers: " . implode(', ', $drivers) . "\n\n";

    if (in_array('pgsql', $drivers)) {
        echo "✓ pdo_pgsql driver is available\n\n";
    } else {
        echo "✗ pdo_pgsql driver is NOT available\n";
        echo "  This is the problem! PHP cannot connect to PostgreSQL.\n\n";
    }
} else {
    echo "✗ PDO extension is NOT loaded\n\n";
}

// Try to connect
echo "=== Connection Test ===\n";
$config = [
    'host' => 'localhost',
    'port' => 5432,
    'database' => 'mi_tienda',
    'username' => 'postgres',
    'password' => 'Mede2020'
];

echo "Attempting to connect to:\n";
echo "  Host: {$config['host']}\n";
echo "  Port: {$config['port']}\n";
echo "  Database: {$config['database']}\n";
echo "  Username: {$config['username']}\n\n";

try {
    $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "✓ CONNECTION SUCCESSFUL!\n";
    echo "  Server version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
    echo "  Client version: " . $pdo->getAttribute(PDO::ATTR_CLIENT_VERSION) . "\n\n";

    // Test query
    $stmt = $pdo->query("SELECT version()");
    $version = $stmt->fetchColumn();
    echo "  PostgreSQL: $version\n\n";

    echo "✅ Everything is working correctly!\n";
    echo "   The problem is NOT with PHP or PostgreSQL.\n";
    echo "   Check your DATA2REST configuration.\n";

} catch (PDOException $e) {
    echo "✗ CONNECTION FAILED\n";
    echo "  Error: " . $e->getMessage() . "\n";
    echo "  Code: " . $e->getCode() . "\n\n";

    if (strpos($e->getMessage(), 'could not find driver') !== false) {
        echo "  Problem: pdo_pgsql extension is not installed\n";
        echo "  Solution: Install PHP PostgreSQL extension\n";
    } elseif (strpos($e->getMessage(), 'authentication failed') !== false) {
        echo "  Problem: Wrong username or password\n";
        echo "  Solution: Check credentials\n";
    } elseif (strpos($e->getMessage(), 'does not exist') !== false) {
        echo "  Problem: Database 'mi_tienda' does not exist\n";
        echo "  Solution: Create the database first\n";
    } else {
        echo "  Problem: Unknown connection error\n";
    }
}

echo "\n=== Loaded Extensions ===\n";
$extensions = get_loaded_extensions();
sort($extensions);
foreach ($extensions as $ext) {
    if (stripos($ext, 'pdo') !== false || stripos($ext, 'pgsql') !== false) {
        echo "  ✓ $ext\n";
    }
}

echo "\n=== PHP Configuration ===\n";
echo "php.ini file: " . php_ini_loaded_file() . "\n";
echo "Additional .ini files: " . (php_ini_scanned_files() ?: 'none') . "\n";
