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
}
