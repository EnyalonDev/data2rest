<?php

namespace App\Modules\Billing\Repositories;

use App\Core\Database;
use PDO;

/**
 * Repositorio de Cuotas
 * Maneja el acceso a datos de installments
 */
class InstallmentRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtiene cuotas de un proyecto con información relacionada
     */
    public function getByProject($projectId)
    {
        $stmt = $this->db->prepare("
            SELECT i.*, 
                   pp.name as plan_name,
                   pp.frequency as plan_frequency,
                   (SELECT SUM(p.amount) FROM payments p WHERE p.installment_id = i.id) as paid_amount,
                   (SELECT COUNT(*) FROM payments p WHERE p.installment_id = i.id) as payment_count
            FROM installments i
            LEFT JOIN payment_plans pp ON i.plan_id = pp.id
            WHERE i.project_id = ?
            ORDER BY i.installment_number ASC
        ");
        $stmt->execute([$projectId]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene cuotas próximas a vencer (próximos X días)
     */
    public function getUpcoming($days = 30, $limit = 50)
    {
        $targetDate = date('Y-m-d', strtotime("+{$days} days"));

        $stmt = $this->db->prepare("
            SELECT i.*, 
                   p.name as project_name,
                   c.name as client_name,
                   c.email as client_email,
                   pp.name as plan_name
            FROM installments i
            INNER JOIN projects p ON i.project_id = p.id
            LEFT JOIN clients c ON p.client_id = c.id
            LEFT JOIN payment_plans pp ON i.plan_id = pp.id
            WHERE i.status = 'pendiente'
            AND i.due_date BETWEEN DATE('now') AND ?
            ORDER BY i.due_date ASC
            LIMIT ?
        ");
        $stmt->execute([$targetDate, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene cuotas vencidas
     */
    public function getOverdue($limit = 100)
    {
        $stmt = $this->db->prepare("
            SELECT i.*, 
                   p.name as project_name,
                   c.name as client_name,
                   c.email as client_email,
                   pp.name as plan_name,
                   JULIANDAY('now') - JULIANDAY(i.due_date) as days_overdue
            FROM installments i
            INNER JOIN projects p ON i.project_id = p.id
            LEFT JOIN clients c ON p.client_id = c.id
            LEFT JOIN payment_plans pp ON i.plan_id = pp.id
            WHERE i.status = 'vencida'
            ORDER BY i.due_date ASC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene una cuota por ID con información completa
     */
    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT i.*, 
                   p.name as project_name,
                   p.client_id,
                   c.name as client_name,
                   c.email as client_email,
                   pp.name as plan_name,
                   pp.frequency as plan_frequency,
                   (SELECT SUM(p.amount) FROM payments p WHERE p.installment_id = i.id) as paid_amount
            FROM installments i
            INNER JOIN projects p ON i.project_id = p.id
            LEFT JOIN clients c ON p.client_id = c.id
            LEFT JOIN payment_plans pp ON i.plan_id = pp.id
            WHERE i.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Marca una cuota como pagada
     */
    public function markAsPaid($installmentId, $amount, $paymentMethod = null, $reference = null, $notes = null)
    {
        try {
            $this->db->beginTransaction();

            // Registrar el pago
            $stmt = $this->db->prepare("
                INSERT INTO payments (installment_id, amount, payment_method, reference, notes, status)
                VALUES (?, ?, ?, ?, ?, 'approved')
            ");
            $stmt->execute([$installmentId, $amount, $paymentMethod, $reference, $notes]);
            $paymentId = $this->db->lastInsertId();

            // Actualizar estado de la cuota
            $stmt = $this->db->prepare("
                UPDATE installments 
                SET status = 'pagada', updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$installmentId]);

            $this->db->commit();

            return [
                'success' => true,
                'payment_id' => $paymentId
            ];

        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Registra un reporte de pago (pendiente de aprobación)
     */
    public function reportPayment($installmentId, $amount, $paymentMethod = null, $reference = null, $notes = null)
    {
        $stmt = $this->db->prepare("
            INSERT INTO payments (installment_id, amount, payment_method, reference, notes, status)
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$installmentId, $amount, $paymentMethod, $reference, $notes]);
        return $this->db->lastInsertId();
    }

    /**
     * Aprueba un pago
     */
    public function approvePayment($paymentId)
    {
        try {
            $this->db->beginTransaction();

            // Obtener información del pago
            $stmt = $this->db->prepare("SELECT installment_id FROM payments WHERE id = ?");
            $stmt->execute([$paymentId]);
            $payment = $stmt->fetch();

            if (!$payment)
                throw new \Exception("Pago no encontrado");

            // Actualizar estado del pago
            $stmt = $this->db->prepare("UPDATE payments SET status = 'approved' WHERE id = ?");
            $stmt->execute([$paymentId]);

            // Actualizar estado de la cuota
            $stmt = $this->db->prepare("
                UPDATE installments 
                SET status = 'pagada', updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$payment['installment_id']]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Rechaza un pago
     */
    public function rejectPayment($paymentId, $reason = null)
    {
        $stmt = $this->db->prepare("UPDATE payments SET status = 'rejected', notes = COALESCE(notes || '\n', '') || ? WHERE id = ?");
        $stmt->execute(["Rechazado: $reason", $paymentId]);
        return true;
    }

    /**
     * Obtiene pagos de una cuota
     */
    public function getPayments($installmentId)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM payments 
            WHERE installment_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$installmentId]);
        return $stmt->fetchAll();
    }

    public function getPaymentById($id)
    {
        $stmt = $this->db->prepare("
            SELECT pay.*,
                   i.installment_number,
                   p.name as project_name,
                   COALESCE(u.public_name, u.username) as client_name
            FROM payments pay
            INNER JOIN installments i ON pay.installment_id = i.id
            INNER JOIN projects p ON i.project_id = p.id
            LEFT JOIN users u ON p.billing_user_id = u.id
            WHERE pay.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
