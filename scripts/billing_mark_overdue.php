#!/usr/bin/env php
<?php

/**
 * Cron Job: Marcar Cuotas Vencidas
 * 
 * Ejecutar diariamente a las 00:30 AM:
 * 30 0 * * * /usr/bin/php /opt/homebrew/var/www/data2rest/scripts/billing_mark_overdue.php
 */

require_once __DIR__ . '/../src/autoload.php';

use App\Core\Config;
use App\Core\Installer;
use App\Modules\Billing\Services\InstallmentStatusService;

// Inicializar sistema
Config::loadEnv();
Installer::check();

echo "[" . date('Y-m-d H:i:s') . "] Iniciando actualización de cuotas vencidas...\n";

try {
    $statusService = new InstallmentStatusService();

    // Marcar cuotas vencidas
    $result = $statusService->markOverdueInstallments();

    echo "✓ Cuotas actualizadas:\n";
    echo "  - Marcadas como vencidas: {$result['marked_as_overdue']}\n";
    echo "  - Fecha de proceso: {$result['date']}\n";

    // Obtener estadísticas
    $stats = $statusService->getOverdueStats();
    echo "\n📊 Estadísticas de morosidad:\n";
    echo "  - Total cuotas vencidas: {$stats['total_overdue']}\n";
    echo "  - Monto total vencido: $" . number_format($stats['total_amount_overdue'], 2) . "\n";
    echo "  - Proyectos con morosidad: {$stats['projects_with_overdue']}\n";

    // Log en el sistema
    $db = \App\Core\Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        INSERT INTO logs (type, details) 
        VALUES ('BILLING_OVERDUE_MARKED', ?)
    ");
    $stmt->execute([json_encode(array_merge($result, $stats))]);

    echo "\n[" . date('Y-m-d H:i:s') . "] Proceso completado exitosamente.\n";

} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";

    // Log del error
    try {
        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO logs (type, details) 
            VALUES ('BILLING_OVERDUE_ERROR', ?)
        ");
        $stmt->execute([json_encode(['error' => $e->getMessage()])]);
    } catch (\Exception $logError) {
        // Ignorar errores de logging
    }

    exit(1);
}

exit(0);
