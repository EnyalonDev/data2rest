#!/usr/bin/env php
<?php
/**
 * Migration Script: Clients to Users
 * 
 * This script migrates data from the deprecated 'clients' table to the 'users' table.
 * Clients will be created as users with role_id = 3 (Cliente role).
 * 
 * Usage: php scripts/migrate_clients_to_users.php
 */

require_once __DIR__ . '/../src/autoload.php';

use App\Core\Database;
use App\Core\Config;

echo "=== Client to User Migration Script ===\n\n";

try {
    // Load config
    Config::loadEnv();

    $db = Database::getInstance()->getConnection();
    $adapter = Database::getInstance()->getAdapter();

    // Check if clients table exists
    $clientsTable = $adapter->quoteName('clients');
    $usersTable = $adapter->quoteName('users');

    echo "1. Checking for clients to migrate...\n";

    $stmt = $db->query("SELECT * FROM $clientsTable WHERE status = 'active'");
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($clients)) {
        echo "   ✓ No active clients found to migrate.\n\n";
        exit(0);
    }

    echo "   Found " . count($clients) . " client(s) to migrate.\n\n";

    $migratedCount = 0;
    $skippedCount = 0;
    $errorCount = 0;

    foreach ($clients as $client) {
        echo "2. Processing client: {$client['name']} ({$client['email']})\n";

        // Check if user already exists with this email
        $checkStmt = $db->prepare("SELECT id FROM $usersTable WHERE email = ?");
        $checkStmt->execute([$client['email']]);
        $existingUser = $checkStmt->fetch();

        if ($existingUser) {
            echo "   ⚠ User already exists with this email (ID: {$existingUser['id']}). Skipping.\n\n";
            $skippedCount++;
            continue;
        }

        try {
            // Create user from client data
            // Generate username from name
            $username = strtolower(str_replace(' ', '_', $client['name']));
            $username = preg_replace('/[^a-z0-9_]/', '', $username);

            // Check if username exists, add number if needed
            $originalUsername = $username;
            $counter = 1;
            while (true) {
                $checkUsername = $db->prepare("SELECT id FROM $usersTable WHERE username = ?");
                $checkUsername->execute([$username]);
                if (!$checkUsername->fetch()) {
                    break;
                }
                $username = $originalUsername . $counter;
                $counter++;
            }

            // Generate a random password (user will need to reset it)
            $tempPassword = bin2hex(random_bytes(8));
            $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);

            // Insert user
            $insertStmt = $db->prepare("
                INSERT INTO $usersTable 
                (username, password, email, role_id, public_name, phone, created_at) 
                VALUES (?, ?, ?, 3, ?, ?, NOW())
            ");

            $insertStmt->execute([
                $username,
                $hashedPassword,
                $client['email'],
                $client['name'],
                $client['phone']
            ]);

            $newUserId = $db->lastInsertId();

            echo "   ✓ Created user (ID: $newUserId, username: $username)\n";
            echo "   ℹ Temporary password: $tempPassword (user should reset this)\n";

            // Update any projects that reference this client
            $updateProjects = $db->prepare("
                UPDATE projects 
                SET billing_user_id = ? 
                WHERE client_id = ?
            ");
            $updateProjects->execute([$newUserId, $client['id']]);
            $updatedProjects = $updateProjects->rowCount();

            if ($updatedProjects > 0) {
                echo "   ✓ Updated $updatedProjects project(s) to reference new user\n";
            }

            // Mark client as migrated (update status)
            $markMigrated = $db->prepare("
                UPDATE $clientsTable 
                SET status = 'migrated', updated_at = NOW() 
                WHERE id = ?
            ");
            $markMigrated->execute([$client['id']]);

            echo "   ✓ Marked client as migrated\n\n";
            $migratedCount++;

        } catch (Exception $e) {
            echo "   ✗ Error migrating client: " . $e->getMessage() . "\n\n";
            $errorCount++;
        }
    }

    echo "=== Migration Complete ===\n";
    echo "Migrated: $migratedCount\n";
    echo "Skipped: $skippedCount\n";
    echo "Errors: $errorCount\n\n";

    if ($migratedCount > 0) {
        echo "⚠ IMPORTANT: Migrated users have temporary passwords.\n";
        echo "   Please send password reset emails to these users.\n\n";
    }

} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
