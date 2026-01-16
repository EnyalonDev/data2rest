<?php

namespace App\Modules\Billing\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use PDO;

/**
 * Controlador REST de Planes de Pago
 */
/**
 * PaymentPlanController Controller
 *
 * Core Features: TODO
 *
 * Security: Requires login, permission checks as implemented.
 *
 * @package App\Modules\
 * @author DATA2REST Development Team
 * @version 1.0.0
 */
class PaymentPlanController extends BaseController
{
    private $db;

/**
 * __construct method
 *
 * @return void
 */
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * GET /api/billing/payment-plans
     * Lista todos los planes de pago activos
     */
/**
 * index method
 *
 * @return void
 */
    public function index()
    {
        $stmt = $this->db->query("
            SELECT * FROM payment_plans 
            WHERE status = 'active'
            ORDER BY frequency, installments
        ");
        $plans = $stmt->fetchAll();

        $this->json([
            'success' => true,
            'data' => $plans,
            'count' => count($plans)
        ]);
    }

    /**
     * POST /api/billing/payment-plans
     * Crea un nuevo plan de pago
     */
/**
 * create method
 *
 * @return void
 */
    public function create()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        // Validaciones
        if (empty($input['name'])) {
            $this->json(['error' => 'El nombre es requerido'], 400);
        }

        // Mapear y validar frecuencia
        $frequency = $input['frequency'] ?? '';
        $allowedFrequencies = ['monthly', 'yearly', 'mensual', 'anual', 'unico'];
        if (empty($frequency) || !in_array($frequency, $allowedFrequencies)) {
            $this->json(['error' => 'La frecuencia no es válida (mensual, anual o unico)'], 400);
        }

        // Mapear cuotas (soporta installments o total_installments)
        $installments = $input['installments'] ?? $input['total_installments'] ?? 0;
        if ($installments < 1) {
            $this->json(['error' => 'El número de cuotas debe ser mayor a 0'], 400);
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO payment_plans (name, frequency, installments, description, contract_duration_months, status)
                VALUES (?, ?, ?, ?, ?, 'active')
            ");
            $stmt->execute([
                $input['name'],
                $frequency,
                $installments,
                $input['description'] ?? null,
                $input['contract_duration_months'] ?? $installments, // Default to installments if not provided
            ]);

            $planId = $this->db->lastInsertId();

            $this->json([
                'success' => true,
                'message' => 'Plan de pago creado exitosamente',
                'plan_id' => $planId
            ], 201);

        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/billing/payment-plans/{id}
     * Obtiene información de un plan específico
     */
/**
 * getById method
 *
 * @return void
 */
    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM payment_plans WHERE id = ?");
        $stmt->execute([$id]);
        $plan = $stmt->fetch();

        if (!$plan) {
            $this->json(['error' => 'Plan no encontrado'], 404);
        }

        // Obtener estadísticas de uso
        $statsStmt = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT p.id) as projects_using,
                COUNT(i.id) as total_installments,
                SUM(CASE WHEN i.status = 'pagada' THEN 1 ELSE 0 END) as paid_installments
            FROM projects p
            LEFT JOIN installments i ON p.id = i.project_id
            WHERE p.current_plan_id = ?
        ");
        $statsStmt->execute([$id]);
        $stats = $statsStmt->fetch();

        $this->json([
            'success' => true,
            'data' => $plan,
            'stats' => $stats
        ]);
    }

    /**
     * PUT /api/billing/payment-plans/{id}
     * Actualiza un plan de pago
     */
/**
 * update method
 *
 * @return void
 */
    public function update($id)
    {
        $input = json_decode(file_get_contents('php://input'), true);

        // Verificar que el plan existe
        $stmt = $this->db->prepare("SELECT * FROM payment_plans WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            $this->json(['error' => 'Plan no encontrado'], 404);
        }

        try {
            $updates = [];
            $values = [];

            if (isset($input['name'])) {
                $updates[] = "name = ?";
                $values[] = $input['name'];
            }

            if (isset($input['description'])) {
                $updates[] = "description = ?";
                $values[] = $input['description'];
            }

            if (isset($input['frequency'])) {
                $updates[] = "frequency = ?";
                $values[] = $input['frequency'];
            }

            $installments = $input['installments'] ?? $input['total_installments'] ?? null;
            if ($installments !== null) {
                $updates[] = "installments = ?";
                $values[] = $installments;
            }

            if (isset($input['contract_duration_months'])) {
                $updates[] = "contract_duration_months = ?";
                $values[] = $input['contract_duration_months'];
            }

            if (isset($input['status'])) {
                $updates[] = "status = ?";
                $values[] = $input['status'];
            }

            if (empty($updates)) {
                $this->json(['error' => 'No hay datos para actualizar'], 400);
            }

            $values[] = $id;
            $sql = "UPDATE payment_plans SET " . implode(', ', $updates) . " WHERE id = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($values);

            $this->json([
                'success' => true,
                'message' => 'Plan actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}
