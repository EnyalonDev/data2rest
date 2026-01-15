<?php

namespace App\Modules\Billing\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use PDO;

/**
 * Controlador REST de Clientes
 */
class ClientController extends BaseController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * GET /api/billing/clients
     * Lista todos los clientes
     */
    public function index()
    {
        $status = $_GET['status'] ?? 'active';

        $sql = "SELECT * FROM clients";
        if ($status !== 'all') {
            $sql .= " WHERE status = ?";
        }
        $sql .= " ORDER BY name ASC";

        $stmt = $this->db->prepare($sql);
        if ($status !== 'all') {
            $stmt->execute([$status]);
        } else {
            $stmt->execute();
        }

        $clients = $stmt->fetchAll();

        $this->json([
            'success' => true,
            'data' => $clients,
            'count' => count($clients)
        ]);
    }

    /**
     * POST /api/billing/clients
     * Crea un nuevo cliente
     */
    public function create()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['name'])) {
            $this->json(['error' => 'El nombre es requerido'], 400);
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO clients (name, email, phone, address, tax_id, status)
                VALUES (?, ?, ?, ?, ?, 'active')
            ");
            $stmt->execute([
                $input['name'],
                $input['email'] ?? null,
                $input['phone'] ?? null,
                $input['address'] ?? null,
                $input['tax_id'] ?? null
            ]);

            $clientId = $this->db->lastInsertId();

            $this->json([
                'success' => true,
                'message' => 'Cliente creado exitosamente',
                'client_id' => $clientId
            ], 201);

        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/billing/clients/{id}
     * Obtiene información de un cliente
     */
    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM clients WHERE id = ?");
        $stmt->execute([$id]);
        $client = $stmt->fetch();

        if (!$client) {
            $this->json(['error' => 'Cliente no encontrado'], 404);
        }

        // Obtener proyectos del cliente
        $projectsStmt = $this->db->prepare("
            SELECT p.*, pp.name as plan_name
            FROM projects p
            LEFT JOIN payment_plans pp ON p.current_plan_id = pp.id
            WHERE p.client_id = ?
        ");
        $projectsStmt->execute([$id]);
        $projects = $projectsStmt->fetchAll();

        $this->json([
            'success' => true,
            'data' => $client,
            'projects' => $projects
        ]);
    }

    /**
     * PUT /api/billing/clients/{id}
     * Actualiza un cliente
     */
    public function update($id)
    {
        $input = json_decode(file_get_contents('php://input'), true);

        // Verificar que existe
        $stmt = $this->db->prepare("SELECT * FROM clients WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            $this->json(['error' => 'Cliente no encontrado'], 404);
        }

        try {
            $updates = [];
            $values = [];

            $allowedFields = ['name', 'email', 'phone', 'address', 'tax_id', 'status'];

            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $updates[] = "$field = ?";
                    $values[] = $input[$field];
                }
            }

            if (empty($updates)) {
                $this->json(['error' => 'No hay datos para actualizar'], 400);
            }

            $updates[] = "updated_at = CURRENT_TIMESTAMP";
            $values[] = $id;

            $sql = "UPDATE clients SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($values);

            $this->json([
                'success' => true,
                'message' => 'Cliente actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/billing/clients/{id}
     * Elimina (desactiva) un cliente
     */
    public function delete($id)
    {
        // Verificar que no tenga proyectos activos
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM projects 
            WHERE client_id = ? AND status = 'active'
        ");
        $stmt->execute([$id]);
        $activeProjects = $stmt->fetchColumn();

        if ($activeProjects > 0) {
            $this->json([
                'error' => 'No se puede eliminar un cliente con proyectos activos',
                'active_projects' => (int) $activeProjects
            ], 400);
        }

        try {
            // Soft delete
            $stmt = $this->db->prepare("
                UPDATE clients 
                SET status = 'inactive', updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$id]);

            $this->json([
                'success' => true,
                'message' => 'Cliente desactivado exitosamente'
            ]);

        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}
