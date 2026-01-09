<?php

namespace App\Core;

/**
 * Base Controller
 * Provides common functionality for all controllers, such as view rendering and JSON responses.
 */
class BaseController
{
    /**
     * Renders a view file within a layout or standalone.
     * 
     * @param string $path Path to the view file relative to src/Views
     * @param array $data Data to be extracted and made available to the view
     * @param string|null $layout Name of the layout file (optional)
     */
    protected function view($path, $data = [], $layout = 'layout')
    {
        // Extract data to make variables available in the scope of the required view
        extract($data);

        $baseUrl = Auth::getBaseUrl();
        $flash = Auth::getFlashMsg();

        // Path to the specific view template
        $viewFile = __DIR__ . '/../Views/' . $path . '.php';

        if (!file_exists($viewFile)) {
            die("View not found: $path");
        }

        // If a layout is specified, it usually includes $viewFile within its own structure
        if ($layout) {
            require_once __DIR__ . '/../Views/' . $layout . '.php';
        } else {
            require_once $viewFile;
        }
    }

    /**
     * Sends a JSON response to the client.
     * 
     * @param mixed $data Data to encode as JSON
     * @param int $code HTTP response code
     */
    protected function json($data, $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Redirects to a different path within the application.
     * 
     * @param string $path Target path
     */
    protected function redirect($path)
    {
        $baseUrl = Auth::getBaseUrl();
        header("Location: {$baseUrl}{$path}");
        exit;
    }

    /**
     * Sanitizes a filename for SEO and filesystem compatibility.
     * Preserves original name but cleans accents and special characters.
     */
    protected function sanitizeFilename($filename)
    {
        $info = pathinfo($filename);
        $name = $info['filename'];
        $ext = isset($info['extension']) ? '.' . strtolower($info['extension']) : '';

        // Broad map for common accents
        $map = [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ñ' => 'n',
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ñ' => 'N',
            'à' => 'a',
            'è' => 'e',
            'ì' => 'i',
            'ò' => 'o',
            'ù' => 'u',
            'À' => 'A',
            'È' => 'E',
            'Ì' => 'I',
            'Ò' => 'O',
            'Ù' => 'U',
            'ä' => 'a',
            'ë' => 'e',
            'ï' => 'i',
            'ö' => 'o',
            'ü' => 'u',
            'Ä' => 'A',
            'Ë' => 'E',
            'Ï' => 'I',
            'Ö' => 'O',
            'Ü' => 'U'
        ];
        $name = strtr($name, $map);

        // Lowercase and cleanup
        $name = mb_strtolower($name, 'UTF-8');
        $name = preg_replace('/[^\w\s-]/u', '', $name);
        $name = preg_replace('/[\s_]+/', '-', $name);
        $name = preg_replace('/-+/', '-', $name);
        $name = trim($name, '-');

        return (empty($name) ? 'file' : $name) . $ext;
    }

    /**
     * Standardizes the storage prefix for a given database context.
     * Uses 'p' + project_id for consistency.
     */
    protected function getStoragePrefix($dbId = null)
    {
        if ($dbId) {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT project_id FROM databases WHERE id = ?");
            $stmt->execute([$dbId]);
            $projectId = $stmt->fetchColumn();
            if ($projectId)
                return 'p' . $projectId;
        }

        $projectId = Auth::getActiveProject();
        return $projectId ? 'p' . $projectId : 'global';
    }
}

