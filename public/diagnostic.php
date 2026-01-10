<?php
// Data2Rest Diagnostic Tool
// Upload this file to your public folder (e.g., public_html or public)
// Access it via browser: yourdomain.com/diagnostic.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><title>System Diagnostics</title>";
echo "<style>body{font-family:sans-serif;line-height:1.5;padding:2rem;background:#f0f9ff;color:#0f172a;}";
echo "h2{border-bottom:2px solid #38bdf8;padding-bottom:0.5rem;margin-top:2rem;}";
echo ".ok{color:green;font-weight:bold;}.fail{color:red;font-weight:bold;}.warn{color:orange;font-weight:bold;}";
echo "table{width:100%;border-collapse:collapse;margin-top:1rem;background:white;}";
echo "th,td{border:1px solid #e2e8f0;padding:0.75rem;text-align:left;}th{background:#f8fafc;}";
echo "code{background:#f1f5f9;padding:0.2rem 0.4rem;border-radius:0.25rem;font-family:monospace;}</style>";
echo "</head><body>";
echo "<h1>🛠️ Data2Rest Diagnostic Tool</h1>";

// 1. Environment
echo "<h2>1. Server Environment</h2>";
echo "<strong>PHP Version:</strong> " . phpversion() . " <br>";
echo "<strong>Server Software:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "<strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "<strong>Current Script:</strong> " . __FILE__ . "<br>";

// 2. Extensions
echo "<h2>2. PHP Extensions</h2>";
$extensions = ['pdo', 'pdo_sqlite', 'sqlite3', 'mbstring', 'json'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<span class='ok'>[OK]</span> Extension <code>$ext</code> is loaded.<br>";
    } else {
        echo "<span class='fail'>[FAIL]</span> Extension <code>$ext</code> is NOT loaded.<br>";
    }
}

// 3. Paths & Permissions
echo "<h2>3. Filesystem & Permissions</h2>";
$publicDir = __DIR__;
$baseDir = realpath(__DIR__ . '/../');
$dataDir = $baseDir . '/data';
$systemDb = $dataDir . '/system.sqlite';

echo "<strong>Base Directory:</strong> " . ($baseDir ?: 'Unknown (Parent of public not found)') . "<br>";

if (!$baseDir) {
    echo "<span class='fail'>[CRITICAL] Cannot process base directory. Check structure.</span>";
    exit;
}

// Check Data Dir
echo "<strong>Data Directory:</strong> <code>$dataDir</code> ";
if (is_dir($dataDir)) {
    echo "<span class='ok'>[EXISTS]</span> ";
    if (is_writable($dataDir)) {
        echo "<span class='ok'>[WRITABLE]</span>";
    } else {
        echo "<span class='fail'>[NOT WRITABLE]</span> (Perms: " . substr(sprintf('%o', fileperms($dataDir)), -4) . ")";
    }
} else {
    echo "<span class='fail'>[DOES NOT EXIST]</span>";
}
echo "<br>";

// Check System DB
echo "<strong>System DB File:</strong> <code>$systemDb</code> ";
if (file_exists($systemDb)) {
    echo "<span class='ok'>[EXISTS]</span> ";
    if (is_writable($systemDb)) {
        echo "<span class='ok'>[WRITABLE]</span>";
    } else {
        echo "<span class='fail'>[NOT WRITABLE]</span> (Perms: " . substr(sprintf('%o', fileperms($systemDb)), -4) . ")";
    }
} else {
    echo "<span class='fail'>[DOES NOT EXIST]</span>";
}
echo "<br>";

// 4. Database Connectivity & Paths
echo "<h2>4. Database Connectivity & Paths</h2>";

try {
    if (!file_exists($systemDb)) {
        throw new Exception("System database file missing.");
    }

    $pdo = new PDO('sqlite:' . $systemDb);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<span class='ok'>[SUCCESS] Connected to system.sqlite via PDO.</span><br>";

    echo "<h3>Registered Databases</h3>";
    $stmt = $pdo->query("SELECT * FROM databases");
    $dbs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($dbs)) {
        echo "No databases registered in system.sqlite.<br>";
    } else {
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>Stored Path (Absolute)</th><th>Status</th><th>Recommended Fix</th></tr>";

        foreach ($dbs as $db) {
            $status = "Unknown";
            $fix = "-";

            // Check stored path
            if (file_exists($db['path'])) {
                $status = "<span class='ok'>Valid</span>";
            } else {
                $status = "<span class='fail'>Invalid / Missing</span>";

                // Analyze fix
                $filename = basename($db['path']);
                $localCandidate = $dataDir . '/' . $filename;

                if (file_exists($localCandidate)) {
                    $fix = "<span class='ok'>Found in data dir!</span><br>Code should auto-heal to:<br><code>$localCandidate</code>";
                } else {
                    $fix = "<span class='fail'>File lost</span><br>Expected name: <code>$filename</code><br>Not found in data dir.";
                }
            }

            echo "<tr>";
            echo "<td>{$db['id']}</td>";
            echo "<td>" . htmlspecialchars($db['name']) . "</td>";
            echo "<td><code>" . htmlspecialchars($db['path']) . "</code></td>";
            echo "<td>$status</td>";
            echo "<td>$fix</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

} catch (Exception $e) {
    echo "<span class='fail'>[FATAL] Database connection failed:</span> " . $e->getMessage() . "<br>";
}

echo "</body></html>";
?>