<?php

namespace App\Modules\Billing\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use PDO;

/**
 * Service API Controller
 * 
 * Manages the billing services catalog including CRUD operations
 * and task template management for each service.
 * 
 * This controller handles:
 * - Service listing, creation, updating, and deletion
 * - Task template management (CRUD operations)
 * - Template export/import functionality
 * 
 * @package App\Modules\Billing\Controllers
 * @author DATA2REST Development Team
 * @version 1.0.0
 */
class ServiceApiController extends BaseController
{
    /**
     * Database connection instance
     * 
     * @var PDO
     */
    private $db;

    /**
     * Constructor - Initializes database connection
     * 
     * Establishes a PDO connection to the system database
     * for all service and template operations.
     */
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get all active services
     * 
     * Retrieves a list of all active billing services from the database,
     * ordered alphabetically by name.
     * 
     * @return void Outputs JSON response with service list
     * 
     * @example
     * GET /api/billing/services
     * Response: {"success": true, "data": [{"id": 1, "name": "Service A", ...}]}
     */
    public function index()
    {
        try {
            $stmt = $this->db->query("SELECT * FROM billing_services WHERE status = 'active' ORDER BY name ASC");
            $services = $stmt->fetchAll();
            $this->json(['success' => true, 'data' => $services]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create a new service
     * 
     * Creates a new billing service with the provided data including
     * name, description, and multiple pricing options (monthly, yearly, one-time).
     * 
     * @return void Outputs JSON response with created service ID
     * 
     * @example
     * POST /api/billing/services
     * Body: {"name": "Web Hosting", "price_monthly": 9.99, "price_yearly": 99.99}
     * Response: {"success": true, "message": "Servicio creado exitosamente", "id": 5}
     */
    public function create()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['name'])) {
            $this->json(['error' => 'Nombre es requerido'], 400);
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO billing_services (name, description, price_monthly, price_yearly, price_one_time, price)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $input['name'],
                $input['description'] ?? null,
                !empty($input['price_monthly']) ? (float) $input['price_monthly'] : 0,
                !empty($input['price_yearly']) ? (float) $input['price_yearly'] : 0,
                !empty($input['price_one_time']) ? (float) $input['price_one_time'] : 0,
                !empty($input['price']) ? (float) $input['price'] : 0
            ]);

