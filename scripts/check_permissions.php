<?php
/**
 * Permission Diagnostic Script
 * Shows current user permissions for debugging
 */

require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Core/Config.php';

use App\Core\Auth;
use App\Core\Database;

Auth::init();

if (!Auth::check()) {
    die("No user logged in. Please login first.\n");
}

echo "=== PERMISSION DIAGNOSTIC ===\n\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'N/A') . "\n";
echo "Username: " . ($_SESSION['username'] ?? 'N/A') . "\n";
echo "Role ID: " . ($_SESSION['role_id'] ?? 'N/A') . "\n";
echo "Group ID: " . ($_SESSION['group_id'] ?? 'N/A') . "\n";
echo "Is Admin: " . (Auth::isAdmin() ? 'YES' : 'NO') . "\n\n";

echo "=== PERMISSIONS ===\n";
if (isset($_SESSION['permissions'])) {
    echo json_encode($_SESSION['permissions'], JSON_PRETTY_PRINT) . "\n\n";
} else {
    echo "No permissions found in session.\n\n";
}

echo "=== PERMISSION CHECKS ===\n";
$checksToTest = [
    'module:databases',
    'module:databases.create_db',
    'module:databases.edit_db',
    'module:databases.delete_db',
    'module:databases.create_table',
    'module:databases.edit_table',
    'module:databases.drop_table',
];

foreach ($checksToTest as $check) {
    $result = Auth::hasPermission($check) ? '✓ YES' : '✗ NO';
    echo "$check: $result\n";
}

echo "\n=== ROLE PERMISSIONS FROM DB ===\n";
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT r.name, r.permissions FROM roles r JOIN users u ON u.role_id = r.id WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$role = $stmt->fetch();
if ($role) {
    echo "Role Name: " . $role['name'] . "\n";
    echo "Role Permissions:\n";
    echo json_encode(json_decode($role['permissions'], true), JSON_PRETTY_PRINT) . "\n";
}

echo "\n=== GROUP PERMISSIONS FROM DB ===\n";
if ($_SESSION['group_id']) {
    $stmt = $db->prepare("SELECT g.name, g.permissions FROM groups g WHERE g.id = ?");
    $stmt->execute([$_SESSION['group_id']]);
    $group = $stmt->fetch();
    if ($group) {
        echo "Group Name: " . $group['name'] . "\n";
        echo "Group Permissions:\n";
        echo json_encode(json_decode($group['permissions'], true), JSON_PRETTY_PRINT) . "\n";
    }
} else {
    echo "No group assigned.\n";
}
