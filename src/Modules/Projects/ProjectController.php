<?php

namespace App\Modules\Projects;

use App\Core\Auth;
use App\Core\Database;
use App\Core\BaseController;
use App\Core\PlanManager;
use App\Core\Config;
use App\Core\Logger;
use App\Modules\Billing\Services\InstallmentGenerator;
use PDO;
use Exception;

/**
 * Project Controller
 * 
 * Comprehensive project lifecycle management system with billing integration,
 * user assignments, and service subscription handling.
 * 
 * Core Features:
 * - Project CRUD operations
 * - User assignment and permissions
 * - Subscription plan management
 * - Service subscription (Alta/Baja)
 * - Task template cloning on service activation
 * - Storage quota management
 * - Project switching for multi-tenant access
 * - Automated backup on deletion
 * 
 * Billing Integration:
 * - Payment plan assignment
 * - Service subscriptions with custom pricing
 * - Installment generation and recalculation
 * - Billing period management (monthly/yearly)
 * - Billing user assignment
 * 
 * Service Management:
 * - Alta (Subscription): Clone task templates on service add
 * - Baja (Cancellation): Mark or delete tasks on service removal
 * - Custom pricing per service
 * - Quantity management
 * 
 * Access Control:
 * - Admin: Full access to all projects
 * - User: Access only to assigned projects
 * - Project-scoped data isolation
 * 
 * Data Integrity:
 * - Automatic backup on project deletion
 * - Cascade deletion of associations
 * - Storage quota enforcement
 * - Session project tracking
 * 
 * @package App\Modules\Projects
 * @author DATA2REST Development Team
 * @version 1.0.0
 */
/**
 * ProjectController Controller
 *
 * Core Features: TODO
 *
 * Security: Requires login, permission checks as implemented.
 *
 * @package App\Modules\
 * @author DATA2REST Development Team
 * @version 1.0.0
 */
class ProjectController extends BaseController
{
    /**
     * Constructor - Requires user authentication
     * 
     * Ensures that only authenticated users can access
     * project management functionality.
     */
    /**
     * __construct method
     *
     * @return void
     */
    public function __construct()
    {
        Auth::requireLogin();
    }

