<?php
/**
 * Script de Migración: SQLite → MySQL
 * 
 * Migra los datos del system.db (SQLite) actual a una nueva
 * instalación con MySQL como base de datos del sistema.
 * 
 * Uso: php scripts/migrate_sqlite_to_mysql.php /ruta/a/system.db
 */

if ($argc < 2) {
    echo "❌ Error: Debes especificar la ruta al archivo system.db de SQLite\n";
    echo "Uso: php scripts/migrate_sqlite_to_mysql.php /ruta/a/system.db\n";
    exit(1);
}

$sqliteDbPath = $argv[1];

if (!file_exists($sqliteDbPath)) {
    echo "❌ Error: Archivo no encontrado: $sqliteDbPath\n";
    exit(1);
}

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║   Data2Rest - Migración SQLite → MySQL                    ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

require_once __DIR__ . '/../src/autoload.php';

use App\Core\Database;
use App\Core\Config;

try {
    // Cargar configuración de la nueva instalación (MySQL)
    Config::loadEnv();
    $mysqlDb = Database::getInstance()->getConnection();

    echo "✓ Conectado a MySQL (nueva instalación)\n";

    // Conectar a SQLite (instalación antigua)
    $sqliteDb = new PDO("sqlite:$sqliteDbPath");
    $sqliteDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✓ Conectado a SQLite (instalación antigua)\n\n";

    // Mapeo de IDs antiguos a nuevos
    $projectIdMap = [];
    $userIdMap = [];
    $dbIdMap = [];

    // ============================================================
    // 1. MIGRAR PROYECTOS
    // ============================================================

    echo "📦 Migrando proyectos...\n";

    $stmt = $sqliteDb->query("SELECT * FROM projects WHERE status = 'active'");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $insertStmt = $mysqlDb->prepare("INSERT INTO projects 
        (name, description, status, storage_quota, client_id, start_date, 
         google_client_id, google_client_secret, domain, allowed_origins, 
         external_auth_enabled, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($projects as $project) {
        $oldId = $project['id'];

        try {
            $insertStmt->execute([
                $project['name'],
                $project['description'] ?? null,
                $project['status'] ?? 'active',
                $project['storage_quota'] ?? 300,
                null, // client_id se asignará después
                $project['start_date'] ?? null,
                $project['google_client_id'] ?? null,
                $project['google_client_secret'] ?? null,
                $project['domain'] ?? null,
                $project['allowed_origins'] ?? null,
                $project['external_auth_enabled'] ?? 0,
                $project['created_at'] ?? date('Y-m-d H:i:s'),
                $project['updated_at'] ?? date('Y-m-d H:i:s')
            ]);

            $newId = $mysqlDb->lastInsertId();
            $projectIdMap[$oldId] = $newId;
            echo "   ✓ '{$project['name']}' (ID: $oldId → $newId)\n";
        } catch (Exception $e) {
            echo "   ⚠ Error en '{$project['name']}': " . $e->getMessage() . "\n";
        }
    }

    // ============================================================
    // 2. MIGRAR USUARIOS CLIENTES
    // ============================================================

    echo "\n📦 Migrando usuarios clientes...\n";

    // Obtener el ID del rol "Usuario"
    $roleStmt = $mysqlDb->prepare("SELECT id FROM roles WHERE name = 'Usuario' LIMIT 1");
    $roleStmt->execute();
    $userRoleId = $roleStmt->fetchColumn();

    if (!$userRoleId) {
        echo "   ⚠ Rol 'Usuario' no encontrado, usando NULL\n";
        $userRoleId = null;
    }

    $stmt = $sqliteDb->query("SELECT * FROM users WHERE role_id >= 3 OR role_id IS NULL");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $insertStmt = $mysqlDb->prepare("INSERT INTO users 
        (username, email, role_id, status, public_name, phone, address, tax_id, google_id, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($users as $user) {
        $oldId = $user['id'];

        // Verificar si ya existe
        $checkStmt = $mysqlDb->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $checkStmt->execute([$user['email'], $user['username']]);
        $existingId = $checkStmt->fetchColumn();

        if ($existingId) {
            $userIdMap[$oldId] = $existingId;
            echo "   ⚠ '{$user['username']}' ya existe (ID: $oldId → $existingId)\n";
            continue;
        }

        try {
            $insertStmt->execute([
                $user['username'],
                $user['email'],
                $userRoleId, // Usar rol "Usuario" por defecto
                $user['status'] ?? 1,
                $user['public_name'] ?? null,
                $user['phone'] ?? null,
                $user['address'] ?? null,
                $user['tax_id'] ?? null,
                $user['google_id'] ?? null,
                $user['created_at'] ?? date('Y-m-d H:i:s')
            ]);

            $newId = $mysqlDb->lastInsertId();
            $userIdMap[$oldId] = $newId;
            echo "   ✓ '{$user['username']}' (ID: $oldId → $newId)\n";
        } catch (Exception $e) {
            echo "   ⚠ Error en '{$user['username']}': " . $e->getMessage() . "\n";
        }
    }

    // ============================================================
    // 3. MIGRAR CONFIGURACIONES DE BASES DE DATOS
    // ============================================================

    echo "\n📦 Migrando configuraciones de bases de datos...\n";

    $stmt = $sqliteDb->query("SELECT * FROM databases");
    $databases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $insertStmt = $mysqlDb->prepare("INSERT INTO `databases` 
        (name, path, type, project_id, config, created_at, last_edit_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");

    foreach ($databases as $database) {
        $oldId = $database['id'];
        $oldProjectId = $database['project_id'] ?? null;
        $newProjectId = $oldProjectId ? ($projectIdMap[$oldProjectId] ?? null) : null;

        try {
            $insertStmt->execute([
                $database['name'],
                $database['path'],
                $database['type'] ?? 'sqlite',
                $newProjectId,
                $database['config'] ?? null,
                $database['created_at'] ?? date('Y-m-d H:i:s'),
                $database['last_edit_at'] ?? null
            ]);

            $newId = $mysqlDb->lastInsertId();
            $dbIdMap[$oldId] = $newId;
            echo "   ✓ '{$database['name']}' (ID: $oldId → $newId)\n";
        } catch (Exception $e) {
            echo "   ⚠ Error en '{$database['name']}': " . $e->getMessage() . "\n";
        }
    }

    // ============================================================
    // 4. MIGRAR RELACIONES PROYECTO-USUARIO
    // ============================================================

    echo "\n📦 Migrando relaciones proyecto-usuario...\n";

    $stmt = $sqliteDb->query("SELECT * FROM project_users");
    $relations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $insertStmt = $mysqlDb->prepare("INSERT INTO project_users 
        (project_id, user_id, permissions, external_permissions, external_access_enabled, assigned_at) 
        VALUES (?, ?, ?, ?, ?, ?)");

    $count = 0;
    foreach ($relations as $relation) {
        $oldProjectId = $relation['project_id'];
        $oldUserId = $relation['user_id'];

        $newProjectId = $projectIdMap[$oldProjectId] ?? null;
        $newUserId = $userIdMap[$oldUserId] ?? null;

        if (!$newProjectId || !$newUserId) {
            echo "   ⚠ Relación omitida (proyecto o usuario no encontrado)\n";
            continue;
        }

        try {
            $insertStmt->execute([
                $newProjectId,
                $newUserId,
                $relation['permissions'] ?? null,
                $relation['external_permissions'] ?? null,
                $relation['external_access_enabled'] ?? 1,
                $relation['assigned_at'] ?? date('Y-m-d H:i:s')
            ]);
            $count++;
        } catch (Exception $e) {
            echo "   ⚠ Error: " . $e->getMessage() . "\n";
        }
    }
    echo "   ✓ $count relaciones migradas\n";

    // ============================================================
    // 5. ACTUALIZAR CLIENT_ID EN PROYECTOS
    // ============================================================

    echo "\n📦 Actualizando client_id en proyectos...\n";

    foreach ($projects as $project) {
        $oldClientId = $project['client_id'] ?? null;
        if ($oldClientId && isset($userIdMap[$oldClientId])) {
            $newProjectId = $projectIdMap[$project['id']];
            $newClientId = $userIdMap[$oldClientId];

            $mysqlDb->prepare("UPDATE projects SET client_id = ? WHERE id = ?")
                ->execute([$newClientId, $newProjectId]);

            echo "   ✓ Proyecto ID $newProjectId → Cliente ID $newClientId\n";
        }
    }

    // ============================================================
    // RESUMEN
    // ============================================================

    echo "\n╔════════════════════════════════════════════════════════════╗\n";
    echo "║   ✅ MIGRACIÓN COMPLETADA EXITOSAMENTE                     ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";

    echo "═══════════════════════════════════════════════════════════\n";
    echo "  RESUMEN:\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "Proyectos migrados:     " . count($projectIdMap) . "\n";
    echo "Usuarios migrados:      " . count($userIdMap) . "\n";
    echo "Bases de datos:         " . count($dbIdMap) . "\n";
    echo "Relaciones P-U:         $count\n";
    echo "\n";

    echo "═══════════════════════════════════════════════════════════\n";
    echo "  PRÓXIMOS PASOS:\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "1. Copiar archivos .db de clientes al directorio data/\n";
    echo "2. Copiar archivos media al directorio uploads/\n";
    echo "3. Configurar permisos: chmod -R 777 data uploads\n";
    echo "4. Verificar acceso a proyectos y bases de datos\n";
    echo "5. Configurar Google OAuth si es necesario\n\n";

} catch (Exception $e) {
    echo "\n❌ Error durante la migración:\n";
    echo $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
