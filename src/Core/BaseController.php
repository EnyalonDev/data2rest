<?php

namespace App\Core;

class BaseController
{
    protected function view($path, $data = [], $layout = 'layout')
    {
        extract($data);

        $baseUrl = Auth::getBaseUrl();
        $flash = Auth::getFlashMsg();

        // The view file to be included in the layout
        $viewFile = __DIR__ . '/../Views/' . $path . '.php';

        if (!file_exists($viewFile)) {
            die("View not found: $path");
        }

        if ($layout) {
            require_once __DIR__ . '/../Views/' . $layout . '.php';
        } else {
            require_once $viewFile;
        }
    }

    protected function json($data, $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function redirect($path)
    {
        $baseUrl = Auth::getBaseUrl();
        header("Location: {$baseUrl}{$path}");
        exit;
    }
}
