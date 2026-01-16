<?php

namespace App\Modules\Billing\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use PDO;

/**
 * Servicio API para gestionar el catálogo de servicios
 */
class ServiceApiController extends BaseController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * GET /api/billing/services
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
     * POST /api/billing/services
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
     * PUT /api/billing/services/{id}
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
     * DELETE /api/billing/services/{id}
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
     * GET /api/billing/services/{id}/templates
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
     * POST /api/billing/services/{id}/templates
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
     * POST /api/billing/services/{id}/templates/import
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
     * GET /api/billing/services/{id}/templates/export-data
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
