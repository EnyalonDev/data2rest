<?php

namespace App\Modules\Billing\Services;

use App\Core\Database;
use App\Core\Logger;
use PDO;

/**
 * Servicio de Cambio de Plan
 * Orquesta el proceso completo de cambiar el plan de un proyecto
 */
class PlanChangeService
{
    private $db;
    private $installmentGenerator;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->installmentGenerator = new InstallmentGenerator();
    }

    /**
     * Cambia el plan de pago de un proyecto
     * 
     * @param int $projectId ID del proyecto
     * @param int $newPlanId ID del nuevo plan
     * @param string|null $newStartDate Nueva fecha de inicio (opcional)
     * @param string|null $reason Razón del cambio
     * @param int|null $userId Usuario que realiza el cambio
     * @return array Resultado del cambio
     */
    public function changePlan($projectId, $newPlanId, $newStartDate = null, $reason = null, $userId = null)
    {
        try {
            $this->db->beginTransaction();

            // 1. Obtener información actual del proyecto
            $stmt = $this->db->prepare("SELECT * FROM projects WHERE id = ?");
            $stmt->execute([$projectId]);
            $project = $stmt->fetch();

            if (!$project) {
                throw new \Exception("Proyecto no encontrado");
            }

            $oldPlanId = $project['current_plan_id'];
            $oldStartDate = $project['start_date'];

            // Si no se proporciona nueva fecha, usar la actual
            if (!$newStartDate) {
                $newStartDate = $oldStartDate ?: date('Y-m-d');
            }

            // 2. Registrar en historial ANTES del cambio
            $stmt = $this->db->prepare("
                INSERT INTO project_plan_history 
                (project_id, old_plan_id, new_plan_id, old_start_date, new_start_date, change_reason, changed_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $projectId,
                $oldPlanId,
                $newPlanId,
                $oldStartDate,
                $newStartDate,
                $reason,
                $userId
            ]);

            // 3. Actualizar proyecto con nuevo plan
            $stmt = $this->db->prepare("
                UPDATE projects 
                SET current_plan_id = ?, start_date = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$newPlanId, $newStartDate, $projectId]);

            // 4. Recalcular cuotas
            $recalcResult = $this->installmentGenerator->recalculateInstallments(
                $projectId,
                $newPlanId,
                $newStartDate
            );

            // 5. Log de actividad
            Logger::log('BILLING_PLAN_CHANGED', [
                'project_id' => $projectId,
                'old_plan_id' => $oldPlanId,
                'new_plan_id' => $newPlanId,
                'reason' => $reason,
                'user_id' => $userId
            ]);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Plan cambiado exitosamente',
                'old_plan_id' => $oldPlanId,
                'new_plan_id' => $newPlanId,
                'recalculation' => $recalcResult
            ];

        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Cambia solo la fecha de inicio sin cambiar el plan
     * 
     * @param int $projectId ID del proyecto
     * @param string $newStartDate Nueva fecha de inicio
     * @param int|null $userId Usuario que realiza el cambio
     * @return array Resultado del cambio
     */
    public function changeStartDate($projectId, $newStartDate, $userId = null)
    {
        try {
            $this->db->beginTransaction();

            // Obtener plan actual
            $stmt = $this->db->prepare("SELECT current_plan_id, start_date FROM projects WHERE id = ?");
            $stmt->execute([$projectId]);
            $project = $stmt->fetch();

            if (!$project || !$project['current_plan_id']) {
                throw new \Exception("Proyecto no tiene plan asignado");
            }

            $oldStartDate = $project['start_date'];

            // Registrar en historial
            $stmt = $this->db->prepare("
                INSERT INTO project_plan_history 
                (project_id, old_plan_id, new_plan_id, old_start_date, new_start_date, change_reason, changed_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $projectId,
                $project['current_plan_id'],
                $project['current_plan_id'],
                $oldStartDate,
                $newStartDate,
                'Cambio de fecha de inicio',
                $userId
            ]);

            // Actualizar fecha
            $stmt = $this->db->prepare("
                UPDATE projects 
                SET start_date = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$newStartDate, $projectId]);

            // Recalcular cuotas
            $recalcResult = $this->installmentGenerator->recalculateInstallments(
                $projectId,
                $project['current_plan_id'],
                $newStartDate
            );

            Logger::log('BILLING_START_DATE_CHANGED', [
                'project_id' => $projectId,
                'old_date' => $oldStartDate,
                'new_date' => $newStartDate,
                'user_id' => $userId
            ]);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Fecha de inicio actualizada',
                'recalculation' => $recalcResult
            ];

        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
