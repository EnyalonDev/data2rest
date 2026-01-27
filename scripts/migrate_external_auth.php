<?php
/**
 * Migración: Autenticación Externa con Google OAuth
 * 
 * Este script extiende las tablas existentes de Data2Rest para soportar
 * autenticación externa en sitios web.
 */

require_once __DIR__ . '/../src/autoload.php';

use App\Core\Database;

echo "=== Migración: Autenticación Externa ===\n\n";

$db = Database::getInstance()->getConnection();

try {
    $db->beginTransaction();

    // 1. Extender tabla projects
    echo "1. Extendiendo tabla 'projects'...\n";

    $projectColumns = [
        'google_client_id' => 'VARCHAR(255)',
        'google_client_secret' => 'VARCHAR(255)',
        'domain' => 'VARCHAR(255)',
        'allowed_origins' => 'TEXT',
        'external_auth_enabled' => 'INTEGER DEFAULT 0'
    ];

    foreach ($projectColumns as $col => $type) {
        try {
            // Verificar si la columna ya existe (SQLite)
            $stmt = $db->query("PRAGMA table_info(projects)");
            $cols = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);

            if (!in_array($col, $cols)) {
                $db->exec("ALTER TABLE projects ADD COLUMN $col $type");
                echo "  ✓ Columna '$col' agregada\n";
            } else {
                echo "  - Columna '$col' ya existe\n";
            }
        } catch (Exception $e) {
            echo "  ✗ Error en '$col': " . $e->getMessage() . "\n";
        }
    }

    // 2. Extender tabla project_users
    echo "\n2. Extendiendo tabla 'project_users'...\n";

    $projectUserColumns = [
        'external_permissions' => 'TEXT',
        'external_access_enabled' => 'INTEGER DEFAULT 1'
    ];

    foreach ($projectUserColumns as $col => $type) {
        try {
            $stmt = $db->query("PRAGMA table_info(project_users)");
            $cols = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);

            if (!in_array($col, $cols)) {
                $db->exec("ALTER TABLE project_users ADD COLUMN $col $type");
                echo "  ✓ Columna '$col' agregada\n";
            } else {
                echo "  - Columna '$col' ya existe\n";
            }
        } catch (Exception $e) {
            echo "  ✗ Error en '$col': " . $e->getMessage() . "\n";
        }
    }

    // 3. Asegurar columna google_id en users
    echo "\n3. Verificando columna 'google_id' en 'users'...\n";

    try {
        $stmt = $db->query("PRAGMA table_info(users)");
        $cols = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);

        if (!in_array('google_id', $cols)) {
            $db->exec("ALTER TABLE users ADD COLUMN google_id TEXT");
            echo "  ✓ Columna 'google_id' agregada\n";
        } else {
            echo "  - Columna 'google_id' ya existe\n";
        }
    } catch (Exception $e) {
        echo "  ✗ Error: " . $e->getMessage() . "\n";
    }

    // 4. Crear tabla project_sessions
    echo "\n4. Creando tabla 'project_sessions'...\n";

    try {
        // Verificar si la tabla ya existe
        $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='project_sessions'");
        $exists = $stmt->fetch();

        if (!$exists) {
            $db->exec("
                CREATE TABLE project_sessions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    project_id INTEGER NOT NULL,
                    user_id INTEGER NOT NULL,
                    token VARCHAR(512) NOT NULL UNIQUE,
                    expires_at DATETIME NOT NULL,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ");
            echo "  ✓ Tabla 'project_sessions' creada\n";
        } else {
            echo "  - Tabla 'project_sessions' ya existe\n";
        }
    } catch (Exception $e) {
        echo "  ✗ Error: " . $e->getMessage() . "\n";
    }

    // 5. Agregar índices para rendimiento
    echo "\n5. Creando índices...\n";

    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_project_sessions_token ON project_sessions(token)",
        "CREATE INDEX IF NOT EXISTS idx_project_sessions_expires ON project_sessions(expires_at)",
        "CREATE INDEX IF NOT EXISTS idx_users_google_id ON users(google_id)",
        "CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)"
    ];

    foreach ($indexes as $indexSql) {
        try {
            $db->exec($indexSql);
            echo "  ✓ Índice creado\n";
        } catch (Exception $e) {
            echo "  - Índice ya existe o error: " . $e->getMessage() . "\n";
        }
    }

    // 6. Configurando system_settings
    echo "\n6. Configurando system_settings...\n";

    // 6.1 Asegurar columna 'description'
    try {
        $stmt = $db->query("PRAGMA table_info(system_settings)");
        $cols = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('description', $cols)) {
            $db->exec("ALTER TABLE system_settings ADD COLUMN description TEXT");
            echo "  ✓ Columna 'description' agregada a system_settings\n";
        }
    } catch (Exception $e) {
        echo "  - Error verificando description: " . $e->getMessage() . "\n";
    }

    // 6.2 Insertar valores defaults
    $settings = [
        ['jwt_secret', bin2hex(random_bytes(32)), 'Clave secreta para firmar tokens JWT'],
        ['jwt_expiration', '86400', 'Tiempo de expiración de tokens en segundos (24h)'],
        ['external_auth_enabled', '1', 'Habilitar autenticación externa para sitios web']
    ];

    foreach ($settings as [$key, $value, $description]) {
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM system_settings WHERE key_name = ?");
            $stmt->execute([$key]);

            if ($stmt->fetchColumn() == 0) {
                $insertStmt = $db->prepare("
                    INSERT INTO system_settings (key_name, value, description, created_at)
                    VALUES (?, ?, ?, datetime('now'))
                ");
                $insertStmt->execute([$key, $value, $description]);
                echo "  ✓ Configuración '$key' agregada\n";
            } else {
                echo "  - Configuración '$key' ya existe\n";
            }
        } catch (Exception $e) {
            echo "  ✗ Error en '$key': " . $e->getMessage() . "\n";
        }
    }

    $db->commit();

    echo "\n=== ✅ Migración completada exitosamente ===\n";
    echo "\nPróximos pasos:\n";
    echo "1. Configurar un proyecto para autenticación externa\n";
    echo "2. Asignar usuarios al proyecto con permisos externos\n";
    echo "3. Configurar Google OAuth en Google Cloud Console\n";

} catch (Exception $e) {
    $db->rollBack();
    echo "\n=== ❌ Error en la migración ===\n";
    echo $e->getMessage() . "\n";
    exit(1);
}
