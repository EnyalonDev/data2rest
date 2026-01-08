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
}

