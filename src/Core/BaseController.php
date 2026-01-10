<?php

namespace App\Core;

require_once __DIR__ . '/External/BladeOne.php';
use eftec\bladeone\BladeOne;

/**
 * Base Controller
 * Provides common functionality for all controllers, such as view rendering and JSON responses.
 */
class BaseController
{
    protected $blade;

    private function initBlade()
    {
        if ($this->blade)
            return;

        $views = __DIR__ . '/../Views';
        $cache = __DIR__ . '/../../data/cache/views';

        if (!file_exists($cache)) {
            mkdir($cache, 0777, true);
        }

        // MODE_DEBUG compiles every time. MODE_AUTO checks if changed.
        $mode = Auth::isDevMode() ? BladeOne::MODE_DEBUG : BladeOne::MODE_AUTO;
        $this->blade = new BladeOne($views, $cache, $mode);

        // Base URL for assets
        $this->blade->setBaseUrl(Auth::getBaseUrl());
    }

    /**
     * Renders a view file using BladeOne.
     * 
     * @param string $path Path to the view file relative to src/Views (using dot notation)
     * @param array $data Data to be passed to the view
     */
    protected function view($path, $data = [])
    {
        $this->initBlade();

        $data['baseUrl'] = Auth::getBaseUrl();
        $data['flash'] = Auth::getFlashMsg();
        $data['lang'] = Lang::current();
        $data['csrf_token'] = Csrf::getToken();
        $data['csrf_field'] = Csrf::field();

        // Normalize path (convert slashes to dots for BladeOne if needed, 
        // but BladeOne accepts both. Dots are standard.)
        $path = str_replace('/', '.', $path);

        echo $this->blade->run($path, $data);
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

        // 1. Try Transliterator (Best quality)
        if (class_exists('Transliterator')) {
            $transliterator = \Transliterator::create('Any-Latin; Latin-ASCII; Lower()');
            $name = $transliterator->transliterate($name);
        }
        // 2. Try iconv (Standard)
        elseif (function_exists('iconv')) {
            $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        }
        // 3. Fallback map (Basic)
        else {
            $map = [
                'á' => 'a',
                'é' => 'e',
                'í' => 'i',
                'ó' => 'o',
                'ú' => 'u',
                'ñ' => 'n',
                'ü' => 'u',
                'Á' => 'A',
                'É' => 'E',
                'Í' => 'I',
                'Ó' => 'O',
                'Ú' => 'U',
                'Ñ' => 'N',
                'Ü' => 'U'
            ];
            $name = strtr($name, $map);
        }

        // Final cleanup
        $name = preg_replace('/[^a-zA-Z0-9\s-]/', '', $name); // Remove non-alphanumeric
        $name = preg_replace('/[\s-]+/', '-', $name);         // Collapse spaces/dashes
        $name = trim($name, '-');                             // Trim edges
        $name = strtolower($name);                            // Ensure lowercase

        return (empty($name) ? 'file-' . substr(uniqid(), -5) : $name) . $ext;
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

    /**
     * Manually verify CSRF token (Middleware Logic).
     * Note: This is primarily handled in Router.php, but exposed here for manual checks.
     */
    protected function verifyCsrf()
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        // Skip API
        if (strpos($uri, '/api/') === 0) {
            return true; 
        }

        // Verify Token
        $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Csrf::verify($token)) {
            http_response_code(403);
            die('CSRF Security Error: Invalid or missing token.');
        }
        return true;
    }
}

