#!/usr/bin/env php
<?php

/**
 * Demo del Módulo de Billing
 * Crea datos de ejemplo para demostrar todas las funcionalidades
 */

require_once __DIR__ . '/../src/autoload.php';

use App\Core\Config;
use App\Core\Database;
use App\Core\Installer;

Config::loadEnv();
Installer::check();

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  🎬 DEMO DEL MÓDULO DE BILLING                           ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n";
echo "\n";

$db = Database::getInstance()->getConnection();

try {
    // 1. Crear clientes de ejemplo
    echo "👥 Creando clientes de ejemplo...\n";

    $clients = [
        ['name' => 'Empresa ABC S.A.', 'email' => 'contacto@abc.com', 'phone' => '+1234567890'],
        ['name' => 'Startup XYZ', 'email' => 'info@xyz.com', 'phone' => '+0987654321'],
        ['name' => 'Corporación Global', 'email' => 'ventas@global.com', 'phone' => '+1122334455']
    ];

    $clientIds = [];
    foreach ($clients as $client) {
        $stmt = $db->prepare("INSERT INTO clients (name, email, phone) VALUES (?, ?, ?)");
        $stmt->execute([$client['name'], $client['email'], $client['phone']]);
        $clientIds[] = $db->lastInsertId();
        echo "  ✓ Cliente '{$client['name']}' creado (ID: {$db->lastInsertId()})\n";
    }

    // 2. Crear proyectos con planes
    echo "\n📊 Creando proyectos con planes de pago...\n";

    $projects = [
        [
            'name' => 'Desarrollo Web Corporativo',
            'client_id' => $clientIds[0],
            'plan_id' => 1, // Plan Mensual
            'start_date' => date('Y-m-01'), // Primer día del mes actual
            'amount' => 12000
        ],
        [
            'name' => 'App Móvil iOS/Android',
            'client_id' => $clientIds[1],
            'plan_id' => 2, // Plan Anual
            'start_date' => date('Y-m-15'),
            'amount' => 24000
        ],
        [
            'name' => 'Sistema ERP Empresarial',
            'client_id' => $clientIds[2],
            'plan_id' => 1, // Plan Mensual
            'start_date' => date('Y-m-d', strtotime('-2 months')), // Hace 2 meses
            'amount' => 18000
        ]
    ];

    $projectIds = [];
    foreach ($projects as $project) {
        // Crear proyecto
        $stmt = $db->prepare("
            INSERT INTO projects (name, client_id, start_date, current_plan_id, billing_status)
            VALUES (?, ?, ?, ?, 'active')
        ");
        $stmt->execute([
            $project['name'],
            $project['client_id'],
            $project['start_date'],
            $project['plan_id']
        ]);
        $projectId = $db->lastInsertId();
        $projectIds[] = $projectId;

        // Generar cuotas
        $plan = $db->query("SELECT * FROM payment_plans WHERE id = {$project['plan_id']}")->fetch();
        $installmentAmount = $project['amount'] / $plan['installments'];

        for ($i = 1; $i <= $plan['installments']; $i++) {
            $dueDate = new DateTime($project['start_date']);
            if ($plan['frequency'] === 'monthly') {
                $dueDate->modify('+' . ($i - 1) . ' months');
            }

            $stmt = $db->prepare("
                INSERT INTO installments (project_id, plan_id, installment_number, due_date, amount, status)
                VALUES (?, ?, ?, ?, ?, 'pendiente')
            ");
            $stmt->execute([
                $projectId,
                $project['plan_id'],
                $i,
                $dueDate->format('Y-m-d'),
                $installmentAmount
            ]);
        }

        echo "  ✓ Proyecto '{$project['name']}' creado con {$plan['installments']} cuotas\n";
    }

    // 3. Simular algunos pagos
    echo "\n💰 Simulando pagos realizados...\n";

    // Pagar las primeras 2 cuotas del proyecto 1
    $stmt = $db->query("
        SELECT * FROM installments 
        WHERE project_id = {$projectIds[0]} 
        ORDER BY installment_number ASC 
        LIMIT 2
    ");
    $installments = $stmt->fetchAll();

    foreach ($installments as $inst) {
        // Registrar pago
        $stmt = $db->prepare("
            INSERT INTO payments (installment_id, amount, payment_method, reference)
            VALUES (?, ?, 'transferencia', ?)
        ");
        $stmt->execute([
            $inst['id'],
            $inst['amount'],
            'TRX-' . str_pad($inst['id'], 6, '0', STR_PAD_LEFT)
        ]);

        // Marcar cuota como pagada
        $db->exec("UPDATE installments SET status = 'pagada' WHERE id = {$inst['id']}");

        echo "  ✓ Cuota #{$inst['installment_number']} pagada ($" . number_format($inst['amount'], 2) . ")\n";
    }

    // Pagar la cuota del proyecto 3 (hace 2 meses)
    $stmt = $db->query("
        SELECT * FROM installments 
        WHERE project_id = {$projectIds[2]} 
        ORDER BY installment_number ASC 
        LIMIT 1
    ");
    $inst = $stmt->fetch();

    if ($inst) {
        $stmt = $db->prepare("
            INSERT INTO payments (installment_id, amount, payment_method, reference)
            VALUES (?, ?, 'efectivo', ?)
        ");
        $stmt->execute([$inst['id'], $inst['amount'], 'CASH-001']);
        $db->exec("UPDATE installments SET status = 'pagada' WHERE id = {$inst['id']}");
        echo "  ✓ Cuota del proyecto 3 pagada ($" . number_format($inst['amount'], 2) . ")\n";
    }

    // 4. Marcar algunas cuotas como vencidas
    echo "\n⏰ Marcando cuotas vencidas...\n";
    $today = date('Y-m-d');
    $stmt = $db->prepare("
        UPDATE installments 
        SET status = 'vencida' 
        WHERE status = 'pendiente' 
        AND due_date < ?
    ");
    $stmt->execute([$today]);
    $overdue = $stmt->rowCount();
    echo "  ✓ {$overdue} cuotas marcadas como vencidas\n";

    // 5. Mostrar estadísticas
    echo "\n📊 ESTADÍSTICAS GENERADAS:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    // Total de cuotas por estado
    $stats = $db->query("
        SELECT 
            status,
            COUNT(*) as count,
            SUM(amount) as total
        FROM installments
        GROUP BY status
    ")->fetchAll();

    echo "\nCuotas por estado:\n";
    foreach ($stats as $stat) {
        $emoji = $stat['status'] === 'pagada' ? '✅' : ($stat['status'] === 'vencida' ? '❌' : '⏳');
        echo "  {$emoji} " . ucfirst($stat['status']) . ": {$stat['count']} cuotas ($" . number_format($stat['total'], 2) . ")\n";
    }

    // Resumen por cliente
    echo "\nResumen por cliente:\n";
    $clientStats = $db->query("
        SELECT 
            c.name,
            COUNT(DISTINCT p.id) as projects,
            COUNT(i.id) as installments,
            SUM(CASE WHEN i.status = 'pagada' THEN i.amount ELSE 0 END) as paid,
            SUM(CASE WHEN i.status = 'pendiente' THEN i.amount ELSE 0 END) as pending
        FROM clients c
        LEFT JOIN projects p ON c.id = p.client_id
        LEFT JOIN installments i ON p.id = i.project_id
        GROUP BY c.id
    ")->fetchAll();

    foreach ($clientStats as $cs) {
        echo "\n  📌 {$cs['name']}\n";
        echo "     Proyectos: {$cs['projects']}\n";
        echo "     Cuotas totales: {$cs['installments']}\n";
        echo "     Pagado: $" . number_format($cs['paid'], 2) . "\n";
        echo "     Pendiente: $" . number_format($cs['pending'], 2) . "\n";
    }

    echo "\n";
    echo "╔══════════════════════════════════════════════════════════╗\n";
    echo "║  ✅ DEMO COMPLETADA EXITOSAMENTE                         ║\n";
    echo "╚══════════════════════════════════════════════════════════╝\n";
    echo "\n";
    echo "🎯 Ahora puedes:\n";
    echo "  1. Probar los endpoints REST en /api/billing/*\n";
    echo "  2. Ver reportes financieros\n";
    echo "  3. Cambiar planes de proyectos\n";
    echo "  4. Registrar más pagos\n";
    echo "\n";
    echo "📚 Consulta la documentación en docs/BILLING.md\n";
    echo "\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

exit(0);
