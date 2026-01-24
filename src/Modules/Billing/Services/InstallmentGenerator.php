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
     * Genera cuotas para un proyecto según su plan y servicios (Lógica Temporal)
     */
    public function generateInstallments($projectId, $planId, $startDate)
    {
        $stmt = $this->db->prepare("SELECT * FROM payment_plans WHERE id = ?");
        $stmt->execute([$planId]);
        $plan = $stmt->fetch();

        if (!$plan) {
            throw new \Exception("Plan de pago no encontrado");
        }

        // Obtener servicios del proyecto
        $stmt = $this->db->prepare("
            SELECT ps.custom_price, ps.quantity, ps.billing_period, 
                   bs.price_monthly, bs.price_yearly, bs.price_one_time, bs.price, bs.name
            FROM project_services ps
            JOIN billing_services bs ON ps.service_id = bs.id
            WHERE ps.project_id = ?
        ");
        $stmt->execute([$projectId]);
        $services = $stmt->fetchAll();

        // Configuración de la línea de tiempo
        $installmentsCount = $plan['installments'] ?? 1;
        $baseDate = new \DateTime($startDate);

        // Determinar frecuencia base para avanzar el calendario (si es 'unico', asumimos mensual para distribución si hay >1 cuota, o anual si es largo plazo)
        // Por defecto, usaremos la frecuencia del PLAN para marcar los Hitos de cobro.
        $frequency = $plan['frequency'];

        $installments = [];

        for ($i = 1; $i <= $installmentsCount; $i++) {
            $currentDate = clone $baseDate;

            // Avanzar fecha según la frecuencia del plan
            if ($i > 1) {
                if ($frequency === 'monthly' || $frequency === 'mensual') {
                    $currentDate->modify('+' . ($i - 1) . ' months');
                } elseif ($frequency === 'yearly' || $frequency === 'anual') {
                    $currentDate->modify('+' . ($i - 1) . ' years');
                }
            }

            // Calcular monto para ESTA cuota específica
            $installmentAmount = 0;

            foreach ($services as $service) {
                $servicePeriod = $service['billing_period'] ?? 'monthly';
                $price = ($service['custom_price'] !== null) ? $service['custom_price'] :
                    ($servicePeriod === 'monthly' ? $service['price_monthly'] :
                        ($servicePeriod === 'yearly' ? $service['price_yearly'] : $service['price_one_time']));

                $price = $price ?: $service['price']; // Fallback
                $totalService = $price * ($service['quantity'] ?: 1);

                // Lógica de Cobro Temporal:

                // CASO 1: Pago Único (Solo se cobra en la Cuota 1)
                if ($servicePeriod === 'unico' || $servicePeriod === 'one_time') {
                    if ($i === 1) {
                        $installmentAmount += $totalService;
                    }
                }

                // CASO 2: Mensual
                // - Si el Plan es MENSUAL: Se cobra en TODAS las cuotas.
                // - Si el Plan es ANUAL: Se cobra 12 meses juntos en la Cuota 1? O solo mes 1?
                //   Requerimiento: "Cliente paga web anual y soporte mensual". 
                //   Si el plan es ANUAL (1 cuota), debería cobrar 12 meses de soporte? O generar 12 cuotas?
                //   Si el sistema genera N cuotas según el plan, y el plan es ANUAL (1 cuota), entonces solo hay 1 fecha de cobro.
                //   Para soportar cobro mensual real, el PLAN debe ser MENSUAL o tener N cuotas.
                //   Asumiremos: Si el servicio coincice con la frecuencia o es más frecuente, se cobra.
                elseif ($servicePeriod === 'monthly' || $servicePeriod === 'mensual') {
                    // Si el plan es mensual, se cobra cada mes.
                    if ($frequency === 'monthly' || $frequency === 'mensual') {
                        $installmentAmount += $totalService;
                    }
                    // Si el plan es anual (1 cuota por año), cobramos 12 meses de soporte por adelantado?
                    // O cobramos solo el primer mes?
                    // Lo lógico en plan anual es cobrar todo el año.
                    elseif ($frequency === 'yearly' || $frequency === 'anual') {
                        $installmentAmount += ($totalService * 12);
                    }
                }

                // CASO 3: Anual
                elseif ($servicePeriod === 'yearly' || $servicePeriod === 'anual') {
                    // Se cobra en la Cuota 1.
                    // Y si hay más años (Plan de 24 meses / 2 cuotas anuales), se cobra en Cuota 2 (Mes 13)

                    if ($frequency === 'yearly' || $frequency === 'anual') {
                        $installmentAmount += $totalService; // 1 año, 1 cobro
                    } elseif ($frequency === 'monthly' || $frequency === 'mensual') {
                        // Plan mensual pero servicio anual (ej. Hosting en un plan de mantenimiento mensual)
                        // Se cobra el total anual en la Cuota 1, y luego en la Cuota 13 (si existe)
                        if (($i - 1) % 12 === 0) {
                            $installmentAmount += $totalService;
                        }
                    }
                }
            }

            // Si el Plan define dividir un monto fijo (ej. Financiación de proyecto "único" en 3 cuotas)
            // Esta lógica choca con la de servicios recurrentes.
            // Para "Pack Cero Estrés" (mix), asumimos que NO es una financiación dividida, sino cobros por servicio.
            // Pero mantenemos soporte legacy: Si no hay servicios detectados o monto es 0, usar lógica antigua?
            // O si frecuencia es 'unico' y hay cuotas, dividir los 'unicos'.

            if ($frequency === 'unico' && $installmentsCount > 1) {
                // Recalcular solo los items ÚNICOS para dividirlos
                // Esto complica. Simplifiquemos: 
                // Si es mix, asumimos facturación directa.
            }

            if ($installmentAmount > 0) {
                $installments[] = [
                    'project_id' => $projectId,
                    'plan_id' => $planId,
                    'installment_number' => $i,
                    'due_date' => $currentDate->format('Y-m-d'),
                    'amount' => $installmentAmount,
                    'status' => 'pendiente'
                ];
            }
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

        // Obtener servicios del proyecto
        $stmt = $this->db->prepare("
            SELECT ps.custom_price, ps.quantity, ps.billing_period, 
                   bs.price_monthly, bs.price_yearly, bs.price_one_time, bs.price, bs.name
            FROM project_services ps
            JOIN billing_services bs ON ps.service_id = bs.id
            WHERE ps.project_id = ?
        ");
        $stmt->execute([$projectId]);
        $services = $stmt->fetchAll();

        $installmentsCount = $newPlan['installments'] ?? 1;

        $newInstallments = [];
        $baseDate = new \DateTime($newStartDate);

        // Adjust base date strictly based on the Plan Frequency logic to continue the schedule
        if ($lastPaidNumber > 0) {
            $freq = $newPlan['frequency'];
            if ($freq === 'monthly' || $freq === 'mensual')
                $baseDate->modify('+' . $lastPaidNumber . ' months');
            elseif ($freq === 'yearly' || $freq === 'anual')
                $baseDate->modify('+' . $lastPaidNumber . ' years');
        }

        $remaining = $installmentsCount - $lastPaidNumber;

        for ($i = 1; $i <= $remaining; $i++) {
            $installmentNumber = $lastPaidNumber + $i;
            $dueDate = clone $baseDate;

            $freq = $newPlan['frequency'];
            // Advance date relative to the adjusted baseDate
            if ($i > 1) {
                if ($freq === 'monthly' || $freq === 'mensual')
                    $dueDate->modify('+' . ($i - 1) . ' months');
                elseif ($freq === 'yearly' || $freq === 'anual')
                    $dueDate->modify('+' . ($i - 1) . ' years');
            }

            // --- Timeline Calculation Logic (Same as generateInstallments) ---
            $installmentAmount = 0;

            foreach ($services as $service) {
                $servicePeriod = $service['billing_period'] ?? 'monthly';
                $price = ($service['custom_price'] !== null) ? $service['custom_price'] :
                    ($servicePeriod === 'monthly' ? $service['price_monthly'] :
                        ($servicePeriod === 'yearly' ? $service['price_yearly'] : $service['price_one_time']));

                $price = $price ?: $service['price'];
                $totalService = $price * ($service['quantity'] ?: 1);

                // Check if this service should be charged in THIS installment number ($installmentNumber)

                // 1. One-time
                if ($servicePeriod === 'unico' || $servicePeriod === 'one_time') {
                    if ($installmentNumber === 1) {
                        $installmentAmount += $totalService;
                    }
                }
                // 2. Monthly
                elseif ($servicePeriod === 'monthly' || $servicePeriod === 'mensual') {
                    if ($freq === 'monthly' || $freq === 'mensual') {
                        $installmentAmount += $totalService;
                    } elseif ($freq === 'yearly' || $freq === 'anual') {
                        $installmentAmount += ($totalService * 12);
                    }
                }
                // 3. Yearly
                elseif ($servicePeriod === 'yearly' || $servicePeriod === 'anual') {
                    if ($freq === 'yearly' || $freq === 'anual') {
                        $installmentAmount += $totalService;
                    } elseif ($freq === 'monthly' || $freq === 'mensual') {
                        // Charged in month 1, 13, 25...
                        if (($installmentNumber - 1) % 12 === 0) {
                            $installmentAmount += $totalService;
                        }
                    }
                }
            }

            if ($installmentAmount > 0) {
                $newInstallments[] = [
                    'project_id' => $projectId,
                    'plan_id' => $newPlanId,
                    'installment_number' => $installmentNumber,
                    'due_date' => $dueDate->format('Y-m-d'),
                    'amount' => $installmentAmount,
                    'status' => 'pendiente'
                ];
            }
        }

        $stmt = $this->db->prepare("INSERT INTO installments (project_id, plan_id, installment_number, due_date, amount, status) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($newInstallments as $inst) {
            $stmt->execute([$inst['project_id'], $inst['plan_id'], $inst['installment_number'], $inst['due_date'], $inst['amount'], $inst['status']]);
        }

        return ['new_count' => count($newInstallments)];
    }
}
