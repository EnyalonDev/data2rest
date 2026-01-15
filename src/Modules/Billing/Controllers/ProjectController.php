<?php

namespace App\Modules\Billing\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use App\Modules\Billing\Services\InstallmentGenerator;
use App\Modules\Billing\Services\PlanChangeService;
use PDO;

/**
 * Controlador REST de Proyectos con Billing
 */
class ProjectController extends BaseController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * POST /api/billing/projects
     * Crea un proyecto con plan de pago
     */
    public function create()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        // Validaciones
        if (empty($input['name'])) {
            $this->json(['error' => 'El nombre del proyecto es requerido'], 400);
        }

        if (empty($input['plan_id'])) {
            $this->json(['error' => 'El plan de pago es requerido'], 400);
        }

        if (empty($input['start_date'])) {
            $this->json(['error' => 'La fecha de inicio es requerida'], 400);
        }

        try {
            $this->db->beginTransaction();

            // Crear proyecto
            $stmt = $this->db->prepare("
                INSERT INTO projects (name, description, client_id, start_date, current_plan_id, billing_status)
                VALUES (?, ?, ?, ?, ?, 'active')
            ");
            $stmt->execute([
                $input['name'],
                $input['description'] ?? null,
                $input['client_id'] ?? null,
                $input['start_date'],
                $input['plan_id']
            ]);

            $projectId = $this->db->lastInsertId();

            // Generar cuotas iniciales
            $generator = new InstallmentGenerator();
            $installments = $generator->generateInstallments(
                $projectId,
                $input['plan_id'],
                $input['start_date']
            );

            $this->db->commit();

            $this->json([
                'success' => true,
                'message' => 'Proyecto creado exitosamente',
                'project_id' => $projectId,
                'installments_created' => count($installments)
            ], 201);

        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * PATCH /api/billing/projects/{id}/change-plan
     * Cambia el plan de pago de un proyecto
     */
    public function changePlan($id)
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['new_plan_id'])) {
            $this->json(['error' => 'El nuevo plan es requerido'], 400);
        }

        try {
            $service = new PlanChangeService();
            $result = $service->changePlan(
                $id,
                $input['new_plan_id'],
                $input['new_start_date'] ?? null,
                $input['reason'] ?? null,
                $input['user_id'] ?? null
            );

            $this->json($result);

        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * PATCH /api/billing/projects/{id}/start-date
     * Cambia la fecha de inicio de un proyecto
     */
    public function changeStartDate($id)
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['new_start_date'])) {
            $this->json(['error' => 'La nueva fecha de inicio es requerida'], 400);
        }

        try {
            $service = new PlanChangeService();
            $result = $service->changeStartDate(
                $id,
                $input['new_start_date'],
                $input['user_id'] ?? null
            );

            $this->json($result);

        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/billing/projects/{id}/plan-history
     * Obtiene el historial de cambios de plan
     */
    public function getPlanHistory($id)
    {
        $stmt = $this->db->prepare("
            SELECT pph.*, 
                   op.name as old_plan_name,
                   np.name as new_plan_name,
                   u.username as changed_by_username
            FROM project_plan_history pph
            LEFT JOIN payment_plans op ON pph.old_plan_id = op.id
            LEFT JOIN payment_plans np ON pph.new_plan_id = np.id
            LEFT JOIN users u ON pph.changed_by = u.id
            WHERE pph.project_id = ?
            ORDER BY pph.created_at DESC
        ");
        $stmt->execute([$id]);
        $history = $stmt->fetchAll();

        $this->json([
            'success' => true,
            'data' => $history,
            'count' => count($history)
        ]);
    }

    /**
     * GET /api/billing/projects/{id}/services
     */
    public function getServices($id)
    {
        $stmt = $this->db->prepare("
            SELECT ps.*, bs.name, bs.description, bs.price
            FROM project_services ps
            JOIN billing_services bs ON ps.service_id = bs.id
            WHERE ps.project_id = ?
        ");
        $stmt->execute([$id]);
        $services = $stmt->fetchAll();

        $this->json(['success' => true, 'data' => $services]);
    }

    /**
     * POST /api/billing/projects/{id}/services
     */
    public function addService($id)
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['service_id'])) {
            $this->json(['error' => 'ID de servicio requerido'], 400);
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO project_services (project_id, service_id, custom_price, quantity, billing_period)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $id,
                $input['service_id'],
                $input['custom_price'] ?? null,
                $input['quantity'] ?? 1,
                $input['billing_period'] ?? 'monthly'
            ]);

            $this->json(['success' => true, 'id' => $this->db->lastInsertId()]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/billing/projects/{id}/services/{service_id}
     */
    public function removeService($id, $service_id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM project_services WHERE project_id = ? AND service_id = ?");
            $stmt->execute([$id, $service_id]);
            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}
