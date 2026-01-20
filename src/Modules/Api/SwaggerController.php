<?php

namespace App\Modules\Api;

use App\Core\Auth;
use App\Core\BaseController;
use App\Core\OpenApiGenerator;

/**
 * Swagger/OpenAPI Documentation Controller
 * 
 * Provides auto-generated API documentation via Swagger UI
 * 
 * @package App\Modules\Api
 * @version 1.0.0
 */
class SwaggerController extends BaseController
{
    /**
     * Display Swagger UI
     */
    public function index()
    {
        $dbId = $_GET['db_id'] ?? null;

        if (!$dbId) {
            Auth::setFlashError('Database ID required');
            $this->redirect('admin/api');
        }

        $this->view('admin/api/swagger', [
            'title' => 'API Documentation',
            'db_id' => $dbId
        ]);
    }

    /**
     * Generate OpenAPI specification JSON
     */
    public function spec()
    {
        // Disable html errors to ensure JSON response
        ini_set('display_errors', 0);
        header('Content-Type: application/json');

        try {
            $dbId = $_GET['db_id'] ?? null;

            if (!$dbId) {
                throw new \Exception('Database ID required');
            }

            $generator = new \App\Core\OpenApiGenerator();
            $spec = $generator->generateSpec($dbId);

            echo json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
        exit;
    }
}
