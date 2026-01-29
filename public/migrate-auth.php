<?php
// public/migrate-auth.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../src/autoload.php';

use App\Core\Database;

echo "<h1>Auth Migration</h1>";

try {
    $db = Database::getInstance()->getConnection();
    echo "Connected to DB.<br>";

    // Check if columns exist
    $columns = $db->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
    $hasToken = false;
    $hasVerified = false;

    foreach ($columns as $col) {
        if ($col['name'] === 'verification_token')
            $hasToken = true;
        if ($col['name'] === 'email_verified_at')
            $hasVerified = true;
    }

    if (!$hasToken) {
        $db->exec("ALTER TABLE users ADD COLUMN verification_token TEXT DEFAULT NULL");
        echo "Added column: verification_token<br>";
    } else {
        echo "Column verification_token already exists.<br>";
    }

    if (!$hasVerified) {
        $db->exec("ALTER TABLE users ADD COLUMN email_verified_at DATETIME DEFAULT NULL");
        echo "Added column: email_verified_at<br>";
    } else {
        echo "Column email_verified_at already exists.<br>";
    }

    echo "<h3>Migration Completed!</h3>";

} catch (Exception $e) {
    echo "<h3 style='color:red'>Error: " . $e->getMessage() . "</h3>";
}
