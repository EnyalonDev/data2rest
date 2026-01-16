<?php

namespace App\Modules\Database;

use App\Core\Auth;

class DiagnosticController
{
    /**
     * PostgreSQL Diagnostic Page
     */
    public function pgDiagnostic()
    {
        // Allow access without authentication for debugging
        header('Content-Type: text/html; charset=utf-8');

        ?>
        <!DOCTYPE html>
        <html>

        <head>
            <title>PostgreSQL Diagnostic</title>
            <style>
                body {
                    font-family: monospace;
                    padding: 20px;
                    background: #1a1a1a;
                    color: #00ff00;
                }

                .success {
                    color: #00ff00;
                }

                .error {
                    color: #ff0000;
                }

                .warning {
                    color: #ffaa00;
                }

                .info {
                    color: #00aaff;
                }

                pre {
                    background: #000;
                    padding: 15px;
                    border-radius: 5px;
                    overflow-x: auto;
                }

                h2 {
                    border-bottom: 2px solid #00ff00;
                    padding-bottom: 10px;
                }
            </style>
        </head>

        <body>
            <h1>🐘 PostgreSQL Diagnostic Report</h1>

            <?php
            echo "<h2>PHP Configuration</h2>";
            echo "<pre>";
            echo "PHP Version: " . PHP_VERSION . "\n";
            echo "PHP SAPI: " . php_sapi_name() . "\n";
            echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
            echo "</pre>";

            echo "<h2>PDO Drivers</h2>";
            echo "<pre>";
            if (extension_loaded('PDO')) {
                echo "<span class='success'>✓ PDO extension is loaded</span>\n\n";
                $drivers = \PDO::getAvailableDrivers();
                echo "Available drivers:\n";
                foreach ($drivers as $driver) {
                    $color = ($driver === 'pgsql') ? 'success' : 'info';
                    echo "  <span class='$color'>• $driver</span>\n";
                }

                if (in_array('pgsql', $drivers)) {
                    echo "\n<span class='success'>✓ pdo_pgsql driver is available!</span>\n";
                } else {
                    echo "\n<span class='error'>✗ pdo_pgsql driver is NOT available</span>\n";
                    echo "<span class='warning'>This is the problem! PHP cannot connect to PostgreSQL.</span>\n";
                }
            } else {
                echo "<span class='error'>✗ PDO extension is NOT loaded</span>\n";
            }
            echo "</pre>";

            echo "<h2>Connection Test</h2>";
            echo "<pre>";
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
                $pdo = new \PDO($dsn, $config['username'], $config['password'], [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
                ]);

                echo "<span class='success'>✓ CONNECTION SUCCESSFUL!</span>\n\n";
                echo "Server Info:\n";
                echo "  Server version: " . $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION) . "\n";
                echo "  Client version: " . $pdo->getAttribute(\PDO::ATTR_CLIENT_VERSION) . "\n\n";

                $stmt = $pdo->query("SELECT version()");
                $version = $stmt->fetchColumn();
                echo "  PostgreSQL: $version\n\n";

                echo "<span class='success'>✅ Everything is working correctly!</span>\n";
                echo "<span class='info'>The problem is NOT with PHP or PostgreSQL.</span>\n";
                echo "<span class='warning'>Check your DATA2REST form configuration.</span>\n";

            } catch (\PDOException $e) {
                echo "<span class='error'>✗ CONNECTION FAILED</span>\n\n";
                echo "Error Details:\n";
                echo "  Message: " . $e->getMessage() . "\n";
                echo "  Code: " . $e->getCode() . "\n\n";

                if (strpos($e->getMessage(), 'could not find driver') !== false) {
                    echo "<span class='error'>Problem: pdo_pgsql extension is not installed</span>\n";
                    echo "<span class='warning'>Solution: Install PHP PostgreSQL extension for your web server</span>\n";
                } elseif (strpos($e->getMessage(), 'authentication failed') !== false) {
                    echo "<span class='error'>Problem: Wrong username or password</span>\n";
                    echo "<span class='warning'>Solution: Check credentials (postgres / Mede2020)</span>\n";
                } elseif (strpos($e->getMessage(), 'does not exist') !== false) {
                    echo "<span class='error'>Problem: Database 'mi_tienda' does not exist</span>\n";
                    echo "<span class='warning'>Solution: Create the database first</span>\n";
                } else {
                    echo "<span class='error'>Problem: Unknown connection error</span>\n";
                }
            }
            echo "</pre>";

            echo "<h2>Loaded Extensions</h2>";
            echo "<pre>";
            $extensions = get_loaded_extensions();
            sort($extensions);
            $found = false;
            foreach ($extensions as $ext) {
                if (stripos($ext, 'pdo') !== false || stripos($ext, 'pgsql') !== false) {
                    echo "<span class='success'>  ✓ $ext</span>\n";
                    $found = true;
                }
            }
            if (!$found) {
                echo "<span class='error'>No PDO or PostgreSQL extensions found</span>\n";
            }
            echo "</pre>";

            echo "<h2>PHP Configuration Files</h2>";
            echo "<pre>";
            echo "php.ini: " . php_ini_loaded_file() . "\n";
            $scanned = php_ini_scanned_files();
            if ($scanned) {
                echo "\nAdditional .ini files:\n";
                foreach (explode(',', $scanned) as $file) {
                    echo "  • " . trim($file) . "\n";
                }
            }
            echo "</pre>";

            echo "<h2>Next Steps</h2>";
            echo "<pre>";
            echo "1. If connection is successful:\n";
            echo "   → Go to DATA2REST create database form\n";
            echo "   → Fill in the exact same credentials\n";
            echo "   → Test connection should work\n\n";

            echo "2. If 'pdo_pgsql driver is NOT available':\n";
            echo "   → You need to install PHP PostgreSQL extension\n";
            echo "   → For ServBay: Check ServBay PHP extensions\n";
            echo "   → For Homebrew PHP: brew install php-pgsql\n\n";

            echo "3. If 'database does not exist':\n";
            echo "   → Run: ./scripts/create_pg_database.sh mi_tienda\n";
            echo "</pre>";
            ?>
        </body>

        </html>
        <?php
        exit;
    }
}
