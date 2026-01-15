<?php

namespace App\Modules\Billing\Services;

use App\Core\Database;
use PDO;

/**
 * Servicio de Actualización de Estado de Cuotas
 * Marca cuotas como vencidas automáticamente
 */
class InstallmentStatusService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Marca cuotas pendientes como vencidas
     * Ejecutado por cron job diariamente
     * 
     * @return array Resultado del procesamiento
     */
    public function markOverdueInstallments()
    {
        $today = date('Y-m-d');

        // Actualizar cuotas pendientes cuya fecha de vencimiento ya pasó
        $stmt = $this->db->prepare("
            UPDATE installments 
            SET status = 'vencida', updated_at = CURRENT_TIMESTAMP
            WHERE status = 'pendiente' 
            AND due_date < ?
        ");
        $stmt->execute([$today]);

        $affectedRows = $stmt->rowCount();

        // Obtener las cuotas que se marcaron como vencidas para enviar notificaciones
        if ($affectedRows > 0) {
            $this->sendOverdueNotifications($today);
        }

        return [
            'marked_as_overdue' => $affectedRows,
            'date' => $today
        ];
    }

    /**
     * Envía notificaciones para cuotas recién marcadas como vencidas
     */
    private function sendOverdueNotifications($today)
    {
        $emailService = new EmailService();

        // Obtener cuotas vencidas de hoy
        $stmt = $this->db->prepare("
            SELECT i.*, p.name as project_name, p.client_id, c.name as client_name, c.email as client_email,
                   JULIANDAY(?) - JULIANDAY(i.due_date) as days_overdue
            FROM installments i
            INNER JOIN projects p ON i.project_id = p.id
            LEFT JOIN clients c ON p.client_id = c.id
            WHERE i.status = 'vencida'
            AND DATE(i.updated_at) = ?
        ");
        $stmt->execute([$today, $today]);
        $overdueInstallments = $stmt->fetchAll();

        foreach ($overdueInstallments as $installment) {
            $recipient = $installment['client_email'] ?: 'admin@example.com';

            try {
                $emailService->sendOverdueNotification([
                    'to' => $recipient,
                    'project_name' => $installment['project_name'],
                    'client_name' => $installment['client_name'],
                    'amount' => $installment['amount'],
                    'due_date' => $installment['due_date'],
                    'installment_number' => $installment['installment_number'],
                    'days_overdue' => (int) $installment['days_overdue']
                ]);

                // Registrar notificación
                $logStmt = $this->db->prepare("
                    INSERT INTO notifications_log (installment_id, notification_type, recipient, status)
                    VALUES (?, ?, ?, ?)
                ");
                $logStmt->execute([$installment['id'], 'overdue', $recipient, 'sent']);

            } catch (\Exception $e) {
                // Log error
                error_log("Error sending overdue notification: " . $e->getMessage());
            }
        }
    }

    /**
     * Obtiene estadísticas de cuotas vencidas
     * 
     * @return array Estadísticas
     */
    public function getOverdueStats()
    {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total_overdue,
                SUM(amount) as total_amount_overdue,
                COUNT(DISTINCT project_id) as projects_with_overdue
            FROM installments
            WHERE status = 'vencida'
        ");

        return $stmt->fetch();
    }
}
