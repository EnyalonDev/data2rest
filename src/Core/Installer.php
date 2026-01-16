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
                $check = $db->query($adapter->getTableExistsSQL('users'));
                $isNew = ($check->fetchColumn() === false);
                // IF table exists check returns row count or name?
                // Adapter::getTableExistsSQL returns a SELECT query. fetch() should return row or false.
                $check->closeCursor();
            }

            // 1. Ensure all tables exist
            foreach (self::$SCHEMA as $tableName => $definition) {
                // Use Adapter to check existence
                $existsSql = $adapter->getTableExistsSQL($tableName);
                $exists = $db->query($existsSql)->fetch();

                if (!$exists) {
                    // Create Table
                    // NOTE: The $SCHEMA SQL is SQLite tailored (AUTOINCREMENT, etc.)
                    // Ideally we should use $adapter->createTable() but our schema is complex.
                    // For MVP, we try to run the SQL. MySQL might accept most if we replace AUTOINCREMENT
                    $sql = $definition['sql'];

                    if ($adapter->getType() === 'mysql') {
                        $sql = str_replace('AUTOINCREMENT', 'AUTO_INCREMENT', $sql);
                        $sql = str_replace('STRING', 'VARCHAR(255)', $sql);
                        // Fix SQLite specific syntax if any
                    } elseif ($adapter->getType() === 'pgsql') {
                        $sql = str_replace('INTEGER PRIMARY KEY AUTOINCREMENT', 'SERIAL PRIMARY KEY', $sql);
                        $sql = str_replace('DATETIME', 'TIMESTAMP', $sql);
                    }

                    try {
                        $db->exec($sql);
                    } catch (\Exception $e) {
                        // Fallback or log. 
                        // For detailed migration, we'd need a Builder.
                        // For now, let's assume the user uses SQLite or we fix SQL on fly.
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
            'time_offset_total' => '0',
            'media_optimize_max_dimension' => '1080',
            'media_optimize_priority' => 'webp',
            'media_optimize_quality' => '85'
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

        // Default Task Statuses for Kanban Board
        $taskStatuses = [
            ['name' => 'Solicitud', 'slug' => 'backlog', 'color' => '#94a3b8', 'position' => 1],
            ['name' => 'En Desarrollo', 'slug' => 'in_progress', 'color' => '#3b82f6', 'position' => 2],
            ['name' => 'En Revisión', 'slug' => 'review', 'color' => '#f59e0b', 'position' => 3],
            ['name' => 'Validación Cliente', 'slug' => 'client_validation', 'color' => '#8b5cf6', 'position' => 4],
            ['name' => 'Finalizado', 'slug' => 'done', 'color' => '#10b981', 'position' => 5]
        ];
        $stmt = $db->prepare("INSERT INTO task_statuses (name, slug, color, position) VALUES (?, ?, ?, ?)");
        foreach ($taskStatuses as $status) {
            $stmt->execute([$status['name'], $status['slug'], $status['color'], $status['position']]);
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
            $stmt = $db->prepare("INSERT INTO billing_services (name, status) VALUES (?, 'active')");
            $stmt->execute([$name]);
            $serviceId = $db->lastInsertId();

            $tplStmt = $db->prepare("INSERT INTO billing_service_templates (service_id, title, priority) VALUES (?, ?, ?)");
            foreach ($templates as $t) {
                $tplStmt->execute([$serviceId, $t['title'], $t['priority']]);
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
        $db->exec("INSERT OR IGNORE INTO system_settings (key, value) VALUES ('media_optimize_max_dimension', '1080')");
        $db->exec("INSERT OR IGNORE INTO system_settings (key, value) VALUES ('media_optimize_priority', 'webp')");
        $db->exec("INSERT OR IGNORE INTO system_settings (key, value) VALUES ('media_optimize_quality', '85')");
    }
}
