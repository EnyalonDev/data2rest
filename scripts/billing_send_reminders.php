#!/usr/bin/env php
<?php

/**
 * Cron Job: Enviar Recordatorios de Pago
 * 
 * Ejecutar diariamente a las 9:00 AM:
 * 0 9 * * * /usr/bin/php /opt/homebrew/var/www/data2rest/scripts/billing_send_reminders.php
 */

require_once __DIR__ . '/../src/autoload.php';

use App\Core\Config;
use App\Core\Installer;
use App\Modules\Billing\Services\ReminderService;

// Inicializar sistema
Config::loadEnv();
Installer::check();

echo "[" . date('Y-m-d H:i:s') . "] Iniciando envío de recordatorios de pago...\n";

try {
    $reminderService = new ReminderService();

    // Enviar recordatorios para cuotas que vencen en 5 días
    $result = $reminderService->processReminders(5);

    echo "✓ Recordatorios procesados:\n";
    echo "  - Cuotas encontradas: {$result['total_found']}\n";
    echo "  - Enviados exitosamente: {$result['sent']}\n";
    echo "  - Fallos: {$result['failed']}\n";
    echo "  - Fecha objetivo: {$result['target_date']}\n";

    // Log en el sistema
    $db = \App\Core\Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        INSERT INTO logs (type, details) 
        VALUES ('BILLING_REMINDERS_SENT', ?)
    ");
    $stmt->execute([json_encode($result)]);

    echo "[" . date('Y-m-d H:i:s') . "] Proceso completado exitosamente.\n";

} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";

    // Log del error
    try {
        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO logs (type, details) 
            VALUES ('BILLING_REMINDERS_ERROR', ?)
        ");
        $stmt->execute([json_encode(['error' => $e->getMessage()])]);
    } catch (\Exception $logError) {
        // Ignorar errores de logging
    }

    exit(1);
}

exit(0);
