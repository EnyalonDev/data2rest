<?php

namespace App\Modules\Billing\Services;

use App\Core\Database;
use PDO;

/**
 * Generador de Cuotas por Plan de Pago
 * Responsable de calcular y crear las cuotas según el plan seleccionado
 */
class InstallmentGenerator
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Calcula el monto total de un proyecto basado en sus servicios asociados
     * 
     * @param int $projectId
     * @param string $frequency ('monthly', 'yearly', 'unico')
     * @return float
     */
    private function getProjectTotalAmount($projectId, $frequency)
    {
        $stmt = $this->db->prepare("
            SELECT ps.custom_price, ps.quantity, ps.billing_period, 
                   bs.price_monthly, bs.price_yearly, bs.price_one_time, bs.price
            FROM project_services ps
            JOIN billing_services bs ON ps.service_id = bs.id
            WHERE ps.project_id = ?
        ");
        $stmt->execute([$projectId]);
        $services = $stmt->fetchAll();

        if (empty($services)) {
            return 0;
        }

        $total = 0;
        foreach ($services as $service) {
            $basePrice = 0;
            $period = $service['billing_period'] ?? 'monthly';

            if ($period === 'monthly') {
                $basePrice = $service['price_monthly'];
            } elseif ($period === 'yearly') {
                $basePrice = $service['price_yearly'];
            } elseif ($period === 'unico') {
                $basePrice = $service['price_one_time'];
            } else {
                $basePrice = $service['price'];
            }

            $price = ($service['custom_price'] !== null) ? $service['custom_price'] : $basePrice;
            $total += $price * ($service['quantity'] ?: 1);
        }

        return $total;
    }

    /**
     * Genera cuotas para un proyecto según su plan y servicios
     */
    public function generateInstallments($projectId, $planId, $startDate)
    {
        $stmt = $this->db->prepare("SELECT * FROM payment_plans WHERE id = ?");
        $stmt->execute([$planId]);
        $plan = $stmt->fetch();

        if (!$plan) {
            throw new \Exception("Plan de pago no encontrado");
        }

        // Obtener monto de servicios
        $servicesTotal = $this->getProjectTotalAmount($projectId, $plan['frequency']);
        $totalAmount = $servicesTotal;
        $installmentsCount = $plan['installments'] ?? 1;

        $installments = [];
        $baseDate = new \DateTime($startDate);
        $installmentAmount = $totalAmount; // In this system, each installment is usually the full service total per period

        // Wait, if it's divided in installments (like 2 cuotas), we divide the total
        if ($installmentsCount > 1 && ($plan['frequency'] === 'unico' || $plan['frequency'] === 'monthly')) {
            // If frequency is 'unico' but has 2 installments, we divide. 
            // If frequency is 'monthly' and has 12 installments, each month is the total or the total/12?
            // Usually monthly subscription each installment is the monthly price.
            // If it's a fixed project divided in months, then we divide.
            // Let's assume if installments > 1 AND frequency != 'monthly/yearly', we divide.
            // Or if it's a contract duration situation.
            if ($plan['frequency'] === 'unico' || $plan['frequency'] === 'especial') {
                $installmentAmount = $totalAmount / $installmentsCount;
            }
        }

        for ($i = 1; $i <= $installmentsCount; $i++) {
            $dueDate = clone $baseDate;

            if ($plan['frequency'] === 'monthly' || $plan['frequency'] === 'mensual') {
                $dueDate->modify('+' . ($i - 1) . ' months');
            } elseif ($plan['frequency'] === 'yearly' || $plan['frequency'] === 'anual') {
                $dueDate->modify('+' . ($i - 1) . ' years');
            }

            $installments[] = [
                'project_id' => $projectId,
                'plan_id' => $planId,
                'installment_number' => $i,
                'due_date' => $dueDate->format('Y-m-d'),
                'amount' => $installmentAmount,
                'status' => 'pendiente'
            ];
        }

        $stmt = $this->db->prepare("
            INSERT INTO installments (project_id, plan_id, installment_number, due_date, amount, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($installments as $installment) {
            $stmt->execute([
                $installment['project_id'],
                $installment['plan_id'],
                $installment['installment_number'],
                $installment['due_date'],
                $installment['amount'],
                $installment['status']
            ]);
        }

        return $installments;
    }

    /**
     * Recalcula cuotas futuras
     */
    public function recalculateInstallments($projectId, $newPlanId, $newStartDate)
    {
        $stmt = $this->db->prepare("SELECT * FROM installments WHERE project_id = ? AND status = 'pagada' ORDER BY installment_number ASC");
        $stmt->execute([$projectId]);
        $paidInstallments = $stmt->fetchAll();

        $this->db->prepare("UPDATE installments SET status = 'cancelada' WHERE project_id = ? AND status != 'pagada'")->execute([$projectId]);

        $lastPaidNumber = !empty($paidInstallments) ? max(array_column($paidInstallments, 'installment_number')) : 0;

        $stmt = $this->db->prepare("SELECT * FROM payment_plans WHERE id = ?");
        $stmt->execute([$newPlanId]);
        $newPlan = $stmt->fetch();

        if (!$newPlan)
            throw new \Exception("Plan no encontrado");

        $servicesTotal = $this->getProjectTotalAmount($projectId, $newPlan['frequency']);
        $totalAmount = ($servicesTotal > 0) ? $servicesTotal : ($newPlan['amount'] ?? 0);
        $installmentsCount = $newPlan['installments'] ?? 1;
        $installmentAmount = ($newPlan['frequency'] === 'unico') ? ($totalAmount / $installmentsCount) : $totalAmount;

        $newInstallments = [];
        $baseDate = new \DateTime($newStartDate);

        if ($lastPaidNumber > 0) {
            $freq = $newPlan['frequency'];
            if ($freq === 'monthly' || $freq === 'mensual')
                $baseDate->modify('+' . $lastPaidNumber . ' months');
            elseif ($freq === 'yearly' || $freq === 'anual')
                $baseDate->modify('+' . $lastPaidNumber . ' years');
        }

        $remaining = $installmentsCount - $lastPaidNumber;

        for ($i = 1; $i <= $remaining; $i++) {
            $dueDate = clone $baseDate;
            $freq = $newPlan['frequency'];
            if ($freq === 'monthly' || $freq === 'mensual')
                $dueDate->modify('+' . ($i - 1) . ' months');
            elseif ($freq === 'yearly' || $freq === 'anual')
                $dueDate->modify('+' . ($i - 1) . ' years');

            $newInstallments[] = [
                'project_id' => $projectId,
                'plan_id' => $newPlanId,
                'installment_number' => $lastPaidNumber + $i,
                'due_date' => $dueDate->format('Y-m-d'),
                'amount' => $installmentAmount,
                'status' => 'pendiente'
            ];
        }

        $stmt = $this->db->prepare("INSERT INTO installments (project_id, plan_id, installment_number, due_date, amount, status) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($newInstallments as $inst) {
            $stmt->execute([$inst['project_id'], $inst['plan_id'], $inst['installment_number'], $inst['due_date'], $inst['amount'], $inst['status']]);
        }

        return ['new_count' => count($newInstallments)];
    }
}
