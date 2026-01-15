<?php

namespace App\Modules\Projects;

use App\Core\Auth;
use App\Core\Database;
use App\Core\BaseController;
use App\Core\PlanManager;
use App\Modules\Billing\Services\InstallmentGenerator;
use PDO;
use Exception;

/**
 * ProjectController
 * Manages the lifecycle of projects, user assignments, and subscription plans.
 */
class ProjectController extends BaseController
{
    public function __construct()
    {
        Auth::requireLogin();
    }

    /**
     * Lists all projects (Admin) or projects assigned to user.
     */
    public function index()
    {
        $db = Database::getInstance()->getConnection();

        if (Auth::isAdmin()) {
            $stmt = $db->query("SELECT p.*, pp.plan_type, pp.next_billing_date 
                               FROM projects p 
                               LEFT JOIN project_plans pp ON p.id = pp.project_id 
                               ORDER BY p.created_at DESC");
        } else {
            $userId = $_SESSION['user_id'];
            $stmt = $db->prepare("SELECT p.*, pp.plan_type, pp.next_billing_date 
                                 FROM projects p 
                                 JOIN project_users pu ON p.id = pu.project_id 
                                 LEFT JOIN project_plans pp ON p.id = pp.project_id 
                                 WHERE pu.user_id = ? 
                                 ORDER BY p.created_at DESC");
            $stmt->execute([$userId]);
        }

        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add storage info to each project
        foreach ($projects as &$project) {
            $project['storage'] = $this->getProjectStorageInfo($project['id']);
        }

        $this->view('admin/projects/index', [
            'title' => 'Project Management',
            'projects' => $projects,
            'breadcrumbs' => ['Projects' => null]
        ]);
    }

    /**
     * Renders project creation form.
     */
    public function form()
    {
        Auth::requireAdmin();
        $id = $_GET['id'] ?? null;
        $project = null;

        $db = Database::getInstance()->getConnection();

        if ($id) {
            $stmt = $db->prepare("SELECT * FROM projects WHERE id = ?");
            $stmt->execute([$id]);
            $project = $stmt->fetch();

            // Fetch assigned users
            $stmt = $db->prepare("SELECT user_id FROM project_users WHERE project_id = ?");
            $stmt->execute([$id]);
            $project['user_ids'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Fetch project plan
            $stmt = $db->prepare("SELECT * FROM project_plans WHERE project_id = ?");
            $stmt->execute([$id]);
            $project['plan'] = $stmt->fetch();
        }

        // Fetch all users for assignment
        $users = $db->query("SELECT id, username, public_name, email, phone, address, tax_id FROM users ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);

        // --- BILLING INTEGRATION ---
        // Fetch Billing Plans
        $billingPlans = $db->query("SELECT id, name, frequency FROM payment_plans WHERE status = 'active' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/projects/form', [
            'title' => $id ? 'Editar Proyecto' : 'Nuevo Proyecto',
            'project' => $project,
            'users' => $users,
            'billingPlans' => $billingPlans,
            'breadcrumbs' => ['Projects' => 'admin/projects', ($id ? 'Edit' : 'New') => null]
        ]);
    }

    /**
     * Saves project data.
     */
    public function save()
    {
        Auth::requireAdmin();
        $id = $_POST['id'] ?? null;
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $storageQuota = $_POST['storage_quota'] ?? 300;
        $planType = $_POST['plan_type'] ?? 'monthly';
        $startDate = $_POST['start_date'] ?? Auth::getCurrentTime();

        if (empty($name)) {
            Auth::setFlashError("Project name is required.");
            $this->redirect('admin/projects/new');
            return;
        }

        $db = Database::getInstance()->getConnection();
        try {
            if ($id) {
                $stmt = $db->prepare("UPDATE projects SET name = ?, description = ?, storage_quota = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$name, $description, $storageQuota, $id]);
            } else {
                $stmt = $db->prepare("INSERT INTO projects (name, description, storage_quota) VALUES (?, ?, ?)");
                $stmt->execute([$name, $description, $storageQuota]);
                $id = $db->lastInsertId();
            }

            // Sync User Assignments
            $assignedUsers = $_POST['user_ids'] ?? [];
            $db->prepare("DELETE FROM project_users WHERE project_id = ?")->execute([$id]);
            foreach ($assignedUsers as $userId) {
                $db->prepare("INSERT INTO project_users (project_id, user_id, permissions) VALUES (?, ?, ?)")
                    ->execute([$id, $userId, json_encode(['all' => true])]);
            }

            // --- BILLING INTEGRATION ---
            $billingUserId = $_POST['billing_user_id'] ?? null;
            $currentPlanId = $_POST['current_plan_id'] ?? null;
            $billingStartDate = $_POST['start_date'] ?? date('Y-m-d');

            // --- SERVICES INTEGRATION ---
            $services = $_POST['services'] ?? [];
            $db->prepare("DELETE FROM project_services WHERE project_id = ?")->execute([$id]);

            $freq = 'monthly';
            if ($currentPlanId) {
                $planFreqStmt = $db->prepare("SELECT frequency FROM payment_plans WHERE id = ?");
                $planFreqStmt->execute([$currentPlanId]);
                $freq = $planFreqStmt->fetchColumn() ?: 'monthly';
            }

            foreach ($services as $srv) {
                $servicePeriod = $srv['billing_period'] ?? $freq;
                $customPrice = isset($srv['custom_price']) ? (float) $srv['custom_price'] : null;
                $stmt = $db->prepare("INSERT INTO project_services (project_id, service_id, quantity, billing_period, custom_price) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$id, $srv['service_id'], $srv['quantity'], $servicePeriod, $customPrice]);
            }

            // Update Billing fields in projects table
            $stmt = $db->prepare("UPDATE projects SET billing_user_id = ?, start_date = ?, current_plan_id = ? WHERE id = ?");
            $stmt->execute([$billingUserId, $billingStartDate, $currentPlanId, $id]);

            // Handle Initial Generation or Recalculation of installments
            if ($currentPlanId) {
                $generator = new InstallmentGenerator();
                $stmt = $db->prepare("SELECT COUNT(*) FROM installments WHERE project_id = ? AND status != 'cancelada'");
                $stmt->execute([$id]);
                if ($stmt->fetchColumn() == 0) {
                    $generator->generateInstallments($id, $currentPlanId, $billingStartDate);
                } else {
                    $generator->recalculateInstallments($id, $currentPlanId, $billingStartDate);
                }
            } else {
                // Keep Legacy Plan System for compatibility
                PlanManager::switchPlan($id, $planType, $startDate);
            }

            // Refresh session project list
            Auth::loadUserProjects();

            Auth::setFlashError("Project saved successfully.", 'success');
            $this->redirect('admin/projects');
        } catch (Exception $e) {
            Auth::setFlashError("Error saving project: " . $e->getMessage());
            $this->redirect('admin/projects');
        }
    }

    /**
     * Renders the project selection page.
     */
    public function select()
    {
        Auth::requireLogin();
        $db = Database::getInstance()->getConnection();
        $userId = $_SESSION['user_id'];

        if (Auth::isAdmin()) {
            $stmt = $db->query("SELECT p.*, pp.plan_type FROM projects p 
                                LEFT JOIN project_plans pp ON p.id = pp.project_id 
                                WHERE p.status = 'active' ORDER BY p.name ASC");
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $db->prepare("SELECT p.*, pp.plan_type FROM projects p 
                                 JOIN project_users pu ON p.id = pu.project_id 
                                 LEFT JOIN project_plans pp ON p.id = pp.project_id 
                                 WHERE pu.user_id = ? AND p.status = 'active' ORDER BY p.name ASC");
            $stmt->execute([$userId]);
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $this->view('admin/projects/select', [
            'title' => 'Mis Proyectos',
            'projects' => $projects,
            'active_project_id' => Auth::getActiveProject()
        ]);
    }

    /**
     * Switches the current active project session.
     */
    public function switch()
    {
        $id = $_GET['id'] ?? null;
        if ($id && Auth::setActiveProject($id)) {
            Auth::setFlashError("Active project switched.", 'success');
        } else {
            Auth::setFlashError("Invalid project selection.");
        }
        $this->redirect('admin/dashboard');
    }

    /**
     * Updates only the plan for a project.
     */
    public function updatePlan()
    {
        Auth::requireAdmin();
        $id = $_POST['project_id'] ?? null;
        $planType = $_POST['plan_type'] ?? null;

        if ($id && $planType) {
            try {
                PlanManager::switchPlan($id, $planType);
                $this->json(['success' => true]);
            } catch (Exception $e) {
                $this->json(['error' => $e->getMessage()], 500);
            }
        }
        $this->json(['error' => 'Missing parameters'], 400);
    }

    /**
     * Deletes a project and its associations.
     */
    public function delete()
    {
        Auth::requireAdmin();
        $id = $_GET['id'] ?? null;
        if ($id) {
            $db = Database::getInstance()->getConnection();
            $db->prepare("DELETE FROM project_users WHERE project_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM project_plans WHERE project_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM projects WHERE id = ?")->execute([$id]);
            Auth::setFlashError("Project deleted successfully.", 'success');
        }
        $this->redirect('admin/projects');
    }
}
