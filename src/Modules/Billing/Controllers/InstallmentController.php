<?php

namespace App\Modules\Billing\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use App\Modules\Billing\Repositories\InstallmentRepository;
use PDO;

/**
 * Controlador REST de Cuotas
 */
class InstallmentController extends BaseController
{
    private $db;
    private $repository;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->repository = new InstallmentRepository();
    }

    /**
     * GET /api/billing/projects/{id}/installments
     * Obtiene todas las cuotas de un proyecto
     */
    public function getByProject($projectId)
    {
        $installments = $this->repository->getByProject($projectId);

        $this->json([
            'success' => true,
            'data' => $installments,
            'count' => count($installments)
        ]);
    }

    /**
     * GET /api/billing/installments/upcoming
     * Obtiene cuotas próximas a vencer
     */
    public function getUpcoming()
    {
        $days = $_GET['days'] ?? 30;
        $limit = $_GET['limit'] ?? 50;

        $installments = $this->repository->getUpcoming($days, $limit);

        $this->json([
            'success' => true,
            'data' => $installments,
            'count' => count($installments),
            'days' => (int) $days
        ]);
    }

    /**
     * GET /api/billing/installments/overdue
     * Obtiene cuotas vencidas
     */
    public function getOverdue()
    {
        $limit = $_GET['limit'] ?? 100;
        $installments = $this->repository->getOverdue($limit);

        $this->json([
            'success' => true,
            'data' => $installments,
            'count' => count($installments)
        ]);
    }

    /**
     * POST /api/billing/installments/{id}/pay
     * Registra un pago para una cuota (Directo por Admin)
     */
    public function pay($id)
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['amount'])) {
            $this->json(['error' => 'El monto es requerido'], 400);
        }

        $installment = $this->repository->getById($id);

        if (!$installment) {
            $this->json(['error' => 'Cuota no encontrada'], 404);
        }

        if ($installment['status'] === 'pagada') {
            $this->json(['error' => 'Esta cuota ya está pagada'], 400);
        }

        try {
            $result = $this->repository->markAsPaid(
                $id,
                $input['amount'],
                $input['payment_method'] ?? null,
                $input['reference'] ?? null,
                $input['notes'] ?? null
            );

            $this->json([
                'success' => true,
                'message' => 'Pago registrado y aprobado exitosamente',
                'payment_id' => $result['payment_id']
            ], 201);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/billing/installments/{id}/report
     * Reporta un pago para una cuota (Por Cliente - Pendiente Aprobación)
     */
    public function report($id)
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['amount'])) {
            $this->json(['error' => 'El monto es requerido'], 400);
        }

        try {
            $paymentId = $this->repository->reportPayment(
                $id,
                $input['amount'],
                $input['payment_method'] ?? null,
                $input['reference'] ?? null,
                $input['notes'] ?? null
            );

            $this->json([
                'success' => true,
                'message' => 'Pago reportado correctamente. Pendiente de revisión.',
                'payment_id' => $paymentId
            ], 201);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/billing/payments/{id}/approve
     */
    public function approve($id)
    {
        try {
            $this->repository->approvePayment($id);
            $this->json(['success' => true, 'message' => 'Pago aprobado exitosamente']);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/billing/payments/{id}/reject
     */
    public function reject($id)
    {
        $input = json_decode(file_get_contents('php://input'), true);
        try {
            $this->repository->rejectPayment($id, $input['reason'] ?? 'Sin motivo especificado');
            $this->json(['success' => true, 'message' => 'Pago rechazado']);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/billing/installments/{id}
     * Obtiene información detallada de una cuota
     */
    public function getById($id)
    {
        $installment = $this->repository->getById($id);

        if (!$installment) {
            $this->json(['error' => 'Cuota no encontrada'], 404);
        }

        // Obtener pagos asociados
        $payments = $this->repository->getPayments($id);

        $this->json([
            'success' => true,
            'data' => $installment,
            'payments' => $payments
        ]);
    }

    /**
     * GET /api/billing/payments/{id}
     */
    public function getPaymentById($id)
    {
        $payment = $this->repository->getPaymentById($id);

        if (!$payment) {
            $this->json(['error' => 'Pago no encontrado'], 404);
        }

        $this->json([
            'success' => true,
            'data' => $payment
        ]);
    }
}
