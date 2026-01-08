<?php
/**
 * Data2Rest - Diagnostic Tool
 * Upload this file to your server root and access it via browser
 * to diagnose installation issues.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Data2Rest Diagnostic</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:900px;margin:50px auto;padding:20px;}";
echo ".success{color:green;font-weight:bold;}.error{color:red;font-weight:bold;}";
echo ".warning{color:orange;font-weight:bold;}h2{border-bottom:2px solid #333;padding-bottom:10px;}";
echo "table{width:100%;border-collapse:collapse;margin:20px 0;}";
echo "td,th{padding:10px;border:1px solid #ddd;text-align:left;}";
echo "th{background:#f4f4f4;}</style></head><body>";

echo "<h1>🔍 Data2Rest - Diagnostic Report</h1>";
echo "<p>Generated: " . date('Y-m-d H:i:s') . "</p>";

// 1. PHP Version Check
echo "<h2>1. PHP Environment</h2>";
echo "<table>";
echo "<tr><th>Check</th><th>Value</th><th>Status</th></tr>";

$phpVersion = phpversion();
$phpOk = version_compare($phpVersion, '8.0.0', '>=');
echo "<tr><td>PHP Version</td><td>$phpVersion</td><td class='" . ($phpOk ? 'success' : 'error') . "'>" . ($phpOk ? '✓ OK' : '✗ FAIL (Need 8.0+)') . "</td></tr>";

// 2. Required Extensions
echo "<tr><th colspan='3'>Required PHP Extensions</th></tr>";
$extensions = ['pdo', 'pdo_sqlite', 'sqlite3', 'session', 'json', 'mbstring'];
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo "<tr><td>$ext</td><td>" . ($loaded ? 'Loaded' : 'Not Loaded') . "</td><td class='" . ($loaded ? 'success' : 'error') . "'>" . ($loaded ? '✓' : '✗') . "</td></tr>";
}

echo "</table>";

// 3. Directory Permissions
echo "<h2>2. Directory Permissions</h2>";
echo "<table>";
echo "<tr><th>Directory</th><th>Exists</th><th>Writable</th><th>Status</th></tr>";

$dirs = [
    'data' => __DIR__ . '/data',
    'public/uploads' => __DIR__ . '/public/uploads',
];

foreach ($dirs as $name => $path) {
    $exists = is_dir($path);
    $writable = $exists && is_writable($path);

    echo "<tr><td>$name</td>";
    echo "<td>" . ($exists ? 'Yes' : 'No') . "</td>";
    echo "<td>" . ($writable ? 'Yes' : 'No') . "</td>";
    echo "<td class='" . ($writable ? 'success' : 'error') . "'>" . ($writable ? '✓ OK' : '✗ FAIL') . "</td>";
    echo "</tr>";

    if (!$exists) {
        echo "<tr><td colspan='4' class='warning'>⚠ Directory does not exist: $path</td></tr>";
    } elseif (!$writable) {
        echo "<tr><td colspan='4' class='warning'>⚠ Directory is not writable. Run: chmod 755 $path</td></tr>";
    }
}

echo "</table>";

// 4. File Permissions
echo "<h2>3. Critical Files</h2>";
echo "<table>";
echo "<tr><th>File</th><th>Exists</th><th>Readable</th><th>Status</th></tr>";

$files = [
    'public/index.php' => __DIR__ . '/public/index.php',
    'src/Core/Config.php' => __DIR__ . '/src/Core/Config.php',
    'src/Core/Auth.php' => __DIR__ . '/src/Core/Auth.php',
    '.htaccess' => __DIR__ . '/.htaccess',
];

foreach ($files as $name => $path) {
    $exists = file_exists($path);
    $readable = $exists && is_readable($path);

    echo "<tr><td>$name</td>";
    echo "<td>" . ($exists ? 'Yes' : 'No') . "</td>";
    echo "<td>" . ($readable ? 'Yes' : 'No') . "</td>";
    echo "<td class='" . ($readable ? 'success' : 'error') . "'>" . ($readable ? '✓ OK' : '✗ FAIL') . "</td>";
    echo "</tr>";
}

echo "</table>";

// 5. Apache Modules
echo "<h2>4. Apache Configuration</h2>";
echo "<table>";
echo "<tr><th>Module</th><th>Status</th></tr>";

if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    $requiredModules = ['mod_rewrite'];

    foreach ($requiredModules as $mod) {
        $loaded = in_array($mod, $modules);
        echo "<tr><td>$mod</td><td class='" . ($loaded ? 'success' : 'error') . "'>" . ($loaded ? '✓ Loaded' : '✗ Not Loaded') . "</td></tr>";
    }
} else {
    echo "<tr><td colspan='2' class='warning'>⚠ Cannot detect Apache modules (not running under Apache or function disabled)</td></tr>";
}

echo "</table>";

// 6. Database Test
echo "<h2>5. Database Connection Test</h2>";
echo "<table>";
echo "<tr><th>Test</th><th>Result</th></tr>";

$dbPath = __DIR__ . '/data/system.sqlite';
$dbExists = file_exists($dbPath);

echo "<tr><td>Database File Exists</td><td class='" . ($dbExists ? 'success' : 'warning') . "'>" . ($dbExists ? '✓ Yes' : '⚠ No (will be created on first run)') . "</td></tr>";

if ($dbExists) {
    try {
        $pdo = new PDO('sqlite:' . $dbPath);
        echo "<tr><td>Database Connection</td><td class='success'>✓ Connected Successfully</td></tr>";

        // Check if usuarios table exists
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='usuarios'");
        $tableExists = $stmt->fetch();
        echo "<tr><td>Users Table Exists</td><td class='" . ($tableExists ? 'success' : 'warning') . "'>" . ($tableExists ? '✓ Yes' : '⚠ No (needs installation)') . "</td></tr>";

    } catch (PDOException $e) {
        echo "<tr><td>Database Connection</td><td class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
    }
}

echo "</table>";

// 7. Server Information
echo "<h2>6. Server Information</h2>";
echo "<table>";
echo "<tr><th>Setting</th><th>Value</th></tr>";
echo "<tr><td>Server Software</td><td>" . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</td></tr>";
echo "<tr><td>Document Root</td><td>" . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</td></tr>";
echo "<tr><td>Script Filename</td><td>" . __FILE__ . "</td></tr>";
echo "<tr><td>Upload Max Filesize</td><td>" . ini_get('upload_max_filesize') . "</td></tr>";
echo "<tr><td>Post Max Size</td><td>" . ini_get('post_max_size') . "</td></tr>";
echo "<tr><td>Max Execution Time</td><td>" . ini_get('max_execution_time') . "s</td></tr>";
echo "<tr><td>Memory Limit</td><td>" . ini_get('memory_limit') . "</td></tr>";
echo "</table>";

// 8. Error Log Check
echo "<h2>7. Error Logging</h2>";
echo "<table>";
echo "<tr><th>Setting</th><th>Value</th></tr>";
echo "<tr><td>Display Errors</td><td>" . (ini_get('display_errors') ? 'On' : 'Off') . "</td></tr>";
echo "<tr><td>Error Reporting</td><td>" . error_reporting() . "</td></tr>";
echo "<tr><td>Error Log Location</td><td>" . (ini_get('error_log') ?: 'Default') . "</td></tr>";
echo "</table>";

echo "<h2>8. Recommendations</h2>";
echo "<ul>";

if (!$phpOk) {
    echo "<li class='error'>⚠ <strong>CRITICAL:</strong> Upgrade PHP to version 8.0 or higher</li>";
}

foreach ($extensions as $ext) {
    if (!extension_loaded($ext)) {
        echo "<li class='error'>⚠ <strong>CRITICAL:</strong> Install PHP extension: $ext</li>";
    }
}

foreach ($dirs as $name => $path) {
    if (!is_dir($path)) {
        echo "<li class='warning'>⚠ Create directory: <code>mkdir -p $path</code></li>";
    } elseif (!is_writable($path)) {
        echo "<li class='warning'>⚠ Make directory writable: <code>chmod 755 $path</code></li>";
    }
}

if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    if (!in_array('mod_rewrite', $modules)) {
        echo "<li class='error'>⚠ <strong>CRITICAL:</strong> Enable Apache mod_rewrite module</li>";
    }
}

echo "</ul>";

echo "<hr><p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Fix any <span class='error'>CRITICAL</span> issues shown above</li>";
echo "<li>Ensure all directories are writable</li>";
echo "<li>Delete this diagnostic.php file for security</li>";
echo "<li>Access your application at: <a href='./'>Main Application</a></li>";
echo "</ol>";

echo "</body></html>";
