<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Database Infrastructure Manager
 * Handles automatic installation and schema synchronization.
 */
class Installer
{
    /**
     * The Master Schema definition.
     * This is the "Truth" of how the database should look.
     */
    private static $SCHEMA = [
        'roles' => [
            'sql' => "CREATE TABLE roles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT UNIQUE NOT NULL,
                description TEXT,
                permissions TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )"
        ],
        'groups' => [
            'sql' => "CREATE TABLE groups (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT UNIQUE NOT NULL,
                description TEXT,
                permissions TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )"
        ],
        'users' => [
            'sql' => "CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE,
                password TEXT,
                role_id INTEGER,
                group_id INTEGER,
                status INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP, public_name TEXT, phone TEXT, address TEXT, email TEXT,
                FOREIGN KEY (role_id) REFERENCES roles(id),
                FOREIGN KEY (group_id) REFERENCES groups(id)
                )"
        ],
        'databases' => [
            'sql' => "CREATE TABLE databases (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT,
                path TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                , project_id INTEGER, last_edit_at DATETIME, config TEXT)"
        ],
        'fields_config' => [
            'sql' => "CREATE TABLE fields_config (
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
                )"
        ],
        'media_config' => [
            'sql' => "CREATE TABLE media_config (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                mime_type TEXT,
                extension TEXT,
                is_allowed INTEGER DEFAULT 1
                )"
        ],
        'api_keys' => [
            'sql' => "CREATE TABLE api_keys (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                key_value TEXT UNIQUE,
                name TEXT,
                permissions TEXT,
                status INTEGER DEFAULT 1,
                user_id INTEGER
                , project_id INTEGER)"
        ],
        'api_endpoints' => [
            'sql' => "CREATE TABLE api_endpoints (
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
                )"
        ],
        'logs' => [
            'sql' => "CREATE TABLE logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                type TEXT,
                details TEXT,
                response_time FLOAT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )"
        ],
        'media_trash' => [
            'sql' => "CREATE TABLE media_trash (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                original_path TEXT NOT NULL,
                original_name TEXT NOT NULL,
                trash_path TEXT NOT NULL,
                file_size INTEGER,
                deleted_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )"
        ],
        'system_settings' => [
            'sql' => "CREATE TABLE system_settings (
                key TEXT PRIMARY KEY,
                value TEXT
                )"
        ],
        'billing_services' => [
            'sql' => "CREATE TABLE billing_services (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                description TEXT,
                price_monthly REAL DEFAULT 0,
                price_yearly REAL DEFAULT 0,
                price_one_time REAL DEFAULT 0,
                price REAL DEFAULT 0,
                status TEXT DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )"
        ],
        'projects' => [
            'sql' => "CREATE TABLE projects (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT UNIQUE NOT NULL,
                description TEXT,
                status TEXT DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                storage_quota INTEGER DEFAULT 300,
                client_id INTEGER,
                billing_user_id INTEGER,
                start_date DATE,
                current_plan_id INTEGER,
                billing_status TEXT DEFAULT 'active',
                FOREIGN KEY(client_id) REFERENCES clients(id),
                FOREIGN KEY(billing_user_id) REFERENCES users(id),
                FOREIGN KEY(current_plan_id) REFERENCES payment_plans(id)
                )"
        ],
        'project_services' => [
            'sql' => "CREATE TABLE project_services (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                project_id INTEGER NOT NULL,
                service_id INTEGER NOT NULL,
                custom_price REAL,
                billing_period TEXT,
                quantity INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
                FOREIGN KEY (service_id) REFERENCES billing_services(id)
            )"
        ],
        'project_users' => [
            'sql' => "CREATE TABLE project_users (
                project_id INTEGER,
                user_id INTEGER,
                permissions TEXT,
                assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (project_id, user_id),
                FOREIGN KEY (project_id) REFERENCES projects(id),
                FOREIGN KEY (user_id) REFERENCES users(id)
                )"
        ],
        'project_plans' => [
            'sql' => "CREATE TABLE project_plans (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                project_id INTEGER UNIQUE,
                plan_type TEXT,
                start_date DATETIME,
                next_billing_date DATETIME,
                status TEXT DEFAULT 'active',
                FOREIGN KEY (project_id) REFERENCES projects(id)
                )"
        ],
        'subscription_history' => [
            'sql' => "CREATE TABLE subscription_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                project_id INTEGER,
                old_plan TEXT,
                new_plan TEXT,
                change_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (project_id) REFERENCES projects(id)
                )"
        ],
        'table_metadata' => [
            'sql' => "CREATE TABLE table_metadata (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                db_id INTEGER,
                table_name TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_edit_at DATETIME,
                FOREIGN KEY (db_id) REFERENCES databases(id),
                UNIQUE(db_id, table_name)
                )"
        ],
        'activity_logs' => [
            'sql' => "CREATE TABLE activity_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, project_id INTEGER, action TEXT, details TEXT, ip_address TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)"
        ],
        'webhooks' => [
            'sql' => "CREATE TABLE webhooks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                project_id INTEGER,
                name TEXT NOT NULL,
                url TEXT NOT NULL,
                events TEXT NOT NULL,
                secret TEXT,
                status INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_triggered_at DATETIME
                )"
        ],
        'webhook_logs' => [
            'sql' => "CREATE TABLE webhook_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                webhook_id INTEGER,
                event TEXT,
                payload TEXT,
                response_code INTEGER,
                response_body TEXT,
                triggered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(webhook_id) REFERENCES webhooks(id) ON DELETE CASCADE
                )"
        ],
        'data_versions' => [
            'sql' => "CREATE TABLE data_versions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                database_id INTEGER,
                table_name TEXT,
                record_id INTEGER,
                action TEXT,
                old_data TEXT,
                new_data TEXT,
                user_id INTEGER,
                api_key_id INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(database_id) REFERENCES databases(id) ON DELETE CASCADE
            )"
        ],
        'clients' => [
            'sql' => "CREATE TABLE clients (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT,
                phone TEXT,
                address TEXT,
                tax_id TEXT,
                status TEXT DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )"
        ],
        'payment_plans' => [
            'sql' => "CREATE TABLE payment_plans (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                frequency TEXT NOT NULL,
                installments INTEGER NOT NULL,
                contract_duration_months INTEGER,
                description TEXT,
                status TEXT DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )"
        ],
        'installments' => [
            'sql' => "CREATE TABLE installments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                project_id INTEGER NOT NULL,
                plan_id INTEGER NOT NULL,
                installment_number INTEGER NOT NULL,
                due_date DATE NOT NULL,
                amount REAL NOT NULL,
                status TEXT DEFAULT 'pendiente',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE CASCADE,
                FOREIGN KEY(plan_id) REFERENCES payment_plans(id)
            )"
        ],
        'payments' => [
            'sql' => "CREATE TABLE payments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                installment_id INTEGER NOT NULL,
                amount REAL NOT NULL,
                payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                payment_method TEXT,
                reference TEXT,
                notes TEXT,
                status TEXT DEFAULT 'approved',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(installment_id) REFERENCES installments(id) ON DELETE CASCADE
            )"
        ],
        'project_plan_history' => [
            'sql' => "CREATE TABLE project_plan_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                project_id INTEGER NOT NULL,
                old_plan_id INTEGER,
                new_plan_id INTEGER,
                old_start_date DATE,
                new_start_date DATE,
                change_reason TEXT,
                changed_by INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE CASCADE,
                FOREIGN KEY(old_plan_id) REFERENCES payment_plans(id),
                FOREIGN KEY(new_plan_id) REFERENCES payment_plans(id),
                FOREIGN KEY(changed_by) REFERENCES users(id)
            )"
        ],
        'notifications_log' => [
            'sql' => "CREATE TABLE notifications_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                installment_id INTEGER NOT NULL,
                notification_type TEXT NOT NULL,
                recipient TEXT NOT NULL,
                status TEXT DEFAULT 'sent',
                sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                error_message TEXT,
                FOREIGN KEY(installment_id) REFERENCES installments(id) ON DELETE CASCADE
            )"
        ]
    ];

    /**
     * Main entry point for checks.
     */
    public static function check()
    {
        $dbPath = Config::get('db_path');
        $dataDir = dirname($dbPath);
        $uploadDir = Config::get('upload_dir');

        if (!is_dir($dataDir))
            mkdir($dataDir, 0755, true);
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0755, true);

        // Run Synchronization
        self::syncSchema($dbPath);
    }

    /**
     * Synchronizes the physical database with the expected schema.
     */
    private static function syncSchema($dbPath)
    {
        $isNew = !file_exists($dbPath);

        try {
            $db = new PDO('sqlite:' . $dbPath);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // 1. Ensure all tables exist
            foreach (self::$SCHEMA as $tableName => $definition) {
                $exists = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$tableName'")->fetch();
                if (!$exists) {
                    $db->exec($definition['sql']);
                } else {
                    // Synchronize columns for existing tables
                    self::syncColumns($db, $tableName, $definition['sql']);
                }
            }

            // 2. Data Initialization (Only if it was new)
            if ($isNew) {
                self::seedDefaults($db);
            }

            // 3. Maintenance / Dynamic checks
            self::runHealthChecks($db);

        } catch (PDOException $e) {
            if (Config::get('dev_mode') === 'on') {
                die("Database Sync Error: " . $e->getMessage());
            }
        }
    }

    /**
     * Compares table columns and adds missing ones via ALTER TABLE.
     */
    private static function syncColumns($db, $tableName, $createSql)
    {
        // Extract column names from the SQL definition (Regex for SQLite CREATE TABLE content)
        // Matches "col_name type..." and handles leading commas
        preg_match_all('/(?:,|\()\s*([a-zA-Z0-9_]+)\s+([a-zA-Z0-9_]+)/i', $createSql, $matches);
        $expectedColumns = $matches[1];
        $columnTypes = $matches[2];

        // Get current columns
        $stmt = $db->query("PRAGMA table_info($tableName)");
        $currentColumns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);

        foreach ($expectedColumns as $index => $col) {
            // Ignore SQL keywords that might be caught
            if (in_array(strtoupper($col), ['PRIMARY', 'FOREIGN', 'CONSTRAINT', 'UNIQUE', 'CHECK']))
                continue;

            if (!in_array($col, $currentColumns)) {
                // Find the full definition for this column in the original SQL
                // Look for column name followed by its type and constraints
                if (preg_match('/(?:\(|\,)\s*' . $col . '\s+([^,)]+)/i', $createSql, $defMatch)) {
                    $colDef = trim($defMatch[1]);
                    // Only add if it's not a COMPLEX constraint (SQLite has limits on ALTER TABLE)
                    if (strpos(strtoupper($colDef), 'PRIMARY KEY') === false) {
                        try {
                            $db->exec("ALTER TABLE $tableName ADD COLUMN $col $colDef");
                        } catch (PDOException $e) {
                            // Column might exist or SQLite limitation
                        }
                    }
                }
            }
        }
    }

    /**
     * Basic data seeding for new installations.
     */
    private static function seedDefaults($db)
    {
        // Default Admin Role
        $adminPermissions = json_encode(['all' => true]);
        $stmt = $db->prepare("INSERT INTO roles (name, description, permissions) VALUES ('Administrator', 'Full system access', ?)");
        $stmt->execute([$adminPermissions]);
        $adminRoleId = $db->lastInsertId();

        // Manager Role
        $managerPermissions = json_encode(['all' => false, 'modules' => ['billing' => ['view', 'manage']], 'databases' => []]);
        $db->prepare("INSERT INTO roles (name, description, permissions) VALUES ('Manager', 'Can manage billing and projects', ?)")
            ->execute([$managerPermissions]);

        // Client Role (ID 4)
        $clientPermissions = json_encode(['all' => false, 'modules' => ['billing' => ['view']], 'databases' => []]);
        $db->prepare("INSERT INTO roles (name, description, permissions) VALUES ('Client', 'Customer access to see their own data', ?)")
            ->execute([$clientPermissions]);
        $clientRoleId = $db->lastInsertId();

        // Initial Admin User (admin / admin123)
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO users (username, password, role_id) VALUES ('admin', ?, ?)")
            ->execute([$password, $adminRoleId]);

        // Initial Standard User (user1 / user123)
        $userPassword = password_hash('user123', PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO users (username, password, role_id) VALUES ('user1', ?, ?)")
            ->execute([$userPassword, $clientRoleId]);

        // Default Settings
        $settings = [
            'dev_mode' => 'off',
            'media_trash_retention' => '30',
            'app_language' => 'es',
            'show_welcome_banner' => '1',
            'time_offset_total' => '0'
        ];
        $stmt = $db->prepare("INSERT INTO system_settings (key, value) VALUES (?, ?)");
        foreach ($settings as $k => $v) {
            $stmt->execute([$k, $v]);
        }

        // Default Payment Plans
        $db->prepare("INSERT INTO payment_plans (name, frequency, installments, description) VALUES (?, ?, ?, ?)")
            ->execute(['Plan Mensual', 'monthly', 12, 'Plan de pago mensual con 12 cuotas']);

        $db->prepare("INSERT INTO payment_plans (name, frequency, installments, description) VALUES (?, ?, ?, ?)")
            ->execute(['Plan Anual', 'yearly', 1, 'Plan de pago anual con 1 cuota']);
    }

    /**
     * Miscellaneous logic for database health and backward compatibility.
     */
    private static function runHealthChecks($db)
    {
        // Ensure at least one project exists if there are databases
        $orphans = $db->query("SELECT COUNT(*) FROM databases WHERE project_id IS NULL")->fetchColumn();
        if ($orphans > 0) {
            $projectCheck = $db->query("SELECT id FROM projects LIMIT 1")->fetch();
            if (!$projectCheck) {
                $db->exec("INSERT INTO projects (name, description) VALUES ('Default Project', 'System generated')");
                $projectId = $db->lastInsertId();
            } else {
                $projectId = $projectCheck['id'];
            }
            $db->prepare("UPDATE databases SET project_id = ? WHERE project_id IS NULL")->execute([$projectId]);
        }

        // Final sanity check for settings
        $db->exec("INSERT OR IGNORE INTO system_settings (key, value) VALUES ('media_trash_retention', '30')");
        $db->exec("INSERT OR IGNORE INTO system_settings (key, value) VALUES ('time_offset_total', '0')");
    }
}
