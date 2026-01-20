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
        $dbId = $_GET['db_id'] ?? null;

        if (!$dbId) {
            $this->json(['error' => 'Database ID required'], 400);
        }

        $generator = new OpenApiGenerator();
        $spec = $generator->generateSpec($dbId);

        header('Content-Type: application/json');
        echo json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
