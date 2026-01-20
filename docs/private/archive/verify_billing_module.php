#!/usr/bin/env php
<?php

/**
 * Script de Verificación del Módulo de Billing
 * Verifica que todas las tablas, servicios y rutas estén correctamente configurados
 */

require_once __DIR__ . '/../src/autoload.php';

use App\Core\Config;
use App\Core\Database;
use App\Core\Installer;

Config::loadEnv();
Installer::check();

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  🔍 VERIFICACIÓN DEL MÓDULO DE BILLING                   ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n";
echo "\n";

$db = Database::getInstance()->getConnection();
$errors = [];
$warnings = [];
$success = [];

// 1. Verificar Tablas
echo "📋 Verificando tablas de la base de datos...\n";
$requiredTables = [
    'clients',
    'payment_plans',
    'installments',
    'payments',
    'project_plan_history',
    'notifications_log'
];

foreach ($requiredTables as $table) {
    $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'");
    if ($stmt->fetch()) {
        echo "  ✓ Tabla '$table' existe\n";
        $success[] = "Tabla $table";
    } else {
        echo "  ✗ Tabla '$table' NO existe\n";
        $errors[] = "Falta tabla $table";
    }
}

// 2. Verificar Planes por Defecto
echo "\n💳 Verificando planes de pago por defecto...\n";
$stmt = $db->query("SELECT COUNT(*) as count FROM payment_plans");
$planCount = $stmt->fetchColumn();

if ($planCount >= 2) {
    echo "  ✓ Planes de pago encontrados: $planCount\n";

    $plans = $db->query("SELECT * FROM payment_plans")->fetchAll();
    foreach ($plans as $plan) {
        echo "    - {$plan['name']} ({$plan['frequency']}, {$plan['installments']} cuotas)\n";
    }
    $success[] = "Planes de pago";
} else {
    echo "  ⚠ Solo se encontraron $planCount planes (se esperaban al menos 2)\n";
    $warnings[] = "Pocos planes de pago";
}

// 3. Verificar Servicios
echo "\n🔧 Verificando servicios...\n";
$services = [
    'App\\Modules\\Billing\\Services\\InstallmentGenerator',
    'App\\Modules\\Billing\\Services\\PlanChangeService',
    'App\\Modules\\Billing\\Services\\ReminderService',
    'App\\Modules\\Billing\\Services\\EmailService',
    'App\\Modules\\Billing\\Services\\InstallmentStatusService'
];

foreach ($services as $service) {
    if (class_exists($service)) {
        echo "  ✓ Servicio " . basename(str_replace('\\', '/', $service)) . " disponible\n";
        $success[] = basename(str_replace('\\', '/', $service));
    } else {
        echo "  ✗ Servicio " . basename(str_replace('\\', '/', $service)) . " NO encontrado\n";
        $errors[] = "Falta servicio " . basename(str_replace('\\', '/', $service));
    }
}

// 4. Verificar Controladores
echo "\n🎮 Verificando controladores...\n";
$controllers = [
    'App\\Modules\\Billing\\Controllers\\ClientController',
    'App\\Modules\\Billing\\Controllers\\ProjectController',
    'App\\Modules\\Billing\\Controllers\\PaymentPlanController',
    'App\\Modules\\Billing\\Controllers\\InstallmentController',
    'App\\Modules\\Billing\\Controllers\\ReportController'
];

foreach ($controllers as $controller) {
    if (class_exists($controller)) {
        echo "  ✓ Controlador " . basename(str_replace('\\', '/', $controller)) . " disponible\n";
        $success[] = basename(str_replace('\\', '/', $controller));
    } else {
        echo "  ✗ Controlador " . basename(str_replace('\\', '/', $controller)) . " NO encontrado\n";
        $errors[] = "Falta controlador " . basename(str_replace('\\', '/', $controller));
    }
}

// 5. Verificar Scripts de Cron
echo "\n⏰ Verificando scripts de cron jobs...\n";
$cronScripts = [
    __DIR__ . '/billing_send_reminders.php',
    __DIR__ . '/billing_mark_overdue.php'
];

foreach ($cronScripts as $script) {
    if (file_exists($script)) {
        $isExecutable = is_executable($script);
        if ($isExecutable) {
            echo "  ✓ Script " . basename($script) . " existe y es ejecutable\n";
            $success[] = basename($script);
        } else {
            echo "  ⚠ Script " . basename($script) . " existe pero NO es ejecutable\n";
            $warnings[] = basename($script) . " no ejecutable";
        }
    } else {
        echo "  ✗ Script " . basename($script) . " NO encontrado\n";
        $errors[] = "Falta script " . basename($script);
    }
}

// 6. Verificar Documentación
echo "\n📚 Verificando documentación...\n";
$docs = [
    __DIR__ . '/../docs/BILLING.md',
    __DIR__ . '/../src/Modules/Billing/README.md'
];

foreach ($docs as $doc) {
    if (file_exists($doc)) {
        echo "  ✓ Documentación " . basename($doc) . " existe\n";
        $success[] = basename($doc);
    } else {
        echo "  ⚠ Documentación " . basename($doc) . " NO encontrada\n";
        $warnings[] = "Falta " . basename($doc);
    }
}

// 7. Verificar Campos en Proyectos
echo "\n🔗 Verificando campos de billing en tabla projects...\n";
$stmt = $db->query("PRAGMA table_info(projects)");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);

$requiredColumns = ['client_id', 'start_date', 'current_plan_id', 'billing_status'];
foreach ($requiredColumns as $col) {
    if (in_array($col, $columns)) {
        echo "  ✓ Campo '$col' existe en projects\n";
        $success[] = "Campo $col";
    } else {
        echo "  ✗ Campo '$col' NO existe en projects\n";
        $errors[] = "Falta campo $col en projects";
    }
}

// Resumen Final
echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  📊 RESUMEN DE VERIFICACIÓN                              ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n";
echo "\n";

echo "✅ Verificaciones exitosas: " . count($success) . "\n";
echo "⚠️  Advertencias: " . count($warnings) . "\n";
echo "❌ Errores: " . count($errors) . "\n";

if (!empty($warnings)) {
    echo "\n⚠️  ADVERTENCIAS:\n";
    foreach ($warnings as $warning) {
        echo "  - $warning\n";
    }
}

if (!empty($errors)) {
    echo "\n❌ ERRORES:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
    echo "\n";
    echo "⚠️  El módulo NO está completamente instalado.\n";
    exit(1);
} else {
    echo "\n";
    echo "🎉 ¡El módulo de Billing está correctamente instalado!\n";
    echo "\n";
    echo "📝 Próximos pasos:\n";
    echo "  1. Configurar cron jobs (ver docs/BILLING.md)\n";
    echo "  2. Probar endpoints REST\n";
    echo "  3. Crear tu primer cliente y proyecto\n";
    echo "\n";
    exit(0);
}
