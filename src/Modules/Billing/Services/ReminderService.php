<?php

namespace App\Modules\Billing\Services;

use App\Core\Database;
use PDO;

/**
 * Servicio de Recordatorios de Pago
 * Envía notificaciones antes del vencimiento de cuotas
 */
class ReminderService
{
    private $db;
    private $emailService;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->emailService = new EmailService();
    }

    /**
     * Procesa recordatorios para cuotas próximas a vencer
     * Ejecutado por cron job diariamente
     * 
     * @param int $daysBeforeDue Días antes del vencimiento (default: 5)
     * @return array Resultado del procesamiento
     */
    public function processReminders($daysBeforeDue = 5)
    {
        $targetDate = date('Y-m-d', strtotime("+{$daysBeforeDue} days"));

        // Obtener cuotas pendientes que vencen en X días
        $stmt = $this->db->prepare("
            SELECT i.*, p.name as project_name, p.client_id, c.name as client_name, c.email as client_email
            FROM installments i
            INNER JOIN projects p ON i.project_id = p.id
            LEFT JOIN clients c ON p.client_id = c.id
            WHERE i.status = 'pendiente'
            AND i.due_date = ?
        ");
        $stmt->execute([$targetDate]);
        $installments = $stmt->fetchAll();

        $sent = 0;
        $failed = 0;

        foreach ($installments as $installment) {
            // Verificar si ya se envió recordatorio para esta cuota
            $checkStmt = $this->db->prepare("
                SELECT COUNT(*) FROM notifications_log 
                WHERE installment_id = ? 
                AND notification_type = 'reminder'
                AND DATE(sent_at) = CURRENT_DATE
            ");
            $checkStmt->execute([$installment['id']]);

            if ($checkStmt->fetchColumn() > 0) {
                continue; // Ya se envió hoy
            }

            // Enviar recordatorio
            $recipient = $installment['client_email'] ?: 'admin@example.com';

            try {
                $this->emailService->sendReminder([
                    'to' => $recipient,
                    'project_name' => $installment['project_name'],
                    'client_name' => $installment['client_name'],
                    'amount' => $installment['amount'],
                    'due_date' => $installment['due_date'],
                    'installment_number' => $installment['installment_number']
                ]);

                // Registrar notificación exitosa
                $this->logNotification(
                    $installment['id'],
                    'reminder',
                    $recipient,
                    'sent'
                );

                $sent++;

            } catch (\Exception $e) {
                // Registrar error
                $this->logNotification(
                    $installment['id'],
                    'reminder',
                    $recipient,
                    'failed',
                    $e->getMessage()
                );

                $failed++;
            }
        }

        return [
            'total_found' => count($installments),
            'sent' => $sent,
            'failed' => $failed,
            'target_date' => $targetDate
        ];
    }

    /**
     * Registra una notificación en el log
     */
    private function logNotification($installmentId, $type, $recipient, $status, $error = null)
    {
        $stmt = $this->db->prepare("
            INSERT INTO notifications_log (installment_id, notification_type, recipient, status, error_message)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$installmentId, $type, $recipient, $status, $error]);
    }
}