            $this->json([
                'success' => true,
                'message' => 'Servicio creado exitosamente',
                'id' => $this->db->lastInsertId()
            ], 201);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update an existing service
     * 
     * Updates the details of an existing billing service including
     * name, description, and pricing information.
     * 
     * @param int $id The ID of the service to update
     * @return void Outputs JSON response with success status
     * 
     * @example
     * PUT /api/billing/services/5
     * Body: {"name": "Premium Hosting", "price_monthly": 19.99}
     * Response: {"success": true}
     */
    public function update($id)
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['name'])) {
            $this->json(['error' => 'Nombre es requerido'], 400);
        }

        try {
            $stmt = $this->db->prepare("
                UPDATE billing_services 
                SET name = ?, description = ?, price_monthly = ?, price_yearly = ?, price_one_time = ?, price = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $input['name'],
                $input['description'] ?? null,
                !empty($input['price_monthly']) ? (float) $input['price_monthly'] : 0,
                !empty($input['price_yearly']) ? (float) $input['price_yearly'] : 0,
                !empty($input['price_one_time']) ? (float) $input['price_one_time'] : 0,
                !empty($input['price']) ? (float) $input['price'] : 0,
                $id
            ]);

            $this->json([
                'success' => true,
                'message' => 'Servicio actualizado exitosamente'
            ]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a service (soft delete)
     * 
     * Performs a soft delete by setting the service status to 'deleted'
     * instead of removing it from the database. This preserves data integrity
     * and allows for potential recovery.
     * 
     * @param int $id The ID of the service to delete
     * @return void Outputs JSON response with success status
     * 
     * @example
     * DELETE /api/billing/services/5
     * Response: {"success": true}
     */
    public function delete($id)
    {
        try {
            // Check if in use? Better soft delete
            $stmt = $this->db->prepare("UPDATE billing_services SET status = 'deleted' WHERE id = ?");
            $stmt->execute([$id]);
            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all task templates for a service
     * 
     * Retrieves all task templates associated with a specific service,
     * ordered by creation date.
     * 
     * @param int $serviceId The ID of the service
     * @return void Outputs JSON response with templates array
     * 
     * @example
     * GET /api/billing/services/5/templates
     * Response: {"success": true, "data": [{"id": 1, "title": "Setup", ...}]}
     */
    public function getTemplates($serviceId)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM billing_service_templates WHERE service_id = ? ORDER BY created_at ASC");
            $stmt->execute([$serviceId]);
            $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->json(['success' => true, 'data' => $templates]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Add a new task template to a service
     * 
     * Creates a new task template associated with the specified service.
     * Templates define standard tasks that should be performed for this service.
     * 
     * @param int $serviceId The ID of the service
     * @return void Outputs JSON response with success status
     * 
     * @example
     * POST /api/billing/services/5/templates
     * Body: {"title": "Initial Setup", "description": "Configure server", "priority": "high"}
     * Response: {"success": true}
     */
    public function addTemplate($serviceId)
    {
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['title'])) {
            $this->json(['error' => 'Title is required'], 400);
            return;
        }

        try {
            $stmt = $this->db->prepare("INSERT INTO billing_service_templates (service_id, title, description, priority) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $serviceId,
                $input['title'],
                $input['description'] ?? null,
                $input['priority'] ?? 'medium'
            ]);

            $this->json(['success' => true, 'id' => $this->db->lastInsertId()]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * PUT /api/billing/services/templates/{id}
     */
    public function updateTemplate($id)
    {
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['title'])) {
            $this->json(['error' => 'Title is required'], 400);
            return;
        }

        try {
            $stmt = $this->db->prepare("UPDATE billing_service_templates SET title = ?, description = ?, priority = ? WHERE id = ?");
            $stmt->execute([
                $input['title'],
                $input['description'] ?? null,
                $input['priority'] ?? 'medium',
                $id
            ]);

            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/billing/services/templates/{id}
     */
    public function deleteTemplate($id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM billing_service_templates WHERE id = ?");
            $stmt->execute([$id]);
            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
    /**
     * GET /api/billing/services/{id}/templates/export
     */
    /**
     * POST /api/billing/services/{id}/templates/generate-export
     */
    public function generateExport($serviceId)
    {
        try {
            $stmt = $this->db->prepare("SELECT title, description, priority FROM billing_service_templates WHERE service_id = ? ORDER BY created_at ASC");
            $stmt->execute([$serviceId]);
            $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Define backup directory relative to root (assuming src in ../src)
            $backupDir = __DIR__ . '/../../../../data/backups/templates_service';

            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $filename = 'service_templates_' . $serviceId . '.txt';
            $filepath = $backupDir . '/' . $filename;

            // Just JSON encoding for simplicity as requested (JSON is text)
            $content = json_encode($templates, JSON_PRETTY_PRINT);

            if (file_put_contents($filepath, $content) === false) {
                throw new \Exception("Failed to write backup file");
            }

            // Return URL to download
            $this->json([
                'success' => true,
                'downloadUrl' => '/api/billing/services/' . $serviceId . '/templates/download?t=' . time(),
                'filename' => $filename
            ]);

        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }



    /**
     * Import task templates from JSON
     * 
     * Imports task templates from multiple sources:
     * - File upload (multipart/form-data)
     * - Direct JSON content (POST field)
     * - JSON body (application/json)
     * 
     * All templates are inserted within a database transaction to ensure
     * data integrity. If any template fails, the entire import is rolled back.
     * 
     * @param int $serviceId The ID of the service to import templates into
     * @return void Outputs JSON response with import count
     * 
     * @example
     * POST /api/billing/services/5/templates/import
     * Body: {"content": "[{\"title\":\"Task 1\",\"priority\":\"high\"}]"}
     * Response: {"success": true, "count": 1}
     */
    public function importTemplates($serviceId)
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $content = null;

        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $content = file_get_contents($_FILES['file']['tmp_name']);
        } elseif (isset($_POST['content'])) {
            // If multipart/form-data with content field
            $content = $_POST['content'];
        } elseif (isset($input['content'])) {
            // If JSON body
            $content = $input['content'];
        }

        if (!$content) {
            $this->json(['error' => 'No file or content provided'], 400);
            return;
        }

        $templates = json_decode($content, true);

        if (!is_array($templates)) {
            $this->json(['error' => 'Invalid JSON format'], 400);
            return;
        }

        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("INSERT INTO billing_service_templates (service_id, title, description, priority) VALUES (?, ?, ?, ?)");

            foreach ($templates as $tpl) {
                if (empty($tpl['title']))
                    continue;
                $stmt->execute([
                    $serviceId,
                    $tpl['title'],
                    $tpl['description'] ?? null,
                    $tpl['priority'] ?? 'medium'
                ]);
            }

            $this->db->commit();
            $this->json(['success' => true, 'count' => count($templates)]);
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
    /**
     * Export task templates as JSON data
     * 
     * Retrieves all task templates for a service and returns them as JSON data.
     * This method is used for the copy-paste export functionality, allowing users
     * to copy the JSON and import it elsewhere without file downloads.
     * 
     * The exported data includes only the essential fields:
     * - title: Template name
     * - description: Template description
     * - priority: Task priority (low, medium, high)
     * 
     * @param int $serviceId The ID of the service to export templates from
     * @return void Outputs JSON response with templates data
     * 
     * @example
     * GET /api/billing/services/5/templates/export-data
     * Response: {"success": true, "data": [{"title": "Setup", "description": "...", "priority": "high"}]}
     */
    public function exportTemplatesData($serviceId)
    {
        try {
            $stmt = $this->db->prepare("SELECT title, description, priority FROM billing_service_templates WHERE service_id = ? ORDER BY created_at ASC");
            $stmt->execute([$serviceId]);
            $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->json(['success' => true, 'data' => $templates]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}
