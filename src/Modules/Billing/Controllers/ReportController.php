<?php

namespace App\Modules\Billing\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use PDO;

/**
 * Controlador de Reportes Financieros
 */
class ReportController extends BaseController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * GET /api/billing/reports/financial-summary
     * Resumen financiero general
     */
    public function financialSummary()
    {
        // Ingresos totales (cuotas pagadas)
        $paidStmt = $this->db->query("
            SELECT 
                COUNT(*) as total_paid_installments,
                SUM(amount) as total_paid_amount
            FROM installments
            WHERE status = 'pagada'
        ");
        $paidData = $paidStmt->fetch();

        // Cuotas pendientes
        $pendingStmt = $this->db->query("
            SELECT 
                COUNT(*) as total_pending_installments,
                SUM(amount) as total_pending_amount
            FROM installments
            WHERE status = 'pendiente'
        ");
        $pendingData = $pendingStmt->fetch();

        // Cuotas vencidas
        $overdueStmt = $this->db->query("
            SELECT 
                COUNT(*) as total_overdue_installments,
                SUM(amount) as total_overdue_amount
            FROM installments
            WHERE status = 'vencida'
        ");
        $overdueData = $overdueStmt->fetch();

        // Proyectos activos con billing
        $projectsStmt = $this->db->query("
            SELECT COUNT(*) as total_projects
            FROM projects
            WHERE current_plan_id IS NOT NULL
            AND billing_status = 'active'
        ");
        $projectsData = $projectsStmt->fetch();

        $this->json([
            'success' => true,
            'data' => [
                'paid' => [
                    'installments' => (int) $paidData['total_paid_installments'],
                    'amount' => (float) $paidData['total_paid_amount']
                ],
                'pending' => [
                    'installments' => (int) $pendingData['total_pending_installments'],
                    'amount' => (float) $pendingData['total_pending_amount']
                ],
                'overdue' => [
                    'installments' => (int) $overdueData['total_overdue_installments'],
                    'amount' => (float) $overdueData['total_overdue_amount']
                ],
                'active_projects' => (int) $projectsData['total_projects']
            ]
        ]);
    }

    /**
     * GET /api/billing/reports/income-comparison
     * Comparación de ingresos reales vs proyectados
     */
    public function incomeComparison()
    {
        $startDate = $_GET['start_date'] ?? date('Y-m-01'); // Primer día del mes actual
        $endDate = $_GET['end_date'] ?? date('Y-m-t'); // Último día del mes actual

        // Ingresos reales (pagos efectivos en el rango)
        $realStmt = $this->db->prepare("
            SELECT 
                COUNT(*) as payments_count,
                SUM(p.amount) as total_amount
            FROM payments p
            WHERE DATE(p.payment_date) BETWEEN ? AND ?
        ");
        $realStmt->execute([$startDate, $endDate]);
        $realIncome = $realStmt->fetch();

        // Ingresos proyectados (cuotas que debían pagarse en el rango)
        $projectedStmt = $this->db->prepare("
            SELECT 
                COUNT(*) as installments_count,
                SUM(amount) as total_amount
            FROM installments
            WHERE due_date BETWEEN ? AND ?
        ");
        $projectedStmt->execute([$startDate, $endDate]);
        $projectedIncome = $projectedStmt->fetch();

        // Tasa de cobro
        $collectionRate = 0;
        if ($projectedIncome['total_amount'] > 0) {
            $collectionRate = ($realIncome['total_amount'] / $projectedIncome['total_amount']) * 100;
        }

        $this->json([
            'success' => true,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'data' => [
                'real_income' => [
                    'payments' => (int) $realIncome['payments_count'],
                    'amount' => (float) $realIncome['total_amount']
                ],
                'projected_income' => [
                    'installments' => (int) $projectedIncome['installments_count'],
                    'amount' => (float) $projectedIncome['total_amount']
                ],
                'collection_rate' => round($collectionRate, 2),
                'difference' => (float) ($realIncome['total_amount'] - $projectedIncome['total_amount'])
            ]
        ]);
    }

    /**
     * GET /api/billing/reports/upcoming-installments
     * Cuotas próximas a vencer (calendario de cobranzas)
     */
    public function upcomingInstallments()
    {
        $days = $_GET['days'] ?? 30;
        $groupBy = $_GET['group_by'] ?? 'date'; // date, project, client

        $targetDate = date('Y-m-d', strtotime("+{$days} days"));

        $stmt = $this->db->prepare("
            SELECT 
                i.due_date,
                i.amount,
                i.installment_number,
                p.name as project_name,
                COALESCE(u.public_name, u.username) as client_name,
                pp.name as plan_name
            FROM installments i
            INNER JOIN projects p ON i.project_id = p.id
            LEFT JOIN users u ON p.billing_user_id = u.id
            LEFT JOIN payment_plans pp ON i.plan_id = pp.id
            WHERE i.status = 'pendiente'
            AND i.due_date BETWEEN DATE('now') AND ?
            ORDER BY i.due_date ASC
        ");
        $stmt->execute([$targetDate]);
        $installments = $stmt->fetchAll();

        // Agrupar según el parámetro
        $grouped = [];
        $total = 0;

        foreach ($installments as $inst) {
            $total += $inst['amount'];

            if ($groupBy === 'date') {
                $key = $inst['due_date'];
            } elseif ($groupBy === 'project') {
                $key = $inst['project_name'];
            } elseif ($groupBy === 'client') {
                $key = $inst['client_name'] ?: 'Sin cliente';
            } else {
                $key = 'all';
            }

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'items' => [],
                    'total_amount' => 0,
                    'count' => 0
                ];
            }

            $grouped[$key]['items'][] = $inst;
            $grouped[$key]['total_amount'] += $inst['amount'];
            $grouped[$key]['count']++;
        }

        $this->json([
            'success' => true,
            'period_days' => (int) $days,
            'group_by' => $groupBy,
            'total_amount' => $total,
            'total_installments' => count($installments),
            'data' => $grouped
        ]);
    }

    /**
     * GET /api/billing/reports/client-summary/{clientId}
     * Resumen financiero de un cliente específico
     */
    public function clientSummary($clientId)
    {
        // Verificar que el cliente (usuario) existe
        $clientStmt = $this->db->prepare("SELECT id, username, public_name, email FROM users WHERE id = ?");
        $clientStmt->execute([$clientId]);
        $client = $clientStmt->fetch();

        if (!$client) {
            $this->json(['error' => 'Cliente no encontrado'], 404);
        }

        // Proyectos del cliente
        $projectsStmt = $this->db->prepare("
            SELECT p.*, pp.name as plan_name
            FROM projects p
            LEFT JOIN payment_plans pp ON p.current_plan_id = pp.id
            WHERE p.billing_user_id = ?
        ");
        $projectsStmt->execute([$clientId]);
        $projects = $projectsStmt->fetchAll();

        // Estadísticas financieras
        $statsStmt = $this->db->prepare("
            SELECT 
                COUNT(CASE WHEN i.status = 'pagada' THEN 1 END) as paid_count,
                SUM(CASE WHEN i.status = 'pagada' THEN i.amount ELSE 0 END) as paid_amount,
                COUNT(CASE WHEN i.status = 'pendiente' THEN 1 END) as pending_count,
                SUM(CASE WHEN i.status = 'pendiente' THEN i.amount ELSE 0 END) as pending_amount,
                COUNT(CASE WHEN i.status = 'vencida' THEN 1 END) as overdue_count,
                SUM(CASE WHEN i.status = 'vencida' THEN i.amount ELSE 0 END) as overdue_amount
            FROM installments i
            INNER JOIN projects p ON i.project_id = p.id
            WHERE p.billing_user_id = ?
        ");
        $statsStmt->execute([$clientId]);
        $stats = $statsStmt->fetch();

        $this->json([
            'success' => true,
            'client' => $client,
            'projects' => $projects,
            'financial_summary' => [
                'paid' => [
                    'count' => (int) $stats['paid_count'],
                    'amount' => (float) $stats['paid_amount']
                ],
                'pending' => [
                    'count' => (int) $stats['pending_count'],
                    'amount' => (float) $stats['pending_amount']
                ],
                'overdue' => [
                    'count' => (int) $stats['overdue_count'],
                    'amount' => (float) $stats['overdue_amount']
                ]
            ]
        ]);
    }
}
