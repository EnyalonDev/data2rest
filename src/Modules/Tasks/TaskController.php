<?php

namespace App\Modules\Tasks;

use App\Core\Auth;
use App\Core\Database;
use App\Core\BaseController;
use PDO;
use Exception;

/**
 * Task Controller
 * 
 * Manages Kanban-style task management with role-based permissions
 * and comprehensive task lifecycle tracking.
 * 
 * Features:
 * - Kanban board visualization with drag-and-drop
 * - Role-based task permissions (Admin, Marketing, Developer, Client)
 * - Task assignment and status management
 * - Task history and comment tracking
 * - Client approval workflow
 * 
 * Permissions:
 * - Admin: Full access (create, edit, delete, move, assign)
 * - Marketing/Developer: Can create, move, assign, and take tasks
 * - Client: Can create tasks and approve from validation status
 * 
 * @package App\Modules\Tasks
 * @author DATA2REST Development Team
 * @version 1.0.0
 */
/**
 * TaskController Controller
 *
 * Core Features: TODO
 *
 * Security: Requires login, permission checks as implemented.
 *
 * @package App\Modules\
 * @author DATA2REST Development Team
 * @version 1.0.0
 */
class TaskController extends BaseController
{
    /**
     * Constructor - Requires user authentication
     * 
     * Ensures that only authenticated users can access
     * any task management functionality.
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
     * Display Kanban board for current project
     */
/**
 * index method
 *
 * @return void
 */
    public function index()
    {
        $projectId = Auth::getActiveProject();
        if (!$projectId) {
            Auth::setFlashError("Por favor selecciona un proyecto primero.", 'error');
            $this->redirect('admin/projects/select');
            return;
        }

        $db = Database::getInstance()->getConnection();

        // Get all task statuses
        $statuses = $db->query("SELECT * FROM task_statuses ORDER BY position ASC")->fetchAll(PDO::FETCH_ASSOC);

        // Get tasks grouped by status
        $tasks = [];
        foreach ($statuses as $status) {
            $stmt = $db->prepare("
                SELECT t.*, 
                       u_assigned.username as assigned_username,
                       u_assigned.public_name as assigned_name,
                       u_created.username as creator_username,
                       u_created.public_name as creator_name
                FROM tasks t
                LEFT JOIN users u_assigned ON t.assigned_to = u_assigned.id
                LEFT JOIN users u_created ON t.created_by = u_created.id
                WHERE t.project_id = ? AND t.status_id = ?
                ORDER BY t.position ASC, t.created_at DESC
            ");
            $stmt->execute([$projectId, $status['id']]);
            $tasks[$status['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Get project users for assignment
        $stmt = $db->prepare("
            SELECT u.id, u.username, u.public_name 
            FROM users u
            JOIN project_users pu ON u.id = pu.user_id
            WHERE pu.project_id = ?
            ORDER BY u.username ASC
        ");
        $stmt->execute([$projectId]);
        $projectUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Check user permissions
        $canDrag = $this->canMoveTask();
        $canCreate = $this->canCreateTask();
        $canDelete = Auth::isAdmin();
        $isClient = $this->isClientRole();

        $this->view('admin/tasks/kanban', [
            'title' => 'Gestión de Tareas',
            'statuses' => $statuses,
            'tasks' => $tasks,
            'projectUsers' => $projectUsers,
            'canDrag' => $canDrag,
            'canCreate' => $canCreate,
            'canDelete' => $canDelete,
            'isClient' => $isClient,
            'breadcrumbs' => ['Proyectos' => 'admin/projects', 'Tareas' => null]
        ]);
    }

    /**
     * Create a new task
     */
/**
 * create method
 *
 * @return void
 */
    public function create()
    {
        if (!$this->canCreateTask()) {
            $this->json(['error' => 'No tienes permisos para crear tareas'], 403);
            return;
        }

        $projectId = Auth::getActiveProject();
        if (!$projectId) {
            $this->json(['error' => 'No hay proyecto activo'], 400);
            return;
        }

        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $priority = $_POST['priority'] ?? 'medium';
        $assignedTo = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null;
        $statusId = $_POST['status_id'] ?? 1; // Default to first status (Backlog)

        if (empty($title)) {
            $this->json(['error' => 'El título es requerido'], 400);
            return;
        }

        try {
            $db = Database::getInstance()->getConnection();

            // Get max position for the status
            $stmt = $db->prepare("SELECT COALESCE(MAX(position), 0) + 1 as next_pos FROM tasks WHERE status_id = ? AND project_id = ?");
            $stmt->execute([$statusId, $projectId]);
            $position = $stmt->fetchColumn();

            $stmt = $db->prepare("
                INSERT INTO tasks (project_id, title, description, priority, status_id, assigned_to, created_by, position)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $projectId,
                $title,
                $description,
                $priority,
                $statusId,
                $assignedTo,
                Auth::getUserId(),
                $position
            ]);

            $taskId = $db->lastInsertId();

            // Log creation
            $this->logTaskHistory($taskId, 'created', null, $statusId);

            $this->json(['success' => true, 'task_id' => $taskId]);
        } catch (Exception $e) {
            $this->json(['error' => 'Error al crear tarea: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update task status and position (Drag & Drop)
     */
/**
 * move method
 *
 * @return void
 */
    public function move()
    {
        if (!$this->canMoveTask()) {
            $this->json(['error' => 'No tienes permisos para mover tareas'], 403);
            return;
        }

        $taskId = $_POST['task_id'] ?? null;
        $newStatusId = $_POST['status_id'] ?? null;
        $newPosition = $_POST['position'] ?? 0;

        if (!$taskId || !$newStatusId) {
            $this->json(['error' => 'Parámetros inválidos'], 400);
            return;
        }

        try {
            $db = Database::getInstance()->getConnection();

            // Get current task info
            $stmt = $db->prepare("SELECT status_id, project_id FROM tasks WHERE id = ?");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$task) {
                $this->json(['error' => 'Tarea no encontrada'], 404);
                return;
            }

            $oldStatusId = $task['status_id'];

            // Update task
            $stmt = $db->prepare("UPDATE tasks SET status_id = ?, position = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$newStatusId, $newPosition, $taskId]);

            // Log the move
            $this->logTaskHistory($taskId, 'moved', $oldStatusId, $newStatusId);

            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => 'Error al mover tarea: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Assign task to self
     */
/**
 * take method
 *
 * @return void
 */
    public function take()
    {
        $taskId = $_POST['task_id'] ?? null;
        if (!$taskId) {
            $this->json(['error' => 'ID de tarea inválido'], 400);
            return;
        }

        try {
            $db = Database::getInstance()->getConnection();

            // Check permissions (not client)
            if ($this->isClientRole()) {
                $this->json(['error' => 'Los clientes no pueden tomar tareas'], 403);
                return;
            }

            // Assign to current user
            $stmt = $db->prepare("UPDATE tasks SET assigned_to = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([Auth::getUserId(), $taskId]);

            $this->logTaskHistory($taskId, 'updated', null, null, 'Usuario se auto-asignó la tarea');

            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => 'Error al tomar tarea: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update task details
     */
/**
 * update method
 *
 * @return void
 */
    public function update()
    {
        $taskId = $_POST['id'] ?? null;
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $priority = $_POST['priority'] ?? 'medium';
        $assignedTo = $_POST['assigned_to'] ?? null;

        if (!$taskId || empty($title)) {
            $this->json(['error' => 'Datos inválidos'], 400);
            return;
        }

        try {
            $db = Database::getInstance()->getConnection();

            // Verify ownership or admin
            $stmt = $db->prepare("SELECT created_by FROM tasks WHERE id = ?");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$task) {
                $this->json(['error' => 'Tarea no encontrada'], 404);
                return;
            }

            if (!Auth::isAdmin() && $task['created_by'] != Auth::getUserId()) {
                $this->json(['error' => 'No tienes permisos para editar esta tarea'], 403);
                return;
            }

            $stmt = $db->prepare("
                UPDATE tasks 
                SET title = ?, description = ?, priority = ?, assigned_to = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$title, $description, $priority, $assignedTo, $taskId]);

            $this->logTaskHistory($taskId, 'updated');

            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => 'Error al actualizar tarea: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete a task (Admin only)
     */
/**
 * delete method
 *
 * @return void
 */
    public function delete()
    {
        Auth::requireAdmin();

        $taskId = $_GET['id'] ?? null;
        if (!$taskId) {
            $this->json(['error' => 'ID de tarea inválido'], 400);
            return;
        }

        try {
            $db = Database::getInstance()->getConnection();

            // Log deletion before deleting
            $this->logTaskHistory($taskId, 'deleted');

            $stmt = $db->prepare("DELETE FROM tasks WHERE id = ?");
            $stmt->execute([$taskId]);

            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => 'Error al eliminar tarea: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Add comment to task and optionally approve (Client special action)
     */
/**
 * addComment method
 *
 * @return void
 */
    public function addComment()
    {
        $taskId = $_POST['task_id'] ?? null;
        $comment = $_POST['comment'] ?? '';
        $approve = isset($_POST['approve']) && $_POST['approve'] === 'true';

        if (!$taskId || empty($comment)) {
            $this->json(['error' => 'Datos inválidos'], 400);
            return;
        }

        try {
            $db = Database::getInstance()->getConnection();

            // Get task info
            $stmt = $db->prepare("SELECT status_id FROM tasks WHERE id = ?");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$task) {
                $this->json(['error' => 'Tarea no encontrada'], 404);
                return;
            }

            $oldStatusId = $task['status_id'];
            $newStatusId = $oldStatusId;

            // If client approves from "Validación Cliente" status, move to "Finalizado"
            if ($approve && $this->isClientRole()) {
                // Get "client_validation" status ID
                $validationStatus = $db->query("SELECT id FROM task_statuses WHERE slug = 'client_validation'")->fetch(PDO::FETCH_ASSOC);
                $doneStatus = $db->query("SELECT id FROM task_statuses WHERE slug = 'done'")->fetch(PDO::FETCH_ASSOC);

                if ($validationStatus && $doneStatus && $task['status_id'] == $validationStatus['id']) {
                    $newStatusId = $doneStatus['id'];
                    $stmt = $db->prepare("UPDATE tasks SET status_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                    $stmt->execute([$newStatusId, $taskId]);
                }
            }

            // Log comment
            $this->logTaskHistory($taskId, $approve ? 'approved' : 'commented', $oldStatusId, $newStatusId, $comment);

            $this->json(['success' => true, 'moved_to_done' => $newStatusId != $oldStatusId]);
        } catch (Exception $e) {
            $this->json(['error' => 'Error al agregar comentario: ' . $e->getMessage()], 500);
        }
    }

/**
 * getTaskDetails method
 *
 * @return void
 */
    public function getTaskDetails()
    {
        $taskId = $_GET['id'] ?? null;
        if (!$taskId) {
            $this->json(['error' => 'ID de tarea inválido'], 400);
            return;
        }

        try {
            $db = Database::getInstance()->getConnection();

            // Get Task Info
            $stmt = $db->prepare("SELECT * FROM tasks WHERE id = ?");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get History
            $stmt = $db->prepare("
                SELECT th.*, 
                       u.username, u.public_name,
                       os.name as old_status_name,
                       ns.name as new_status_name
                FROM task_history th
                JOIN users u ON th.user_id = u.id
                LEFT JOIN task_statuses os ON th.old_status_id = os.id
                LEFT JOIN task_statuses ns ON th.new_status_id = ns.id
                WHERE th.task_id = ?
                ORDER BY th.created_at DESC
            ");
            $stmt->execute([$taskId]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get Comments
            $stmt = $db->prepare("
                SELECT tc.*, 
                       u.username, u.public_name
                FROM task_comments tc
                JOIN users u ON tc.user_id = u.id
                WHERE tc.task_id = ?
                ORDER BY tc.created_at DESC
            ");
            $stmt->execute([$taskId]);
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->json(['success' => true, 'task' => $task, 'history' => $history, 'comments' => $comments]);
        } catch (Exception $e) {
            $this->json(['error' => 'Error al obtener detalles: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Post a generic comment to the task
     */
/**
 * postComment method
 *
 * @return void
 */
    public function postComment()
    {
        $taskId = $_POST['task_id'] ?? null;
        $comment = $_POST['comment'] ?? '';

        if (!$taskId || empty($comment)) {
            $this->json(['error' => 'Datos inválidos'], 400);
            return;
        }

        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("INSERT INTO task_comments (task_id, user_id, comment) VALUES (?, ?, ?)");
            $stmt->execute([$taskId, Auth::getUserId(), $comment]);

            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => 'Error al publicar comentario: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Assign task to a user
     */
/**
 * assign method
 *
 * @return void
 */
    public function assign()
    {
        $taskId = $_POST['task_id'] ?? null;
        $userId = $_POST['user_id'] ?? null;

        // Convert empty string to null (for unassigning)
        if ($userId === '')
            $userId = null;

        if (!$taskId) {
            $this->json(['error' => 'ID de tarea inválido'], 400);
            return;
        }

        try {
            $db = Database::getInstance()->getConnection();

            // Check current assignment
            $current = $db->prepare("SELECT assigned_to FROM tasks WHERE id = ?");
            $current->execute([$taskId]);
            $oldUserId = $current->fetchColumn();

            // Strict comparison might fail if types differ (string vs int), so use non-strict or cast
            if ($oldUserId != $userId) {
                $stmt = $db->prepare("UPDATE tasks SET assigned_to = ? WHERE id = ?");
                $stmt->execute([$userId, $taskId]);

                $userName = 'Nadie';
                if ($userId) {
                    $uStmt = $db->prepare("SELECT public_name FROM users WHERE id = ?");
                    $uStmt->execute([$userId]);
                    $userName = $uStmt->fetchColumn() ?: 'Usuario Desconocido';
                }

                $this->logTaskHistory($taskId, 'assigned', null, null, "Asignado a: $userName");
            }

            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => 'Error al asignar tarea: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Log task history
     */
    private function logTaskHistory($taskId, $action, $oldStatusId = null, $newStatusId = null, $comment = null)
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                INSERT INTO task_history (task_id, user_id, action, old_status_id, new_status_id, comment)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $taskId,
                Auth::getUserId(),
                $action,
                $oldStatusId,
                $newStatusId,
                $comment
            ]);
        } catch (Exception $e) {
            // Log error but don't fail the main operation
            error_log("Error logging task history: " . $e->getMessage());
        }
    }

    /**
     * Check if current user can move tasks
     */
    private function canMoveTask()
    {
        // Admin, Editor, Dev, Marketing can move
        // Client cannot move
        return !$this->isClientRole();
    }

    /**
     * Check if current user can create tasks
     */
    private function canCreateTask()
    {
        // All authenticated users can create tasks
        return Auth::check();
    }

    /**
     * Check if current user has Client role
     */
    private function isClientRole()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT r.name 
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.id = ?
        ");
        $stmt->execute([Auth::getUserId()]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);

        return $role && strtolower($role['name']) === 'client';
    }
}
