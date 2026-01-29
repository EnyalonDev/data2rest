<?php
require_once __DIR__ . '/../src/autoload.php';

use App\Core\Database;

// Verify Database Connection
try {
    $db = Database::getInstance()->getConnection();

    echo "--- Users in Project 2 ---\n";
    $stmt = $db->prepare("
        SELECT u.id, u.username, u.email, pu.external_permissions 
        FROM project_users pu 
        JOIN users u ON pu.user_id = u.id 
        WHERE pu.project_id = 2
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($users as $user) {
        $perms = json_decode($user['external_permissions'], true);
        $role = $perms['role'] ?? 'unknown';
        echo "ID: {$user['id']} | User: {$user['username']} | Email: {$user['email']} | Role: $role\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