    /**
     * Display list of projects
     * 
     * Shows all projects with plan and storage information.
     * Implements access control based on user role.
     * 
     * Features:
     * - Admin: View all projects
     * - User: View only assigned projects
     * - Plan information display
     * - Storage quota tracking
     * 
     * @return void Renders project list view
     * 
     * @example
     * GET /admin/projects
     */
    /**
     * index method
     *
     * @return void
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
    /**
     * form method
     *
     * @return void
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
     * Save project data (create or update)
     * 
     * Comprehensive project save with billing integration, service management,
     * and automated task template cloning.
     * 
     * Features:
     * - Project creation/update
     * - User assignment synchronization
     * - Billing plan integration
     * - Service subscription management (Alta/Baja)
     * - Task template cloning on service activation
     * - Task cancellation on service removal
     * - Installment generation/recalculation
     * 
     * Service Alta (Subscription):
     * - Clones task templates from service
     * - Creates tasks in Backlog status
     * - Associates tasks with project and service
     * 
     * Service Baja (Cancellation):
     * - Deletes tasks in Backlog
     * - Marks in-progress tasks as [CANCELADO]
     * 
     * @return void Redirects to project list on success
     * 
     * @example
     * POST /admin/projects/save
     * Body: name=Project&user_ids[]=1&services[0][service_id]=1
     */
    /**
     * save method
     *
     * @return void
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

            // Get current services before deletion for comparison
            $oldServicesStmt = $db->prepare("SELECT service_id FROM project_services WHERE project_id = ?");
            $oldServicesStmt->execute([$id]);
            $oldServiceIds = $oldServicesStmt->fetchAll(PDO::FETCH_COLUMN);

            $db->prepare("DELETE FROM project_services WHERE project_id = ?")->execute([$id]);

            $freq = 'monthly';
            if ($currentPlanId) {
                $planFreqStmt = $db->prepare("SELECT frequency FROM payment_plans WHERE id = ?");
                $planFreqStmt->execute([$currentPlanId]);
                $freq = $planFreqStmt->fetchColumn() ?: 'monthly';
            }

            $currentUserId = $_SESSION['user_id'] ?? 1; // Fallback to 1 if session issue
            $backlogId = $db->query("SELECT id FROM task_statuses WHERE slug = 'backlog'")->fetchColumn() ?: 1;
            $newServiceIds = [];

            foreach ($services as $srv) {
                $serviceId = $srv['service_id'];
                $newServiceIds[] = $serviceId;

                $servicePeriod = $srv['billing_period'] ?? $freq;
                $customPrice = isset($srv['custom_price']) ? (float) $srv['custom_price'] : null;
                $stmt = $db->prepare("INSERT INTO project_services (project_id, service_id, quantity, billing_period, custom_price) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$id, $serviceId, $srv['quantity'], $servicePeriod, $customPrice]);

                // HANDLE ALTA (Subscription): New Service Added
                if (!in_array($serviceId, $oldServiceIds)) {
                    // Clone templates
                    $tplStmt = $db->prepare("SELECT * FROM billing_service_templates WHERE service_id = ?");
                    $tplStmt->execute([$serviceId]);
                    $templates = $tplStmt->fetchAll(PDO::FETCH_ASSOC);

                    $taskStmt = $db->prepare("INSERT INTO tasks (project_id, title, description, priority, status_id, created_by, service_id, assigned_to) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    foreach ($templates as $tpl) {
                        // Assign logic: By default unassigned or assigned to someone? Requirements don't specify assignment.
                        $taskStmt->execute([$id, $tpl['title'], $tpl['description'], $tpl['priority'], $backlogId, $currentUserId, $serviceId, null]);
                    }
                }
            }

            // HANDLE BAJA (Cancellation): Service Removed
            $removedServices = array_diff($oldServiceIds, $newServiceIds);
            foreach ($removedServices as $rmId) {
                // Find tasks for this project and service
                $tasksStmt = $db->prepare("SELECT id, status_id, title FROM tasks WHERE project_id = ? AND service_id = ?");
                $tasksStmt->execute([$id, $rmId]);
                $associatedTasks = $tasksStmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($associatedTasks as $task) {
                    if ($task['status_id'] == $backlogId) {
                        // Delete if in Backlog
                        $db->prepare("DELETE FROM tasks WHERE id = ?")->execute([$task['id']]);
                    } else {
                        // Mark as cancelled if in progress/done
                        if (strpos($task['title'], '[CANCELADO]') === false) {
                            $newTitle = "[CANCELADO] " . $task['title'];
                            $db->prepare("UPDATE tasks SET title = ? WHERE id = ?")->execute([$newTitle, $task['id']]);
                        }
                    }
                }
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
     * Display project selection page
     * 
     * Renders a page for users to select which project to work on.
     * Implements access control for project visibility.
     * 
     * Features:
     * - Admin: View all active projects
     * - User: View only assigned active projects
     * - Current project highlighting
     * 
     * @return void Renders project selection view
     * 
     * @example
     * GET /admin/projects/select
     */
    /**
     * select method
     *
     * @return void
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
     * Switch active project in session
     * 
     * Changes the current active project for the user session,
     * affecting all project-scoped operations.
     * 
     * @return void Redirects to dashboard with status message
     * 
     * @example
     * GET /admin/projects/switch?id=5
     */
    /**
     * switch method
     *
     * @return void
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
    /**
     * updatePlan method
     *
     * @return void
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
     * Delete project with automatic backup
     * 
     * Deletes a project and all its associations with automatic
     * database backup to prevent data loss.
     * 
     * Features:
     * - Automatic database backup before deletion
     * - Cascade deletion of associations
     * - Backup naming: Project_Delete_DataBase_{Project}_{DB}_{Timestamp}
     * - Backup location: data/backups/deleted_projects/
     * 
     * Deleted Associations:
     * - Databases and field configurations
     * - User assignments
     * - Plans and services
     * - Installments
     * 
     * @return void Redirects to project list with status message
     * 
     * @example
     * GET /admin/projects/delete?id=5
     */
    /**
     * delete method
     *
     * @return void
     */
    public function delete()
    {
        Auth::requireAdmin();
        $id = $_GET['id'] ?? null;
        if ($id) {
            $db = Database::getInstance()->getConnection();

            // 1. Fetch Project Info and Databases
            $stmt = $db->prepare("SELECT name FROM projects WHERE id = ?");
            $stmt->execute([$id]);
            $projectName = $stmt->fetchColumn() ?: 'UnknownProject';
            // Sanitize project name for filename
            $safeProjectName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $projectName);

            $stmt = $db->prepare("SELECT * FROM databases WHERE project_id = ?");
            $stmt->execute([$id]);
            $databases = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 2. Prepare Backup Directory
            $backupDir = dirname(Config::get('db_path')) . '/backups/deleted_projects';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            // 3. Backup and Delete Databases
            foreach ($databases as $database) {
                // Check if physical file exists
                if (file_exists($database['path'])) {
                    // "Project Delete DataBase" format as requested
                    // Project_Delete_DataBase_{Project}_{DB}_{Timestamp}.sqlite
                    $safeDbName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $database['name']);
                    $backupName = sprintf(
                        "Project_Delete_DataBase_%s_%s_%s.sqlite",
                        $safeProjectName,
                        $safeDbName,
                        date('Y-m-d_H-i-s')
                    );
                    $backupPath = $backupDir . '/' . $backupName;

                    // Copy file to backup location
                    if (copy($database['path'], $backupPath)) {
                        // Delete original file
                        unlink($database['path']);
                    }
                }

                // Delete field configs for this DB
                $db->prepare("DELETE FROM fields_config WHERE db_id = ?")->execute([$database['id']]);
                // Delete DB record
                $db->prepare("DELETE FROM databases WHERE id = ?")->execute([$database['id']]);
            }

            // 4. Delete Project Associations
            $db->prepare("DELETE FROM project_users WHERE project_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM project_plans WHERE project_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM project_services WHERE project_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM installments WHERE project_id = ?")->execute([$id]);

            // 5. Delete Project Record
            $db->prepare("DELETE FROM projects WHERE id = ?")->execute([$id]);

            Logger::log('DELETE_PROJECT', ['id' => $id, 'name' => $projectName, 'backup_dir' => $backupDir]);
            Auth::setFlashError("Project deleted successfully. Databases backed up to deleted_projects folder.", 'success');
        }
        $this->redirect('admin/projects');
    }
}
