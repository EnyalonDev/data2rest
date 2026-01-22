<?php
// reproduce_issue.php

// 1. Mock Session
session_start();
$_SESSION['user_id'] = 999; // Dummy Admin
$_SESSION['username'] = 'admin_repro';
$_SESSION['permissions'] = ['all' => true]; // Super Admin

// 2. Load Framework
require_once __DIR__ . '/src/autoload.php';

use App\Core\Database;
use App\Modules\Database\CrudController;

// 3. Setup Test Data
$dbPath = '/opt/homebrew/var/www/data2rest/data/mundo_jacome_697234fb68376.sqlite';
$sqlite = new SQLite3($dbPath);

// Insert a test contact
$sqlite->exec("INSERT INTO contacts (name, email, phone, message, created_at) VALUES ('TO_DELETE', 'test@delete.com', '1234567890', 'Test Message', datetime('now'))");
$id = $sqlite->lastInsertRowID();

echo "Created test record with ID: $id\n";
echo "Verifying existence before delete...\n";
$count = $sqlite->querySingle("SELECT COUNT(*) FROM contacts WHERE id = $id");
echo "Record count: $count\n";

if ($count == 0) {
    die("Failed to insert test record.\n");
}

// 4. Mock Request Data for CrudController
$_GET['db_id'] = 2; // From previous checking
$_GET['table'] = 'contacts';
$_GET['id'] = "  " . $id . "  ";

$_POST['db_id'] = 2; // CrudController checks POST or GET
$_POST['table'] = 'contacts';
$_POST['id'] = "  " . $id . "  ";

// 5. Execute Delete
echo "Attempting delete via CrudController...\n";

// Capture output to avoid header issues messing up CLI (though headers usually just warn in CLI)
ob_start();
$controller = new CrudController();
$controller->delete();
$output = ob_get_clean();

// 6. Verify Result
echo "Delete operation completed.\n";
echo "Verifying existence after delete...\n";
$countAfter = $sqlite->querySingle("SELECT COUNT(*) FROM contacts WHERE id = $id");

if ($countAfter == 0) {
    echo "SUCCESS: Record was deleted.\n";
} else {
    echo "FAILURE: Record still exists!\n";
}

// 7. Output Logs (Simulated by checking PHP error log if redirected, but here we just rely on stdout/stderr if configured)
// Since we used error_log(), check stderr or the configured log file.
