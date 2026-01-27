<?php
/**
 * Script de Importación para Migración Limpia
 * 
 * Importa datos exportados desde una instalación anterior
 * a una instalación limpia de Data2Rest.
 * 
 * Uso: php scripts/import_from_migration.php /ruta/a/migration_data.json
 */

require_once __DIR__ . '/../src/autoload.php';

use App\Core\Database;
use App\Core\Config;

echo "=== Data2Rest - Importación desde Migración ===\n\n";

// Verificar argumento
if ($argc < 2) {
    echo "❌ Error: Debes especificar el archivo JSON de migración\n";
    echo "Uso: php scripts/import_from_migration.php /ruta/a/migration_data.json\n";
    exit(1);
}

$jsonFile = $argv[1];

if (!file_exists($jsonFile)) {
    echo "❌ Error: Archivo no encontrado: $jsonFile\n";
    exit(1);
}

try {
    Config::loadEnv();
    $db = Database::getInstance()->getConnection();

    // Cargar datos
    echo "📂 Cargando datos desde: $jsonFile\n";
    $json = file_get_contents($jsonFile);
    $data = json_decode($json, true);

    if (!$data) {
        throw new Exception("Error al parsear JSON");
    }

    echo "   ✓ Datos cargados correctamente\n";
    echo "   📅 Fecha de exportación: " . ($data['export_date'] ?? 'N/A') . "\n\n";

    // Mapeo de IDs antiguos a nuevos
    $projectIdMap = [];
    $userIdMap = [];
    $dbIdMap = [];

    // 1. Importar Proyectos
    echo "📦 Importando proyectos...\n";
    $stmt = $db->prepare("INSERT INTO projects (name, description, status, storage_quota, client_id, start_date, 
                          google_client_id, google_client_secret, domain, allowed_origins, external_auth_enabled, 
                          created_at, updated_at) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($data['projects'] as $project) {
        $oldId = $project['id'];

        try {
            $stmt->execute([
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

            $newId = $db->lastInsertId();
            $projectIdMap[$oldId] = $newId;
            echo "   ✓ Proyecto '{$project['name']}' importado (ID: $oldId → $newId)\n";
        } catch (Exception $e) {
            echo "   ⚠ Error importando proyecto '{$project['name']}': " . $e->getMessage() . "\n";
        }
    }

    // 2. Importar Usuarios Clientes
    echo "\n📦 Importando usuarios clientes...\n";

    // Obtener el ID del rol "Usuario" o "Cliente"
    $roleStmt = $db->prepare("SELECT id FROM roles WHERE name IN ('Usuario', 'Cliente') ORDER BY id DESC LIMIT 1");
    $roleStmt->execute();
    $defaultRoleId = $roleStmt->fetchColumn();

    if (!$defaultRoleId) {
        echo "   ⚠ Advertencia: No se encontró rol 'Usuario' o 'Cliente', usando NULL\n";
        $defaultRoleId = null;
    }

    $stmt = $db->prepare("INSERT INTO users (username, email, role_id, status, public_name, phone, address, tax_id, google_id, created_at) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($data['clients'] as $client) {
        $oldId = $client['id'];

        // Verificar si el usuario ya existe (por email o username)
        $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $checkStmt->execute([$client['email'], $client['username']]);
        $existingId = $checkStmt->fetchColumn();

        if ($existingId) {
            $userIdMap[$oldId] = $existingId;
            echo "   ⚠ Usuario '{$client['username']}' ya existe (ID: $oldId → $existingId)\n";
            continue;
        }

        try {
            $stmt->execute([
                $client['username'],
                $client['email'],
                $defaultRoleId, // Usar rol por defecto
                $client['status'] ?? 1,
                $client['public_name'] ?? null,
                $client['phone'] ?? null,
                $client['address'] ?? null,
                $client['tax_id'] ?? null,
                $client['google_id'] ?? null,
                $client['created_at'] ?? date('Y-m-d H:i:s')
            ]);

            $newId = $db->lastInsertId();
            $userIdMap[$oldId] = $newId;
            echo "   ✓ Usuario '{$client['username']}' importado (ID: $oldId → $newId)\n";
        } catch (Exception $e) {
            echo "   ⚠ Error importando usuario '{$client['username']}': " . $e->getMessage() . "\n";
        }
    }

    // 3. Importar Configuraciones de Bases de Datos
    echo "\n📦 Importando configuraciones de bases de datos...\n";
    $stmt = $db->prepare("INSERT INTO databases (name, path, type, project_id, config, created_at, last_edit_at) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)");

    foreach ($data['databases'] as $database) {
        $oldId = $database['id'];
        $oldProjectId = $database['project_id'] ?? null;
        $newProjectId = $oldProjectId ? ($projectIdMap[$oldProjectId] ?? null) : null;

        try {
            $stmt->execute([
                $database['name'],
                $database['path'],
                $database['type'] ?? 'sqlite',
                $newProjectId,
                $database['config'] ?? null,
                $database['created_at'] ?? date('Y-m-d H:i:s'),
                $database['last_edit_at'] ?? null
            ]);

            $newId = $db->lastInsertId();
            $dbIdMap[$oldId] = $newId;
            echo "   ✓ Base de datos '{$database['name']}' importada (ID: $oldId → $newId)\n";
        } catch (Exception $e) {
            echo "   ⚠ Error importando BD '{$database['name']}': " . $e->getMessage() . "\n";
        }
    }

    // 4. Importar Relaciones Proyecto-Usuario
    echo "\n📦 Importando relaciones proyecto-usuario...\n";
    $stmt = $db->prepare("INSERT INTO project_users (project_id, user_id, permissions, external_permissions, external_access_enabled, assigned_at) 
                          VALUES (?, ?, ?, ?, ?, ?)");

    foreach ($data['project_users'] as $relation) {
        $oldProjectId = $relation['project_id'];
        $oldUserId = $relation['user_id'];

        $newProjectId = $projectIdMap[$oldProjectId] ?? null;
        $newUserId = $userIdMap[$oldUserId] ?? null;

        if (!$newProjectId || !$newUserId) {
            echo "   ⚠ Relación omitida (proyecto o usuario no encontrado)\n";
            continue;
        }

        try {
            $stmt->execute([
                $newProjectId,
                $newUserId,
                $relation['permissions'] ?? null,
                $relation['external_permissions'] ?? null,
                $relation['external_access_enabled'] ?? 1,
                $relation['assigned_at'] ?? date('Y-m-d H:i:s')
            ]);

            echo "   ✓ Relación importada (Proyecto: $newProjectId, Usuario: $newUserId)\n";
        } catch (Exception $e) {
            echo "   ⚠ Error importando relación: " . $e->getMessage() . "\n";
        }
    }

    // 5. Actualizar client_id en proyectos
    echo "\n📦 Actualizando client_id en proyectos...\n";
    foreach ($data['projects'] as $project) {
        $oldClientId = $project['client_id'] ?? null;
        if ($oldClientId && isset($userIdMap[$oldClientId])) {
            $newProjectId = $projectIdMap[$project['id']];
            $newClientId = $userIdMap[$oldClientId];

            $db->prepare("UPDATE projects SET client_id = ? WHERE id = ?")
                ->execute([$newClientId, $newProjectId]);

            echo "   ✓ Proyecto ID $newProjectId vinculado a cliente ID $newClientId\n";
        }
    }

    echo "\n✅ Importación completada exitosamente\n\n";

    // Resumen
    echo "=== Resumen de Importación ===\n";
    echo "Proyectos importados:   " . count($projectIdMap) . "\n";
    echo "Usuarios importados:    " . count($userIdMap) . "\n";
    echo "Bases de datos:         " . count($dbIdMap) . "\n";
    echo "Relaciones P-U:         " . count($data['project_users']) . "\n";
    echo "\n";

    // Instrucciones finales
    echo "=== Próximos Pasos ===\n";
    echo "1. Verificar que los archivos .db estén en el directorio data/\n";
    echo "2. Verificar que los archivos media estén en uploads/\n";
    echo "3. Configurar permisos: chmod -R 777 data uploads\n";
    echo "4. Probar acceso a proyectos y bases de datos\n";
    echo "5. Configurar Google OAuth si es necesario\n\n";

} catch (Exception $e) {
    echo "\n❌ Error durante la importación:\n";
    echo $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
