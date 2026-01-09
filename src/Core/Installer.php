<?php

namespace App\Core;

use PDO;
use PDOException;

class Installer
{
    public static function check()
    {
        $dbPath = Config::get('db_path');
        $dataDir = dirname($dbPath);
        $uploadDir = Config::get('upload_dir');

        // Create directories if missing
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // If DB doesn't exist, initialize
        if (!file_exists($dbPath)) {
            self::initDatabase($dbPath);
        } else {
            self::runMigrations($dbPath);
        }
    }

    private static function runMigrations($dbPath)
    {
        try {
            $db = new PDO('sqlite:' . $dbPath);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // 1. Add missing columns to 'databases'
            $stmt = $db->query("PRAGMA table_info(databases)");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);

            if (!in_array('project_id', $columns)) {
                $db->exec("ALTER TABLE databases ADD COLUMN project_id INTEGER");
            }
            if (!in_array('last_edit_at', $columns)) {
                $db->exec("ALTER TABLE databases ADD COLUMN last_edit_at DATETIME");
            }

            // 2. Add missing columns to 'api_keys'
            $stmt = $db->query("PRAGMA table_info(api_keys)");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
            if (!in_array('project_id', $columns)) {
                $db->exec("ALTER TABLE api_keys ADD COLUMN project_id INTEGER");
            }

            // 3. Create missing tables
            $tables = [
                'projects' => "CREATE TABLE projects (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT UNIQUE NOT NULL,
                    description TEXT,
                    status TEXT DEFAULT 'active',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )",
                'project_users' => "CREATE TABLE project_users (
                    project_id INTEGER,
                    user_id INTEGER,
                    permissions TEXT,
                    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (project_id, user_id),
                    FOREIGN KEY (project_id) REFERENCES projects(id),
                    FOREIGN KEY (user_id) REFERENCES users(id)
                )",
                'project_plans' => "CREATE TABLE project_plans (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    project_id INTEGER UNIQUE,
                    plan_type TEXT,
                    start_date DATETIME,
                    next_billing_date DATETIME,
                    status TEXT DEFAULT 'active',
                    FOREIGN KEY (project_id) REFERENCES projects(id)
                )",
                'subscription_history' => "CREATE TABLE subscription_history (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    project_id INTEGER,
                    old_plan TEXT,
                    new_plan TEXT,
                    change_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (project_id) REFERENCES projects(id)
                )",
                'table_metadata' => "CREATE TABLE table_metadata (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    db_id INTEGER,
                    table_name TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    last_edit_at DATETIME,
                    FOREIGN KEY (db_id) REFERENCES databases(id),
                    UNIQUE(db_id, table_name)
                )",
                'media_trash' => "CREATE TABLE media_trash (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    original_path TEXT NOT NULL,
                    original_name TEXT NOT NULL,
                    trash_path TEXT NOT NULL,
                    file_size INTEGER,
                    deleted_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )",
                'activity_logs' => "CREATE TABLE activity_logs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER,
                    project_id INTEGER,
                    action TEXT,
                    details TEXT,
                    ip_address TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )"
            ];

            foreach ($tables as $name => $query) {
                $check = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$name'")->fetch();
                if (!$check) {
                    $db->exec($query);
                }
            }

            // 4. Check system_settings structure (Legacy migration)
            $stmt = $db->query("PRAGMA table_info(system_settings)");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
            if (in_array('id', $columns)) {
                // If it has 'id', we migrate to the simpler (key, value) structure
                $db->exec("CREATE TABLE system_settings_new (key TEXT PRIMARY KEY, value TEXT)");
                $db->exec("INSERT OR IGNORE INTO system_settings_new (key, value) SELECT key, value FROM system_settings");
                $db->exec("DROP TABLE system_settings");
                $db->exec("ALTER TABLE system_settings_new RENAME TO system_settings");
            }

            // 5. Check if we need to create a default project for orphaned dbs
            $orphans = $db->query("SELECT COUNT(*) FROM databases WHERE project_id IS NULL")->fetchColumn();
            if ($orphans > 0) {
                // Ensure at least one project exists
                $projectCheck = $db->query("SELECT id FROM projects LIMIT 1")->fetch();
                if (!$projectCheck) {
                    $db->exec("INSERT INTO projects (name, description) VALUES ('Default Project', 'System generated project for existing data')");
                    $projectId = $db->lastInsertId();

                    // Assign admin user to this project
                    $adminId = $db->query("SELECT id FROM users WHERE username = 'admin'")->fetchColumn();
                    if ($adminId) {
                        $db->prepare("INSERT OR IGNORE INTO project_users (project_id, user_id, permissions) VALUES (?, ?, ?)")
                            ->execute([$projectId, $adminId, json_encode(['all' => true])]);
                    }
                } else {
                    $projectId = $projectCheck['id'];
                }

                // Update orphaned databases
                $db->prepare("UPDATE databases SET project_id = ? WHERE project_id IS NULL")
                    ->execute([$projectId]);
            }

        } catch (PDOException $e) {
            // Log or handle migration error
        }
    }

    private static function initDatabase($dbPath)
    {
        try {
            $db = new PDO('sqlite:' . $dbPath);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $queries = [
                // 1. Core Structures (No dependencies)
                "CREATE TABLE roles (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT UNIQUE NOT NULL,
                    description TEXT,
                    permissions TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE groups (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT UNIQUE NOT NULL,
                    description TEXT,
                    permissions TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE projects (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT UNIQUE NOT NULL,
                    description TEXT,
                    status TEXT DEFAULT 'active',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE media_config (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    mime_type TEXT,
                    extension TEXT,
                    is_allowed INTEGER DEFAULT 1
                )",
                "CREATE TABLE api_endpoints (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT,
                    path TEXT UNIQUE,
                    method TEXT,
                    table_source TEXT,
                    sql_query TEXT,
                    requires_auth INTEGER DEFAULT 1,
                    return_type TEXT,
                    visible_fields TEXT,
                    is_active INTEGER DEFAULT 1
                )",
                "CREATE TABLE logs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    type TEXT,
                    details TEXT,
                    response_time FLOAT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE system_settings (
                    key TEXT PRIMARY KEY,
                    value TEXT
                )",
                "CREATE TABLE media_trash (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    original_path TEXT NOT NULL,
                    original_name TEXT NOT NULL,
                    trash_path TEXT NOT NULL,
                    file_size INTEGER,
                    deleted_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )",

                // 2. Dependent Structures (Level 1)
                "CREATE TABLE users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    username TEXT UNIQUE,
                    password TEXT,
                    role_id INTEGER,
                    group_id INTEGER,
                    status INTEGER DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (role_id) REFERENCES roles(id),
                    FOREIGN KEY (group_id) REFERENCES groups(id)
                )",
                "CREATE TABLE databases (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    project_id INTEGER,
                    name TEXT,
                    path TEXT,
                    last_edit_at DATETIME,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (project_id) REFERENCES projects(id)
                )",
                "CREATE TABLE project_users (
                    project_id INTEGER,
                    user_id INTEGER,
                    permissions TEXT,
                    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (project_id, user_id),
                    FOREIGN KEY (project_id) REFERENCES projects(id),
                    FOREIGN KEY (user_id) REFERENCES users(id)
                )",
                "CREATE TABLE project_plans (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    project_id INTEGER UNIQUE,
                    plan_type TEXT,
                    start_date DATETIME,
                    next_billing_date DATETIME,
                    status TEXT DEFAULT 'active',
                    FOREIGN KEY (project_id) REFERENCES projects(id)
                )",
                "CREATE TABLE subscription_history (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    project_id INTEGER,
                    old_plan TEXT,
                    new_plan TEXT,
                    change_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (project_id) REFERENCES projects(id)
                )",
                "CREATE TABLE api_keys (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    project_id INTEGER,
                    key_value TEXT UNIQUE,
                    name TEXT,
                    permissions TEXT,
                    status INTEGER DEFAULT 1,
                    FOREIGN KEY (project_id) REFERENCES projects(id)
                )",
                "CREATE TABLE activity_logs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER,
                    project_id INTEGER,
                    action TEXT,
                    details TEXT,
                    ip_address TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id),
                    FOREIGN KEY (project_id) REFERENCES projects(id)
                )",

                // 3. Dependent Structures (Level 2)
                "CREATE TABLE fields_config (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    db_id INTEGER,
                    table_name TEXT,
                    field_name TEXT,
                    data_type TEXT,
                    view_type TEXT,
                    is_required INTEGER DEFAULT 0,
                    is_visible INTEGER DEFAULT 1,
                    is_editable INTEGER DEFAULT 1,
                    is_foreign_key INTEGER DEFAULT 0,
                    related_table TEXT,
                    related_field TEXT,
                    options TEXT,
                    FOREIGN KEY (db_id) REFERENCES databases(id)
                )",
                "CREATE TABLE table_metadata (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    db_id INTEGER,
                    table_name TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    last_edit_at DATETIME,
                    FOREIGN KEY (db_id) REFERENCES databases(id),
                    UNIQUE(db_id, table_name)
                )"
            ];

            foreach ($queries as $query) {
                $db->exec($query);
            }

            // Create default admin role with all permissions
            $adminPermissions = json_encode(['all' => true]);
            $stmt = $db->prepare("INSERT INTO roles (name, description, permissions) VALUES ('Administrator', 'Full system access', ?)");
            $stmt->execute([$adminPermissions]);
            $adminRoleId = $db->lastInsertId();

            // Create default user role with limited permissions
            $userPermissions = json_encode(['all' => false, 'modules' => [], 'databases' => []]);
            $stmt = $db->prepare("INSERT INTO roles (name, description, permissions) VALUES ('User', 'Standard user access', ?)");
            $stmt->execute([$userPermissions]);

            // Create default admin user (password: admin123)
            $password = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, password, role_id) VALUES ('admin', ?, ?)");
            $stmt->execute([$password, $adminRoleId]);

            // Insert Default System Settings
            $defaultSettings = [
                'dev_mode' => 'off',
                'media_trash_retention' => '30',
                'app_language' => 'es'
            ];

            $stmt = $db->prepare("INSERT INTO system_settings (key, value) VALUES (?, ?)");
            foreach ($defaultSettings as $key => $val) {
                $stmt->execute([$key, $val]);
            }

        } catch (PDOException $e) {
            die("Auto-Installation Error: " . $e->getMessage());
        }
    }
}
