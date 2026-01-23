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
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP, public_name TEXT, phone TEXT, address TEXT, email TEXT, tax_id TEXT,
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
                , project_id INTEGER, last_edit_at DATETIME, config TEXT, type TEXT DEFAULT 'sqlite')"
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
                status INTEGER DEFAULT 1
                , project_id INTEGER, user_id INTEGER, rate_limit INTEGER DEFAULT 1000, description TEXT)"
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
                key_name TEXT PRIMARY KEY,
                value TEXT,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )"
        ],
        'projects' => [
            'sql' => "CREATE TABLE projects (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT UNIQUE NOT NULL,
                description TEXT,
                status TEXT DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                , storage_quota INTEGER DEFAULT 300, client_id INTEGER, start_date DATE, current_plan_id INTEGER, billing_status TEXT DEFAULT 'active', billing_user_id INTEGER REFERENCES users(id))"
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
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP, api_key_id INTEGER,
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
                description TEXT,
                status TEXT DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                , contract_duration_months INTEGER)"
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
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP, status TEXT DEFAULT 'approved',
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
        ],
        'billing_services' => [
            'sql' => "CREATE TABLE billing_services (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                description TEXT,
                status TEXT DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                , price REAL DEFAULT 0, price_monthly REAL DEFAULT 0, price_yearly REAL DEFAULT 0, price_one_time REAL DEFAULT 0)"
        ],
        'project_services' => [
            'sql' => "CREATE TABLE project_services (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                project_id INTEGER NOT NULL REFERENCES projects(id),
                service_id INTEGER NOT NULL REFERENCES billing_services(id),
                custom_price REAL,
                billing_period TEXT,
                quantity INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )"
        ],
        'task_statuses' => [
            'sql' => "CREATE TABLE task_statuses (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                slug TEXT UNIQUE NOT NULL,
                color TEXT DEFAULT '#6366f1',
                position INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )"
        ],
        'tasks' => [
            'sql' => "CREATE TABLE tasks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                project_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                description TEXT,
                priority TEXT DEFAULT 'medium',
                status_id INTEGER NOT NULL,
                assigned_to INTEGER,
                created_by INTEGER NOT NULL,
                position INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP, service_id INTEGER REFERENCES billing_services(id),
                FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE CASCADE,
                FOREIGN KEY(status_id) REFERENCES task_statuses(id),
                FOREIGN KEY(assigned_to) REFERENCES users(id),
                FOREIGN KEY(created_by) REFERENCES users(id)
                )"
        ],
        'task_history' => [
            'sql' => "CREATE TABLE task_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                task_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                action TEXT NOT NULL,
                old_status_id INTEGER,
                new_status_id INTEGER,
                comment TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(task_id) REFERENCES tasks(id) ON DELETE CASCADE,
                FOREIGN KEY(user_id) REFERENCES users(id),
                FOREIGN KEY(old_status_id) REFERENCES task_statuses(id),
                FOREIGN KEY(new_status_id) REFERENCES task_statuses(id)
                )"
        ],
        'billing_service_templates' => [
            'sql' => "CREATE TABLE billing_service_templates (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                service_id INTEGER NOT NULL REFERENCES billing_services(id) ON DELETE CASCADE,
                title TEXT NOT NULL,
                description TEXT,
                priority TEXT DEFAULT 'medium',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )"
        ],
        'task_comments' => [
            'sql' => "CREATE TABLE task_comments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                task_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                comment TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(task_id) REFERENCES tasks(id) ON DELETE CASCADE,
                FOREIGN KEY(user_id) REFERENCES users(id)
                )"
        ],
        'api_rate_limits' => [
            'sql' => "CREATE TABLE api_rate_limits (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                api_key_id INTEGER NOT NULL,
                endpoint TEXT NOT NULL,
                request_count INTEGER DEFAULT 0,
                window_start DATETIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(api_key_id) REFERENCES api_keys(id) ON DELETE CASCADE
                )"
        ],
        'api_key_permissions' => [
            'sql' => "CREATE TABLE api_key_permissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                api_key_id INTEGER NOT NULL,
                database_id INTEGER,
                table_name TEXT,
                can_read INTEGER DEFAULT 0,
                can_create INTEGER DEFAULT 0,
                can_update INTEGER DEFAULT 0,
                can_delete INTEGER DEFAULT 0,
                allowed_ips TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(api_key_id) REFERENCES api_keys(id) ON DELETE CASCADE,
                FOREIGN KEY(database_id) REFERENCES databases(id) ON DELETE CASCADE
                )"
        ],
        'api_cache' => [
            'sql' => "CREATE TABLE api_cache (
                cache_key TEXT PRIMARY KEY,
                data TEXT NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )"
        ],
        'api_access_logs' => [
            'sql' => "CREATE TABLE api_access_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                api_key_id INTEGER,
                method TEXT,
                endpoint TEXT,
                status_code INTEGER,
                ip_address TEXT,
                response_time_ms FLOAT,
                user_agent TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )"
        ],
        'webhook_queue' => [
            'sql' => "CREATE TABLE webhook_queue (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                project_id INTEGER,
                url TEXT,
                event TEXT,
                payload TEXT,
                attempts INTEGER DEFAULT 0,
                next_attempt_at DATETIME,
                status TEXT DEFAULT 'pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_error TEXT
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
    /**
     * Synchronizes the physical database with the expected schema.
     */
    private static function syncSchema($dbPath)
    {
        // For SQLite, check file existence. For others, check tables.
        $config = Config::get('system_db_config');
        $isSQLite = ($config && ($config['type'] ?? 'sqlite') === 'sqlite');
        $isNew = $isSQLite ? !file_exists($dbPath) : false; // For remote DBs, we check if tables exist later

        try {
            // Use the configured system database adapter
            $adapter = Database::getInstance()->getAdapter();
            $db = Database::getInstance()->getConnection();

            // For remote DBs, if we can connect, assumes DB exists (created by InstallController)
            if (!$isSQLite) {
                // Check if 'users' table exists as a proxy for "isNew"
                $checkSql = $adapter->getTableExistsSQL('users');
                try {
                    $check = $db->query($checkSql);
                    $isNew = ($check->fetch() === false);
                    $check->closeCursor();
                } catch (\Exception $e) {
                    $isNew = true;
                }
            }

            error_log("Installer: Starting sync. Type: " . $adapter->getType() . ", isNew: " . ($isNew ? 'true' : 'false'));

            // 0. Pre-Schema Migration: key -> key_name
            // Must be done BEFORE syncColumns sees 'key_name' missing and adds an empty one.
            try {
                $checkTable = $adapter->getTableExistsSQL('system_settings');
                $tblExists = false;
                try {
                    $res = $db->query($checkTable);
                    $tblExists = (bool) $res->fetchColumn();
                } catch (\Exception $e) {
                }

                if ($tblExists) {
                    $cols = [];
                    $type = $adapter->getType();

                    if ($type === 'sqlite') {
                        $stmt = $db->query("PRAGMA table_info(system_settings)");
                        $cols = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
                    } elseif ($type === 'mysql') {
                        $stmt = $db->query("SHOW COLUMNS FROM system_settings");
                        $cols = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
                    } elseif ($type === 'pgsql') {
                        $stmt = $db->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'system_settings'");
                        $cols = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
                    }

                    // If 'key' exists but 'key_name' does not, rename it
                    if (in_array('key', $cols) && !in_array('key_name', $cols)) {
                        error_log("Installer: Migrating system_settings column 'key' to 'key_name'...");
                        if ($type === 'sqlite') {
                            $db->exec("ALTER TABLE system_settings RENAME COLUMN key TO key_name");
                        } elseif ($type === 'mysql') {
                            $db->exec("ALTER TABLE system_settings CHANGE `key` `key_name` VARCHAR(255) NOT NULL");
                        } elseif ($type === 'pgsql') {
                            $db->exec("ALTER TABLE system_settings RENAME COLUMN \"key\" TO key_name");
                        }
                    }

                    // Ensure updated_at exists (Migration for production)
                    if (!in_array('updated_at', $cols)) {
                        error_log("Installer: Adding 'updated_at' column to system_settings...");
                        if ($type === 'sqlite') {
                            $db->exec("ALTER TABLE system_settings ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP");
                        } elseif ($type === 'mysql') {
                            $db->exec("ALTER TABLE system_settings ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP");
                        } elseif ($type === 'pgsql') {
                            $db->exec("ALTER TABLE system_settings ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
                        }
                    }
                }

                // Pre-Migration: users -> google_id
                $checkUsers = $adapter->getTableExistsSQL('users');
                $usersExist = false;
                try {
                    $res = $db->query($checkUsers);
                    $usersExist = (bool) $res->fetchColumn();
                } catch (\Exception $e) {
                }

                if ($usersExist) {
                    $cols = [];
                    $type = $adapter->getType();
                    if ($type === 'sqlite') {
                        $stmt = $db->query("PRAGMA table_info(users)");
                        $cols = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
                    } elseif ($type === 'mysql') {
                        $stmt = $db->query("SHOW COLUMNS FROM users");
                        $cols = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
                    } elseif ($type === 'pgsql') {
                        $stmt = $db->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'users'");
                        $cols = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
                    }

                    if (!in_array('google_id', $cols)) {
                        error_log("Installer: Adding 'google_id' column to users...");
                        if ($type === 'sqlite') {
                            $db->exec("ALTER TABLE users ADD COLUMN google_id TEXT");
                        } elseif ($type === 'mysql') {
                            $db->exec("ALTER TABLE users ADD COLUMN google_id TEXT");
                        } elseif ($type === 'pgsql') {
                            $db->exec("ALTER TABLE users ADD COLUMN google_id TEXT");
                        }
                    }
                }
            } catch (\Exception $e) {
                error_log("Pre-Migration warning: " . $e->getMessage());
            }

            // 1. Ensure all tables exist
            foreach (self::$SCHEMA as $tableName => $definition) {
                // Use Adapter to check existence
                $existsSql = $adapter->getTableExistsSQL($tableName);
                $exists = false;
                try {
                    $result = $db->query($existsSql);
                    // Use fetchColumn to handle both "SELECT name" (SQLite) and "SELECT EXISTS" (Postgres)
                    // Postgres returns boolean true/false. SQLite returns name (truthy) or false (falsy).
                    $exists = (bool) $result->fetchColumn();
                    $result->closeCursor();
                } catch (\Exception $e) {
                    $exists = false;
                }

                if (!$exists) {
                    error_log("Installer: Table [$tableName] does not exist. Creating...");
                    // Create Table
                    $sql = $definition['sql'];

                    if ($adapter->getType() === 'mysql') {
                        // 1. Basic syntax repairs
                        $sql = str_replace('AUTOINCREMENT', 'AUTO_INCREMENT', $sql);
                        $sql = str_replace('STRING', 'VARCHAR(255)', $sql);

                        // 2. Escape table name
                        $sql = preg_replace('/CREATE TABLE (\w+)/i', 'CREATE TABLE `$1`', $sql);

                        // 3. MySQL Limitation: TEXT columns cannot have DEFAULT values. Convert to VARCHAR(255).
                        // This fixes the 'projects.status' and 'projects.billing_status' errors.
                        $sql = preg_replace('/(\w+)\s+TEXT\s+DEFAULT/i', '`$1` VARCHAR(255) DEFAULT', $sql);

                        // 4. MySQL Limitation: UNIQUE/PRIMARY KEY cannot be on plain TEXT columns (Inline).
                        $sql = preg_replace('/(\w+)\s+TEXT\s+UNIQUE/i', '`$1` VARCHAR(255) UNIQUE', $sql);
                        $sql = preg_replace('/(\w+)\s+TEXT\s+PRIMARY KEY/i', '`$1` VARCHAR(255) PRIMARY KEY', $sql);

                        // 5. MySQL Limitation: UNIQUE/PRIMARY KEY constraints at the end of CREATE TABLE
                        // If a column is in a UNIQUE() index, it cannot be TEXT.
                        if (preg_match('/UNIQUE\s*\(([^)]+)\)/i', $sql, $matches)) {
                            $cols = explode(',', $matches[1]);
                            foreach ($cols as $col) {
                                $col = trim($col, " \t\n\r\0\x0B` ");
                                // Find 'colname TEXT' and change to 'colname VARCHAR(255)'
                                $sql = preg_replace('/(\b' . preg_quote($col, '/') . '\b)\s+TEXT/i', '$1 VARCHAR(255)', $sql);
                            }
                        }

                        // 6. Escape all column names (heuristic: match words at start of line or after comma/parenthesis)
                        $sql = preg_replace('/^\s*(\w+)\s+(INTEGER|TEXT|VARCHAR|DATETIME|DATE|FLOAT|TIMESTAMP|SERIAL|BOOLEAN)/im', '`$1` $2', $sql);
                        $sql = preg_replace('/(,\s*|\(\s*)(\w+)\s+(INTEGER|TEXT|VARCHAR|DATETIME|DATE|FLOAT|TIMESTAMP|SERIAL|BOOLEAN)/i', '$1`$2` $3', $sql);

                        // 7. Escape Foreign Keys
                        $sql = preg_replace('/FOREIGN KEY\s*\((\w+)\)\s*REFERENCES\s*(\w+)\s*\((\w+)\)/i', 'FOREIGN KEY (`$1`) REFERENCES `$2` (`$3`)', $sql);
                    } elseif ($adapter->getType() === 'pgsql') {
                        $sql = str_replace('INTEGER PRIMARY KEY AUTOINCREMENT', 'SERIAL PRIMARY KEY', $sql);
                        $sql = str_replace('DATETIME', 'TIMESTAMP', $sql);
                        // Fix for JSON fields or text fields that might be interpreted wrong
                        // But mostly the issue is "Database Sync Error: ... invalid JSON" 
                        // It seems like the ERROR message itself is being parsed as JSON somewhere?
                        // Or maybe a previous query failed and returned an error string instead of JSON.

                        // Ensure table names are double quoted for Postgres to preserve case/reserved words if needed
                        // But standard Postgres is lowercase. Our schema uses lowercase keys.
                        // Let's just ensure we don't have lingering SQLite syntax
                        $sql = str_replace('INT DEFAULT 0', 'INTEGER DEFAULT 0', $sql);
                        $sql = str_replace('INT DEFAULT 1', 'INTEGER DEFAULT 1', $sql);

                        // Handle "Database S" error - likely the word "Database" appearing in an error 
                        // unexpectedly.

                        // IMPORTANT: The user error 'Unexpected token 'D', "Database S"... is not valid JSON'
                        // suggests that the RESPONSE from the server (which might be this die() message)
                        // is being parsed by the Javascript frontend which expects JSON.
                        // So we should NOT die() with plain text if it's an AJAX request, or we should format it.
                        // However, let's fix the SQL schema issues first.

                        // Postgres specific fixes if needed.
                        // e.g. "groups" is a reserved word in MySQL, but fine in Postgres if quoted.
                        // SQLite uses "groups".
                        $sql = str_replace('CREATE TABLE groups', 'CREATE TABLE "groups"', $sql);
                        $sql = str_replace('REFERENCES groups(id)', 'REFERENCES "groups"(id)', $sql);

                        // "users" is also reserved in Postgres
                        $sql = str_replace('CREATE TABLE users', 'CREATE TABLE "users"', $sql);
                        $sql = str_replace('from users', 'from "users"', $sql); // regex might be safer
                        // Fix for 'key' column which is reserved, but ONLY exact match
                        // The previous replace was capturing 'cache_key' -> 'cache_"key"' which is wrong.
                        // We use word boundaries or look for spaces.
                        $sql = preg_replace('/\bkey\s+TEXT\s+PRIMARY\s+KEY/i', '"key" TEXT PRIMARY KEY', $sql);

                        // Generic quoting for Postgres
                        $sql = preg_replace('/CREATE TABLE ([a-z_]+)/i', 'CREATE TABLE "$1"', $sql);
                        // Fix references
                        $sql = preg_replace('/REFERENCES ([a-z_]+)\(/i', 'REFERENCES "$1"(', $sql);
                    }

                    try {
                        $db->exec($sql);
                        error_log("Installer: Table [$tableName] created successfully.");
                    } catch (\Exception $e) {
                        error_log("Schema Creation Error for table [$tableName]: " . $e->getMessage());
                        throw new \Exception("Could not create table [$tableName]: " . $e->getMessage());
                    }
                } else {
                    // Synchronize columns for existing tables
                    // Now supports MySQL and Postgres too
                    self::syncColumns($db, $tableName, $definition['sql']);
                }
            }

            // 2. Data Initialization
            // This ensures missing default data (admin user, services, plans) is created once.
            // We check a flag in system_settings to avoid re-running seeds on every request.
            if (!Config::getSetting('system_seeded')) {
                error_log("Installer: Initializing default data...");
                self::seedDefaults($db);
                error_log("Installer: Data initialization completed.");
            }

            // 3. Maintenance / Dynamic checks
            self::runHealthChecks($db);

        } catch (\Throwable $e) {
            error_log("Database Sync Error: " . $e->getMessage());

            // In PostgreSQL, error details are often crucial
            if (Config::get('dev_mode')) {
                // If it fails during schema creation, it might be due to partial queries.
                // We don't want to stop execution if it's just a warning.
                if (stripos($e->getMessage(), 'already exists') === false) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => "Database Sync Error (See logs): " . $e->getMessage()
                    ]);
                    exit;
                }
            }
        }
    }

    /**
     * Compares table columns and adds missing ones via ALTER TABLE.
     */
    private static function syncColumns($db, $tableName, $createSql)
    {
        $adapter = Database::getInstance()->getAdapter();
        $type = $adapter->getType();

        // Extract column names from the SQL definition (Regex for SQLite CREATE TABLE content)
        preg_match_all('/(?:,|\()\s*([a-zA-Z0-9_]+)\s+([a-zA-Z0-9_]+)/i', $createSql, $matches);
        $expectedColumns = $matches[1];

        // 1. Get Current Columns
        $currentColumns = [];
        try {
            if ($type === 'sqlite') {
                $stmt = $db->query("PRAGMA table_info($tableName)");
                $currentColumns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
            } elseif ($type === 'mysql') {
                // MySQL requires backticks usually, but safe table name helps
                $stmt = $db->query("SHOW COLUMNS FROM `$tableName`");
                $currentColumns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0); // Field is first column
            } elseif ($type === 'pgsql') {
                // Postgres info schema
                $stmt = $db->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = ?");
                $stmt->execute([strtolower($tableName)]);
                $currentColumns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            }
        } catch (\Exception $e) {
            return; // Cannot inspect table, skip
        }

        foreach ($expectedColumns as $col) {
            // Ignore SQL keywords that might be caught
            if (in_array(strtoupper($col), ['PRIMARY', 'FOREIGN', 'CONSTRAINT', 'UNIQUE', 'CHECK', 'KEY']))
                continue;

            if (!in_array($col, $currentColumns)) {
                // Find definition in the original SQL
                if (preg_match('/(?:\(|\,)\s*' . $col . '\s+([^,)]+)/i', $createSql, $defMatch)) {
                    $colDef = trim($defMatch[1]);

                    // Only add if it's not a COMPLEX constraint
                    if (strpos(strtoupper($colDef), 'PRIMARY KEY') !== false)
                        continue;

                    // Adapt Column Type for Target DB
                    if ($type === 'mysql') {
                        $colDef = str_replace('AUTOINCREMENT', 'AUTO_INCREMENT', $colDef);
                        $colDef = str_replace('STRING', 'VARCHAR(255)', $colDef);
                        // Fix MySQL TEXT DEFAULT limitation
                        if (stripos($colDef, 'TEXT') !== false && stripos($colDef, 'DEFAULT') !== false) {
                            $colDef = str_replace('TEXT', 'VARCHAR(255)', $colDef);
                        }
                    } elseif ($type === 'pgsql') {
                        $colDef = str_replace('DATETIME', 'TIMESTAMP', $colDef);
                        $colDef = str_replace('INT DEFAULT', 'INTEGER DEFAULT', $colDef);
                    }

                    try {
                        $alterSql = "ALTER TABLE " . $adapter->quoteName($tableName) . " ADD COLUMN " . $adapter->quoteName($col) . " $colDef";
                        $db->exec($alterSql);
                        error_log("Installer: Added column [$col] to table [$tableName]");
                    } catch (\Exception $e) {
                        error_log("Installer: Failed to add column [$col] to [$tableName]: " . $e->getMessage());
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
        // Helper to get or create role
        $getOrCreateRole = function ($name, $desc, $perms) use ($db) {
            $stmt = $db->prepare("SELECT id FROM roles WHERE name = ?");
            $stmt->execute([$name]);
            $id = $stmt->fetchColumn();
            if (!$id) {
                $db->prepare("INSERT INTO roles (name, description, permissions) VALUES (?, ?, ?)")
                    ->execute([$name, $desc, $perms]);
                return $db->lastInsertId();
            }
            return $id;
        };

        // 1. Default Admin Role
        $adminPermissions = json_encode(['all' => true]);
        $adminRoleId = $getOrCreateRole('Administrator', 'Full system access', $adminPermissions);

        // 2. Project Director Role (Editor)
        $directorPermissions = json_encode([
            'all' => false,
            'modules' => [
                'projects' => ['view', 'create', 'edit', 'delete'],
                'media' => ['view', 'upload', 'delete'],
                'databases' => ['view', 'view_tables'],
                'billing' => ['view']
            ]
        ]);
        $directorRoleId = $getOrCreateRole('Director de Proyecto', 'Can manage projects and contents', $directorPermissions);

        // 3. Client Role
        $clientPermissions = json_encode([
            'all' => false,
            'modules' => [
                'projects' => ['view'],
                'billing' => ['view']
            ]
        ]);
        $clientRoleId = $getOrCreateRole('Cliente', 'Limited access to assigned projects', $clientPermissions);

        // 4. Default User Role (Empty permissions)
        $userPermissions = json_encode(['all' => false, 'modules' => []]);
        $userRoleId = $getOrCreateRole('Usuario', 'No access by default', $userPermissions);

        // --- USERS (IDEMPOTENT) ---

        $createSystemUser = function ($username, $password, $roleId, $publicName) use ($db) {
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $id = $stmt->fetchColumn();
            if (!$id) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $db->prepare("INSERT INTO users (username, password, role_id, public_name) VALUES (?, ?, ?, ?)")
                    ->execute([$username, $hash, $roleId, $publicName]);
                return $db->lastInsertId();
            }
            return $id;
        };

        // 1. Super Admin (admin / admin123)
        $adminUserId = $createSystemUser('admin', 'admin123', $adminRoleId, 'Super Admin');

        // 2. Editor (editor / director123) - Rol Director de Proyecto
        $editorUserId = $createSystemUser('editor', 'director123', $directorRoleId, 'Director Proyecto');

        // 3. Cliente (cliente / cliente123) - Rol Cliente
        $clientUserId = $createSystemUser('cliente', 'cliente123', $clientRoleId, 'Cliente Principal');

        // 4. Usuario (usuario / usuario123) - Rol Usuario
        $userUserId = $createSystemUser('usuario', 'usuario123', $userRoleId, 'Usuario Estándar');

        // --- DEFAULT PROJECT ---

        $stmt = $db->prepare("SELECT id FROM projects WHERE name = 'Data2Rest'");
        $stmt->execute();
        $defaultProjectId = $stmt->fetchColumn();

        if (!$defaultProjectId) {
            $db->prepare("INSERT INTO projects (name, description, status, storage_quota, client_id) VALUES ('Data2Rest', 'Proyecto base por defecto', 'active', 500, ?)")
                ->execute([$clientUserId]);
            $defaultProjectId = $db->lastInsertId();
        }

        // Assign users to default project
        // Admin gets access via 'all' permission, but explicit assignment is good for filtering
        // Assign users to default project (Idempotent)
        // Admin gets access via 'all' permission, but explicit assignment is good for filtering
        $assignStmt = $db->prepare("INSERT INTO project_users (project_id, user_id, permissions) VALUES (?, ?, ?)");
        $checkAssign = $db->prepare("SELECT COUNT(*) FROM project_users WHERE project_id = ? AND user_id = ?");

        $fullAccess = json_encode(['all' => true]);

        $assignments = [
            [$adminUserId, $fullAccess],
            [$editorUserId, $fullAccess],
            [$clientUserId, json_encode(['view_only' => true])]
        ];

        foreach ($assignments as $assign) {
            $checkAssign->execute([$defaultProjectId, $assign[0]]);
            if ($checkAssign->fetchColumn() == 0) {
                $assignStmt->execute([$defaultProjectId, $assign[0], $assign[1]]);
            }
        }

        // Default Settings
        $settings = [
            'dev_mode' => 'off',
            'media_trash_retention' => '30',
            'app_language' => 'es',
            'show_welcome_banner' => '1',
            'time_offset_total' => '0',
            'media_optimize_max_dimension' => '1080',
            'media_optimize_priority' => 'webp',
            'media_optimize_quality' => '85'
        ];

        // Use key_name
        $stmt = $db->prepare("INSERT INTO system_settings (key_name, value) VALUES (?, ?)");

        // Only insert settings if they don't exist
        foreach ($settings as $k => $v) {
            $check = $db->prepare("SELECT COUNT(*) FROM system_settings WHERE key_name = ?");
            $check->execute([$k]);
            if ($check->fetchColumn() == 0) {
                $stmt->execute([$k, $v]);
            }
        }

        // Default Payment Plans (Check existence to avoid duplicates)
        $plans = [
            ['Plan Mensual', 'monthly', 12, 'Plan de pago mensual con 12 cuotas'],
            ['Plan Anual', 'yearly', 1, 'Plan de pago anual con 1 cuota']
        ];
        foreach ($plans as $plan) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM payment_plans WHERE name = ?");
            $stmt->execute([$plan[0]]);
            if ($stmt->fetchColumn() == 0) {
                $db->prepare("INSERT INTO payment_plans (name, frequency, installments, description) VALUES (?, ?, ?, ?)")
                    ->execute($plan);
            }
        }

        // Default Task Statuses for Kanban Board
        $taskStatuses = [
            ['name' => 'Solicitud', 'slug' => 'backlog', 'color' => '#94a3b8', 'position' => 1],
            ['name' => 'En Desarrollo', 'slug' => 'in_progress', 'color' => '#3b82f6', 'position' => 2],
            ['name' => 'En Revisión', 'slug' => 'review', 'color' => '#f59e0b', 'position' => 3],
            ['name' => 'Validación Cliente', 'slug' => 'client_validation', 'color' => '#8b5cf6', 'position' => 4],
            ['name' => 'Finalizado', 'slug' => 'done', 'color' => '#10b981', 'position' => 5]
        ];

        // This is safe to run only if table is empty or check individual, doing bulk check
        $stmtStatusCheck = $db->query("SELECT COUNT(*) FROM task_statuses");
        if ($stmtStatusCheck->fetchColumn() == 0) {
            $stmt = $db->prepare("INSERT INTO task_statuses (name, slug, color, position) VALUES (?, ?, ?, ?)");
            foreach ($taskStatuses as $status) {
                $stmt->execute([$status['name'], $status['slug'], $status['color'], $status['position']]);
            }
        }

        // Default Services (as per requirements) and their templates
        $services = [
            'Web Básica' => [
                ['title' => 'Recolectar logo y textos', 'priority' => 'high'],
                ['title' => 'Configurar subdominio', 'priority' => 'high'],
                ['title' => 'Carga inicial', 'priority' => 'medium'],
                ['title' => 'Carga 5 productos', 'priority' => 'medium'],
                ['title' => 'Botón WhatsApp', 'priority' => 'medium'],
                ['title' => 'Control de Calidad (QA)', 'priority' => 'high'],
                ['title' => 'Entrega final', 'priority' => 'high']
            ],
            'Dominio Propio' => [
                ['title' => 'Búsqueda de disponibilidad', 'priority' => 'high'],
                ['title' => 'Registro de dominio', 'priority' => 'high'],
                ['title' => 'Configuración DNS', 'priority' => 'high'],
                ['title' => 'Instalación SSL', 'priority' => 'high']
            ],
            'ChatBot IA' => [
                ['title' => 'Definir base de conocimientos', 'priority' => 'high'],
                ['title' => 'Configuración del Prompt IA', 'priority' => 'high'],
                ['title' => 'Integración en web', 'priority' => 'medium'],
                ['title' => 'Pruebas de respuesta', 'priority' => 'medium']
            ],
            'Catálogo Expandido' => [
                ['title' => 'Ampliar estructura de BD', 'priority' => 'high'],
                ['title' => 'Recibir inventario completo', 'priority' => 'medium'],
                ['title' => 'Carga masiva de productos', 'priority' => 'medium'],
                ['title' => 'Categorización avanzada', 'priority' => 'low']
            ],
            'Correos Corporativos' => [
                ['title' => 'Crear cuentas de correo', 'priority' => 'high'],
                ['title' => 'Configurar registros MX', 'priority' => 'high'],
                ['title' => 'Enviar credenciales al cliente', 'priority' => 'medium'],
                ['title' => 'Enviar manual Outlook/Gmail', 'priority' => 'low']
            ],
            'Gestor de Citas' => [
                ['title' => 'Configurar horarios de atención', 'priority' => 'high'],
                ['title' => 'Definir servicios reservables', 'priority' => 'high'],
                ['title' => 'Configurar avisos Correo/WA', 'priority' => 'medium'],
                ['title' => 'Instalar widget de calendario', 'priority' => 'medium']
            ]
        ];

        // Insert Services and Templates
        foreach ($services as $name => $templates) {
            // Check if service exists
            $stmt = $db->prepare("SELECT id FROM billing_services WHERE name = ?");
            $stmt->execute([$name]);
            $existingId = $stmt->fetchColumn();

            if (!$existingId) {
                // Insert Service
                $stmt = $db->prepare("INSERT INTO billing_services (name, status) VALUES (?, 'active')");
                $stmt->execute([$name]);
                $serviceId = $db->lastInsertId();
            } else {
                $serviceId = $existingId;
            }

            // Insert Templates (check duplicates)
            foreach ($templates as $tmpl) {
                $check = $db->prepare("SELECT COUNT(*) FROM billing_service_templates WHERE service_id = ? AND title = ?");
                $check->execute([$serviceId, $tmpl['title']]);
                if ($check->fetchColumn() == 0) {
                    $db->prepare("INSERT INTO billing_service_templates (service_id, title, priority) VALUES (?, ?, ?)")
                        ->execute([$serviceId, $tmpl['title'], $tmpl['priority']]);
                }
            }
        }

        // Pack Todo en Uno (Clone A, B, E)
        $packName = 'Pack "Todo en Uno"';
        $stmt = $db->prepare("SELECT id FROM billing_services WHERE name = ?");
        $stmt->execute([$packName]);
        $packId = $stmt->fetchColumn();

        if (!$packId) {
            $stmt = $db->prepare("INSERT INTO billing_services (name, status) VALUES (?, 'active')");
            $stmt->execute([$packName]);
            $packId = $db->lastInsertId();

            // Clone templates from Web Básica, Dominio Propio, Correos Corporativos
            $db->exec("INSERT INTO billing_service_templates (service_id, title, priority)
                       SELECT $packId, title, priority FROM billing_service_templates 
                       WHERE service_id IN (
                           SELECT id FROM billing_services WHERE name IN ('Web Básica', 'Dominio Propio', 'Correos Corporativos')
                       )");
        }

        // Mark as seeded to avoid re-running on every request
        // Mark as seeded to avoid re-running on every request
        // Try to update or insert the flag using key_name
        $update = $db->prepare("UPDATE system_settings SET value = '1' WHERE key_name = 'system_seeded'");
        $update->execute();

        if ($update->rowCount() == 0) {
            $insert = $db->prepare("INSERT INTO system_settings (key_name, value) VALUES ('system_seeded', '1')");
            $insert->execute();
        }
    }

    /**
     * Miscellaneous logic for database health and backward compatibility.
     */
    private static function runHealthChecks($db)
    {
        // Ensure at least one project exists if there are databases
        // Use adapter to quote correctly for both MySQL (`) and Postgres (")
        $adapter = Database::getInstance()->getAdapter();
        $qDatabases = $adapter->quoteName('databases');

        try {
            $orphans = $db->query("SELECT COUNT(*) FROM $qDatabases WHERE project_id IS NULL")->fetchColumn();
            if ($orphans > 0) {
                $projectCheck = $db->query("SELECT id FROM projects LIMIT 1")->fetch();
                if (!$projectCheck) {
                    $db->exec("INSERT INTO projects (name, description) VALUES ('Default Project', 'System generated')");
                    $projectId = $db->lastInsertId();
                } else {
                    $projectId = $projectCheck['id'];
                }
                $db->prepare("UPDATE $qDatabases SET project_id = ? WHERE project_id IS NULL")->execute([$projectId]);
            }
        } catch (\PDOException $e) {
            // Ignore table not found errors during early install
        }

        // Migration: key -> key_name
        try {
            $cols = [];
            $adapter = Database::getInstance()->getAdapter();
            $type = $adapter->getType();

            if ($type === 'sqlite') {
                $stmt = $db->query("PRAGMA table_info(system_settings)");
                $cols = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
            } elseif ($type === 'mysql') {
                $stmt = $db->query("SHOW COLUMNS FROM system_settings");
                $cols = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            }

            // If 'key' exists but 'key_name' does not, rename it
            if (in_array('key', $cols) && !in_array('key_name', $cols)) {
                error_log("Installer: Migrating system_settings column 'key' to 'key_name'...");
                if ($type === 'sqlite') {
                    $db->exec("ALTER TABLE system_settings RENAME COLUMN key TO key_name");
                } elseif ($type === 'mysql') {
                    // MySQL requires full definition
                    $db->exec("ALTER TABLE system_settings CHANGE `key` `key_name` VARCHAR(255) PRIMARY KEY");
                } elseif ($type === 'pgsql') {
                    $db->exec("ALTER TABLE system_settings RENAME COLUMN \"key\" TO key_name");
                }
            }
        } catch (\Exception $e) {
            error_log("Migration warning: " . $e->getMessage());
        }

        // Final sanity check for settings
        $defaults = [
            'media_trash_retention' => '30',
            'time_offset_total' => '0',
            'media_optimize_max_dimension' => '1080',
            'media_optimize_priority' => 'webp',
            'media_optimize_quality' => '85',
            'start_week_on' => '1' // Monday
        ];

        $checkStmt = $db->prepare("SELECT 1 FROM system_settings WHERE key_name = ?");
        $insertStmt = $db->prepare("INSERT INTO system_settings (key_name, value) VALUES (?, ?)");

        foreach ($defaults as $key => $val) {
            $checkStmt->execute([$key]);
            if (!$checkStmt->fetch()) {
                $insertStmt->execute([$key, $val]);
            }
        }
    }
}
