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
                status INTEGER DEFAULT 1
                , project_id INTEGER, user_id INTEGER)"
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
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                service_id INTEGER REFERENCES billing_services(id),
                FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE CASCADE,
                FOREIGN KEY(status_id) REFERENCES task_statuses(id),
                FOREIGN KEY(assigned_to) REFERENCES users(id),
                FOREIGN KEY(created_by) REFERENCES users(id)
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
                        $sql = str_replace('key TEXT PRIMARY KEY', '"key" TEXT PRIMARY KEY', $sql);

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
                    // Only for SQLite for now as regex is fragile
                    if ($adapter->getType() === 'sqlite') {
                        self::syncColumns($db, $tableName, $definition['sql']);
                    }
                }
            }

            // 2. Data Initialization (Only if it was new)
            if ($isNew) {
                error_log("Installer: Seeding default data...");
                self::seedDefaults($db);
                error_log("Installer: Seeding completed.");
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
        // 1. Default Admin Role
        $adminPermissions = json_encode(['all' => true]);
        $stmt = $db->prepare("INSERT INTO roles (name, description, permissions) VALUES ('Administrator', 'Full system access', ?)");
        $stmt->execute([$adminPermissions]);
        $adminRoleId = $db->lastInsertId();

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
        $db->prepare("INSERT INTO roles (name, description, permissions) VALUES ('Director de Proyecto', 'Can manage projects and contents', ?)")
            ->execute([$directorPermissions]);
        $directorRoleId = $db->lastInsertId();

        // 3. Client Role
        $clientPermissions = json_encode([
            'all' => false,
            'modules' => [
                'projects' => ['view'],
                'billing' => ['view']
            ]
        ]);
        $db->prepare("INSERT INTO roles (name, description, permissions) VALUES ('Cliente', 'Limited access to assigned projects', ?)")
            ->execute([$clientPermissions]);
        $clientRoleId = $db->lastInsertId();

        // 4. Default User Role (Empty permissions)
        $userPermissions = json_encode(['all' => false, 'modules' => []]);
        $db->prepare("INSERT INTO roles (name, description, permissions) VALUES ('Usuario', 'No access by default', ?)")
            ->execute([$userPermissions]);
        $userRoleId = $db->lastInsertId();

        // --- USERS ---

        // 1. Super Admin (admin / admin123)
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO users (username, password, role_id, public_name) VALUES ('admin', ?, ?, 'Super Admin')")
            ->execute([$password, $adminRoleId]);
        $adminUserId = $db->lastInsertId();

        // 2. Editor (editor / director123) - Rol Director de Proyecto
        $directorPass = password_hash('director123', PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO users (username, password, role_id, public_name) VALUES ('editor', ?, ?, 'Director Proyecto')")
            ->execute([$directorPass, $directorRoleId]);
        $editorUserId = $db->lastInsertId();

        // 3. Cliente (cliente / cliente123) - Rol Cliente
        $clientPass = password_hash('cliente123', PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO users (username, password, role_id, public_name) VALUES ('cliente', ?, ?, 'Cliente Principal')")
            ->execute([$clientPass, $clientRoleId]);
        $clientUserId = $db->lastInsertId();

        // 4. Usuario (usuario / usuario123) - Rol Usuario
        $userPass = password_hash('usuario123', PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO users (username, password, role_id, public_name) VALUES ('usuario', ?, ?, 'Usuario Estándar')")
            ->execute([$userPass, $userRoleId]);

        // --- DEFAULT PROJECT ---

        $db->prepare("INSERT INTO projects (name, description, status, storage_quota, client_id) VALUES ('Data2Rest', 'Proyecto base por defecto', 'active', 500, ?)")
            ->execute([$clientUserId]);
        $defaultProjectId = $db->lastInsertId();

        // Assign users to default project
        // Admin gets access via 'all' permission, but explicit assignment is good for filtering
        $assignStmt = $db->prepare("INSERT INTO project_users (project_id, user_id, permissions) VALUES (?, ?, ?)");
        $fullAccess = json_encode(['all' => true]);

        $assignStmt->execute([$defaultProjectId, $adminUserId, $fullAccess]);
        $assignStmt->execute([$defaultProjectId, $editorUserId, $fullAccess]); // Director has full project access
        $assignStmt->execute([$defaultProjectId, $clientUserId, json_encode(['view_only' => true])]); // Client typically read-only or limited logic

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
        // Ensure quotes via adapter or explicit double quotes for Postgres
        $stmt = $db->prepare("INSERT INTO system_settings (" . Database::getInstance()->getAdapter()->quoteName('key') . ", value) VALUES (?, ?)");

        // Only insert settings if they don't exist
        foreach ($settings as $k => $v) {
            $check = $db->prepare("SELECT COUNT(*) FROM system_settings WHERE " . Database::getInstance()->getAdapter()->quoteName('key') . " = ?");
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

        // Final sanity check for settings
        // Use portable check-then-insert instead of SQLite 'INSERT OR IGNORE'
        $defaults = [
            'media_trash_retention' => '30',
            'time_offset_total' => '0',
            'media_optimize_max_dimension' => '1080',
            'media_optimize_priority' => 'webp',
            'media_optimize_quality' => '85',
            'start_week_on' => '1' // Monday
        ];

        $qKey = $adapter->quoteName('key');
        $checkStmt = $db->prepare("SELECT 1 FROM system_settings WHERE $qKey = ?");
        $insertStmt = $db->prepare("INSERT INTO system_settings ($qKey, value) VALUES (?, ?)");

        foreach ($defaults as $key => $val) {
            $checkStmt->execute([$key]);
            if (!$checkStmt->fetch()) {
                $insertStmt->execute([$key, $val]);
            }
        }
    }
}
