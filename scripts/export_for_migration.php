<?php
/**
 * Script de Exportación para Migración Limpia
 * 
 * Exporta datos selectivos de la instalación actual para importar
 * en una instalación limpia de Data2Rest.
 * 
 * Uso: php scripts/export_for_migration.php
 */

require_once __DIR__ . '/../src/autoload.php';

use App\Core\Database;
use App\Core\Config;

echo "=== Data2Rest - Exportación para Migración ===\n\n";

try {
    Config::loadEnv();
    $db = Database::getInstance()->getConnection();

    $exportData = [
        'export_date' => date('Y-m-d H:i:s'),
        'version' => '1.0',
        'projects' => [],
        'databases' => [],
        'clients' => [],
        'project_users' => [],
        'google_configs' => [],
        'payment_plans' => [],
        'billing_services' => []
    ];

    // 1. Exportar Proyectos
    echo "📦 Exportando proyectos...\n";
    $stmt = $db->query("SELECT * FROM projects WHERE status = 'active'");
    $exportData['projects'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "   ✓ " . count($exportData['projects']) . " proyectos exportados\n";

    // 2. Exportar Configuraciones de Bases de Datos
    echo "📦 Exportando configuraciones de bases de datos...\n";
    $stmt = $db->query("SELECT * FROM databases");
    $exportData['databases'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "   ✓ " . count($exportData['databases']) . " bases de datos exportadas\n";

    // 3. Exportar Usuarios Clientes (role_id >= 3)
    echo "📦 Exportando usuarios clientes...\n";
    $stmt = $db->query("SELECT id, username, email, role_id, status, public_name, phone, address, tax_id, google_id, created_at 
                        FROM users 
                        WHERE role_id >= 3 OR role_id IS NULL");
    $exportData['clients'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "   ✓ " . count($exportData['clients']) . " clientes exportados\n";

    // 4. Exportar Relaciones Proyecto-Usuario
    echo "📦 Exportando relaciones proyecto-usuario...\n";
    $stmt = $db->query("SELECT * FROM project_users");
    $exportData['project_users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "   ✓ " . count($exportData['project_users']) . " relaciones exportadas\n";

    // 5. Exportar Configuraciones de Google OAuth
    echo "📦 Exportando configuraciones de Google OAuth...\n";
    $stmt = $db->query("SELECT id, google_client_id, google_client_secret, domain, allowed_origins, external_auth_enabled 
                        FROM projects 
                        WHERE external_auth_enabled = 1");
    $exportData['google_configs'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "   ✓ " . count($exportData['google_configs']) . " configuraciones OAuth exportadas\n";

    // 6. Exportar Planes de Pago (opcional)
    echo "📦 Exportando planes de pago...\n";
    try {
        $stmt = $db->query("SELECT * FROM payment_plans WHERE status = 'active'");
        $exportData['payment_plans'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "   ✓ " . count($exportData['payment_plans']) . " planes exportados\n";
    } catch (Exception $e) {
        echo "   ⚠ Tabla payment_plans no encontrada (opcional)\n";
    }

    // 7. Exportar Servicios de Facturación (opcional)
    echo "📦 Exportando servicios de facturación...\n";
    try {
        $stmt = $db->query("SELECT * FROM billing_services WHERE status = 'active'");
        $exportData['billing_services'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "   ✓ " . count($exportData['billing_services']) . " servicios exportados\n";
    } catch (Exception $e) {
        echo "   ⚠ Tabla billing_services no encontrada (opcional)\n";
    }

    // Guardar archivo JSON
    $outputDir = $_SERVER['HOME'] . '/migracion_data2rest';
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }

    $outputFile = $outputDir . '/migration_data.json';
    file_put_contents($outputFile, json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    echo "\n✅ Exportación completada exitosamente\n";
    echo "📁 Archivo guardado en: $outputFile\n";
    echo "📊 Tamaño: " . round(filesize($outputFile) / 1024, 2) . " KB\n\n";

    // Resumen
    echo "=== Resumen de Exportación ===\n";
    echo "Proyectos:              " . count($exportData['projects']) . "\n";
    echo "Bases de datos:         " . count($exportData['databases']) . "\n";
    echo "Clientes:               " . count($exportData['clients']) . "\n";
    echo "Relaciones P-U:         " . count($exportData['project_users']) . "\n";
    echo "Configs OAuth:          " . count($exportData['google_configs']) . "\n";
    echo "Planes de pago:         " . count($exportData['payment_plans']) . "\n";
    echo "Servicios facturación:  " . count($exportData['billing_services']) . "\n";
    echo "\n";

    // Instrucciones
    echo "=== Próximos Pasos ===\n";
    echo "1. Copiar bases de datos de clientes:\n";
    echo "   cp data/*.db ~/migracion_data2rest/ (excepto system.db)\n\n";
    echo "2. Copiar archivos media:\n";
    echo "   cp -r uploads ~/migracion_data2rest/\n\n";
    echo "3. Realizar instalación limpia en nuevo directorio\n\n";
    echo "4. Ejecutar script de importación:\n";
    echo "   php scripts/import_from_migration.php ~/migracion_data2rest/migration_data.json\n\n";

} catch (Exception $e) {
    echo "\n❌ Error durante la exportación:\n";
    echo $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
