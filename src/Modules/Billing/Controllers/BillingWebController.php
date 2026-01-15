<?php

namespace App\Modules\Billing\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use App\Core\Auth;
use PDO;

/**
 * Controlador Web de Billing
 * Maneja las vistas administrativas del módulo
 */
class BillingWebController extends BaseController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Dashboard principal de Billing
     */
    public function index()
    {
        // Verificar permisos
        if (!Auth::isAdmin()) {
            if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 4) {
                $this->redirect('admin/billing/installments');
                return;
            }
            Auth::setFlashError('No tienes permisos para acceder a esta sección', 'error');
            $this->redirect('/admin/dashboard');
            return;
        }

        // Obtener resumen financiero
        $financialSummary = $this->getFinancialSummary();

        // Obtener cuotas próximas a vencer (próximos 30 días)
        $upcomingInstallments = $this->getUpcomingInstallments(30);

        // Obtener cuotas vencidas
        $overdueInstallments = $this->getOverdueInstallments();

        // Obtener actividad reciente
        $recentActivity = $this->getRecentActivity(10);

        // Obtener datos para gráficos
        $chartData = $this->getChartData();

        $this->view('admin.billing.index', [
            'title' => 'Billing Management',
            'financialSummary' => $financialSummary,
            'upcomingInstallments' => $upcomingInstallments,
            'overdueInstallments' => $overdueInstallments,
            'recentActivity' => $recentActivity,
            'chartData' => $chartData
        ]);
    }

    /**
     * Vista de clientes (ahora unificada con usuarios)
     */
    public function clients()
    {
        if (!Auth::isAdmin()) {
            Auth::setFlashError('No tienes permisos para acceder a esta sección', 'error');
            $this->redirect('/admin/dashboard');
            return;
        }

        // Buscamos solo usuarios que tengan el rol de cliente (ID 4)
        $stmt = $this->db->query("
            SELECT u.*,
                   COALESCE(u.public_name, u.username) as name,
                   COUNT(DISTINCT p.id) as projects_count,
                   SUM(CASE WHEN i.status = 'pagada' THEN i.amount ELSE 0 END) as total_paid,
                   SUM(CASE WHEN i.status = 'pendiente' THEN i.amount ELSE 0 END) as total_pending,
                   SUM(CASE WHEN i.status = 'vencida' THEN i.amount ELSE 0 END) as total_overdue
            FROM users u
            LEFT JOIN projects p ON u.id = p.billing_user_id
            LEFT JOIN installments i ON p.id = i.project_id
            WHERE u.role_id = 4
            GROUP BY u.id
            ORDER BY name ASC
        ");
        $clients = $stmt->fetchAll();

        $this->view('admin.billing.clients', [
            'title' => 'Gestión de Clientes (Usuarios)',
            'clients' => $clients
        ]);
    }

    /**
     * Vista de proyectos con billing
     */
    public function projects()
    {
        if (!Auth::isAdmin()) {
            Auth::setFlashError('No tienes permisos para acceder a esta sección', 'error');
            $this->redirect('/admin/dashboard');
            return;
        }

        $stmt = $this->db->query("
            SELECT p.*,
                   COALESCE(u.public_name, u.username) as client_name,
                   u.email as client_email,
                   u.phone as client_phone,
                   u.tax_id as client_tax_id,
                   pp.name as plan_name,
                   pp.frequency as plan_frequency,
                   COUNT(i.id) as total_installments,
                   SUM(CASE WHEN i.status = 'pagada' THEN 1 ELSE 0 END) as paid_installments,
                   SUM(CASE WHEN i.status = 'pendiente' THEN 1 ELSE 0 END) as pending_installments,
                   SUM(CASE WHEN i.status = 'vencida' THEN 1 ELSE 0 END) as overdue_installments
            FROM projects p
            LEFT JOIN users u ON p.billing_user_id = u.id
            LEFT JOIN payment_plans pp ON p.current_plan_id = pp.id
            LEFT JOIN installments i ON p.id = i.project_id
            WHERE p.current_plan_id IS NOT NULL
            GROUP BY p.id
            ORDER BY p.created_at DESC
        ");
        $projects = $stmt->fetchAll();

        // Obtener planes disponibles
        $plans = $this->db->query("SELECT * FROM payment_plans WHERE status = 'active' ORDER BY name")->fetchAll();

        $this->view('admin.billing.projects', [
            'title' => 'Projects with Billing',
            'projects' => $projects,
            'plans' => $plans
        ]);
    }

    /**
     * Vista de cuotas
     */
    public function installments()
    {
        if (!Auth::isAdmin() && $_SESSION['role_id'] != 4) {
            Auth::setFlashError('No tienes permisos para acceder a esta sección', 'error');
            $this->redirect('/admin/dashboard');
            return;
        }

        $filter = $_GET['filter'] ?? 'all';
        $projectId = $_GET['project_id'] ?? null;
        $userId = Auth::getUserId();
        $isAdmin = Auth::isAdmin();

        $sql = "
            SELECT i.*,
                   p.name as project_name,
                   COALESCE(u.public_name, u.username) as client_name,
                   pp.name as plan_name,
                   (SELECT SUM(amount) FROM payments WHERE installment_id = i.id) as paid_amount
            FROM installments i
            INNER JOIN projects p ON i.project_id = p.id
            LEFT JOIN users u ON p.billing_user_id = u.id
            LEFT JOIN payment_plans pp ON i.plan_id = pp.id
            WHERE 1=1
        ";

        if (!$isAdmin) {
            $sql .= " AND p.billing_user_id = " . (int) $userId;
        }

        if ($filter === 'upcoming') {
            $sql .= " AND i.status = 'pendiente' AND i.due_date BETWEEN DATE('now') AND DATE('now', '+30 days')";
        } elseif ($filter === 'overdue') {
            $sql .= " AND i.status = 'vencida'";
        } elseif ($filter === 'paid') {
            $sql .= " AND i.status = 'pagada'";
        } elseif ($filter === 'pending') {
            $sql .= " AND i.status = 'pendiente'";
        }

        if ($projectId) {
            $sql .= " AND i.project_id = " . (int) $projectId;
        }

        $sql .= " ORDER BY i.due_date ASC LIMIT 100";

        $installments = $this->db->query($sql)->fetchAll();

        // Obtener proyectos para el filtro (solo los del cliente si no es admin)
        $projectsSql = "SELECT id, name FROM projects WHERE current_plan_id IS NOT NULL";
        if (!$isAdmin) {
            $projectsSql .= " AND billing_user_id = " . (int) $userId;
        }
        $projectsSql .= " ORDER BY name";
        $projects = $this->db->query($projectsSql)->fetchAll();

        $this->view('admin.billing.installments', [
            'title' => 'Installments Management',
            'installments' => $installments,
            'projects' => $projects,
            'currentFilter' => $filter,
            'currentProjectId' => $projectId
        ]);
    }

    /**
     * Vista de planes de pago
     */
    public function plans()
    {
        if (!Auth::isAdmin()) {
            Auth::setFlashError('No tienes permisos para acceder a esta sección', 'error');
            $this->redirect('/admin/dashboard');
            return;
        }

        // Obtener todos los planes con conteo de proyectos
        $stmt = $this->db->query("
            SELECT pp.*,
                   COUNT(p.id) as projects_count
            FROM payment_plans pp
            LEFT JOIN projects p ON pp.id = p.current_plan_id
            GROUP BY pp.id
            ORDER BY pp.status DESC, pp.name ASC
        ");
        $plans = $stmt->fetchAll();

        $this->view('admin.billing.plans', [
            'title' => 'Payment Plans Management',
            'plans' => $plans
        ]);
    }

    /**
     * Vista de reportes financieros
     */
    public function reports()
    {
        if (!Auth::isAdmin()) {
            Auth::setFlashError('No tienes permisos para acceder a esta sección', 'error');
            $this->redirect('/admin/dashboard');
            return;
        }

        $period = $_GET['period'] ?? 'current_year';

        // Resumen general
        $summary = $this->getReportSummary($period);

        // Comparativa de ingresos
        $incomeComparison = $this->getIncomeComparison();

        // Top clientes
        $topClients = $this->getTopClients(10);

        // Proyección
        $forecast = $this->getForecast(6);

        $this->view('admin.billing.reports', [
            'title' => 'Financial Reports',
            'summary' => $summary,
            'incomeComparison' => $incomeComparison,
            'topClients' => $topClients,
            'forecast' => $forecast
        ]);
    }

    /**
     * Vista de historial de pagos
     */
    public function payments()
    {
        if (!Auth::isAdmin() && $_SESSION['role_id'] != 4) {
            Auth::setFlashError('No tienes permisos para acceder a esta sección', 'error');
            $this->redirect('/admin/dashboard');
            return;
        }

        $userId = Auth::getUserId();
        $isAdmin = Auth::isAdmin();

        $sql = "
            SELECT pay.*,
                   i.installment_number,
                   p.name as project_name,
                   COALESCE(u.public_name, u.username) as client_name,
                   u.id as client_id
            FROM payments pay
            INNER JOIN installments i ON pay.installment_id = i.id
            INNER JOIN projects p ON i.project_id = p.id
            LEFT JOIN users u ON p.billing_user_id = u.id
            WHERE 1=1
        ";

        if (!$isAdmin) {
            $sql .= " AND p.billing_user_id = " . (int) $userId;
        }

        $sql .= " ORDER BY pay.created_at DESC LIMIT 200";
        $stmt = $this->db->query($sql);
        $payments = $stmt->fetchAll();

        // Resumen de pagos
        $summaryStmt = $this->db->query("
            SELECT 
                COUNT(*) as total_payments,
                SUM(amount) as total_received,
                AVG(amount) as average_payment,
                (SELECT COUNT(*) FROM payments WHERE payment_date >= DATE('now', 'start of month')) as month_payments,
                (SELECT SUM(amount) FROM payments WHERE payment_date >= DATE('now', 'start of month')) as month_received,
                (SELECT payment_date FROM payments ORDER BY payment_date DESC LIMIT 1) as last_payment_date,
                (SELECT amount FROM payments ORDER BY payment_date DESC LIMIT 1) as last_payment_amount
            FROM payments
        ");
        $summary = $summaryStmt->fetch();

        // Obtener clientes para filtro
        $clients = $this->db->query("SELECT id, name FROM clients ORDER BY name")->fetchAll();

        $this->view('admin.billing.payments', [
            'title' => 'Payment History',
            'payments' => $payments,
            'summary' => $summary,
            'clients' => $clients
        ]);
    }



    /**
     * Obtiene el resumen financiero
     */
    private function getFinancialSummary()
    {
        $stmt = $this->db->query("
            SELECT 
                COUNT(CASE WHEN status = 'pagada' THEN 1 END) as paid_count,
                SUM(CASE WHEN status = 'pagada' THEN amount ELSE 0 END) as paid_amount,
                COUNT(CASE WHEN status = 'pendiente' THEN 1 END) as pending_count,
                SUM(CASE WHEN status = 'pendiente' THEN amount ELSE 0 END) as pending_amount,
                COUNT(CASE WHEN status = 'vencida' THEN 1 END) as overdue_count,
                SUM(CASE WHEN status = 'vencida' THEN amount ELSE 0 END) as overdue_amount
            FROM installments
        ");

        return $stmt->fetch();
    }

    /**
     * Obtiene cuotas próximas a vencer
     */
    private function getUpcomingInstallments($days = 30)
    {
        $targetDate = date('Y-m-d', strtotime("+{$days} days"));

        $stmt = $this->db->prepare("
            SELECT i.*, p.name as project_name, COALESCE(u.public_name, u.username) as client_name
            FROM installments i
            INNER JOIN projects p ON i.project_id = p.id
            LEFT JOIN users u ON p.billing_user_id = u.id
            WHERE i.status = 'pendiente'
            AND i.due_date BETWEEN DATE('now') AND ?
            ORDER BY i.due_date ASC
            LIMIT 10
        ");
        $stmt->execute([$targetDate]);

        return $stmt->fetchAll();
    }

    /**
     * Obtiene cuotas vencidas
     */
    private function getOverdueInstallments()
    {
        $stmt = $this->db->query("
            SELECT i.*, p.name as project_name, COALESCE(u.public_name, u.username) as client_name,
                   JULIANDAY('now') - JULIANDAY(i.due_date) as days_overdue
            FROM installments i
            INNER JOIN projects p ON i.project_id = p.id
            LEFT JOIN users u ON p.billing_user_id = u.id
            WHERE i.status = 'vencida'
            ORDER BY i.due_date ASC
            LIMIT 10
        ");

        return $stmt->fetchAll();
    }

    /**
     * Obtiene actividad reciente
     */
    private function getRecentActivity($limit = 10)
    {
        $stmt = $this->db->prepare("
            SELECT 
                'payment' as type,
                p.payment_date as date,
                p.amount,
                i.installment_number,
                pr.name as project_name,
                COALESCE(u.public_name, u.username) as client_name
            FROM payments p
            INNER JOIN installments i ON p.installment_id = i.id
            INNER JOIN projects pr ON i.project_id = pr.id
            LEFT JOIN users u ON pr.billing_user_id = u.id
            ORDER BY p.payment_date DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);

        return $stmt->fetchAll();
    }

    /**
     * Obtiene datos para gráficos
     */
    private function getChartData()
    {
        // Ingresos por mes (últimos 6 meses)
        $incomeByMonth = $this->db->query("
            SELECT 
                strftime('%Y-%m', payment_date) as month,
                SUM(amount) as total
            FROM payments
            WHERE payment_date >= DATE('now', '-6 months')
            GROUP BY month
            ORDER BY month ASC
        ")->fetchAll();

        // Cuotas por estado
        $installmentsByStatus = $this->db->query("
            SELECT 
                status,
                COUNT(*) as count,
                SUM(amount) as total
            FROM installments
            GROUP BY status
        ")->fetchAll();

        return [
            'income_by_month' => $incomeByMonth,
            'installments_by_status' => $installmentsByStatus
        ];
    }

    /**
     * Obtiene resumen para reportes
     */
    private function getReportSummary($period)
    {
        $stmt = $this->db->query("
            SELECT 
                SUM(CASE WHEN i.status = 'pagada' THEN i.amount ELSE 0 END) as total_income,
                SUM(CASE WHEN i.status = 'pendiente' THEN i.amount ELSE 0 END) as pending_amount,
                COUNT(DISTINCT p.id) as active_projects,
                AVG(i.amount) as average_ticket
            FROM installments i
            INNER JOIN projects p ON i.project_id = p.id
        ");

        return $stmt->fetch();
    }

    /**
     * Obtiene comparativa de ingresos
     */
    private function getIncomeComparison()
    {
        $currentYear = date('Y');
        $previousYear = $currentYear - 1;

        $labels = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $currentYearData = [];
        $previousYearData = [];

        for ($month = 1; $month <= 12; $month++) {
            // Año actual
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(amount), 0) as total
                FROM payments
                WHERE strftime('%Y', payment_date) = ? AND strftime('%m', payment_date) = ?
            ");
            $stmt->execute([$currentYear, sprintf('%02d', $month)]);
            $currentYearData[] = (float) $stmt->fetchColumn();

            // Año anterior
            $stmt->execute([$previousYear, sprintf('%02d', $month)]);
            $previousYearData[] = (float) $stmt->fetchColumn();
        }

        return [
            'labels' => $labels,
            'current_year' => $currentYearData,
            'previous_year' => $previousYearData
        ];
    }

    /**
     * Obtiene top clientes
     */
    private function getTopClients($limit = 10)
    {
        $stmt = $this->db->prepare("
            SELECT c.*,
                   COUNT(DISTINCT p.id) as projects_count,
                   SUM(CASE WHEN i.status = 'pagada' THEN i.amount ELSE 0 END) as total_paid,
                   SUM(CASE WHEN i.status = 'pendiente' THEN i.amount ELSE 0 END) as total_pending
            FROM clients c
            LEFT JOIN projects p ON c.id = p.client_id
            LEFT JOIN installments i ON p.id = i.project_id
            GROUP BY c.id
            ORDER BY (total_paid + total_pending) DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);

        return $stmt->fetchAll();
    }

    /**
     * Obtiene proyección de ingresos
     */
    private function getForecast($months = 6)
    {
        $labels = [];
        $amounts = [];

        for ($i = 0; $i < $months; $i++) {
            $date = date('Y-m', strtotime("+{$i} months"));
            $labels[] = date('M Y', strtotime("+{$i} months"));

            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(amount), 0) as total
                FROM installments
                WHERE strftime('%Y-%m', due_date) = ? AND status = 'pendiente'
            ");
            $stmt->execute([$date]);
            $amounts[] = (float) $stmt->fetchColumn();
        }

        return [
            'labels' => $labels,
            'amounts' => $amounts
        ];
    }

    /**
     * Vista de catálogo de servicios
     */
    public function services()
    {
        if (!Auth::isAdmin()) {
            Auth::setFlashError('No tienes permisos para acceder a esta sección', 'error');
            $this->redirect('/admin/dashboard');
            return;
        }

        $stmt = $this->db->query("SELECT * FROM billing_services WHERE status = 'active' ORDER BY name ASC");
        $services = $stmt->fetchAll();

        $this->view('admin.billing.services', [
            'title' => 'Catálogo de Servicios',
            'services' => $services
        ]);
    }
}
