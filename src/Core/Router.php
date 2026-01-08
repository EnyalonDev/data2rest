<?php

namespace App\Core;

/**
 * Simple HTTP Router
 * Maps URI patterns to controller methods or closures.
 */
class Router
{
    /** @var array Registered routes */
    protected $routes = [];

    /**
     * Registers a new route.
     * 
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $path URI pattern (e.g., '/user/{id}')
     * @param mixed $handler Controller@method string or closure
     */
    public function add($method, $path, $handler)
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }

    /**
     * Matches the current request against registered routes and executes the handler.
     * 
     * @param string $method Current HTTP method
     * @param string $uri Current URI
     * @return mixed Handler result
     */
    public function dispatch($method, $uri)
    {
        // Parse the URI and extract only the path component
        $uri = parse_url($uri, PHP_URL_PATH);

        $scriptName = $_SERVER['SCRIPT_NAME'];
        $baseDir = dirname($scriptName);

        // Strip base directory from URI for matching
        if (strpos($uri, $baseDir) === 0) {
            $uri = substr($uri, strlen($baseDir));
        }

        $rootBase = dirname($baseDir);
        if ($rootBase !== '/' && $rootBase !== '.' && strpos($uri, $rootBase) === 0) {
            $uri = substr($uri, strlen($rootBase));
        }

        // Normalize URI
        if ($uri === '')
            $uri = '/';
        $uri = '/' . trim($uri, '/');

        foreach ($this->routes as $route) {
            if ($route['method'] === $method) {
                // Convert route path to regex (e.g., {id} becomes ([^/]+))
                $pattern = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([^/]+)', $route['path']);
                $pattern = '#^' . str_replace('/', '\/', $pattern) . '$#';

                if (preg_match($pattern, $uri, $matches)) {
                    array_shift($matches); // Remove the full match string
                    return $this->executeHandler($route['handler'], $matches);
                }
            }
        }

        // No match found
        http_response_code(404);
        echo "404 Not Found - Router: " . htmlspecialchars($uri);
    }

    /**
     * Executes the assigned route handler.
     * 
     * @param mixed $handler Handler function or string
     * @param array $params Extracted URI parameters
     * @return mixed
     */
    protected function executeHandler($handler, $params = [])
    {
        // If it's a closure
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        }

        // If it's a "Controller@method" string
        if (is_string($handler) && strpos($handler, '@') !== false) {
            list($controller, $method) = explode('@', $handler);
            $controller = "App\\Modules\\" . $controller;
            if (class_exists($controller)) {
                $instance = new $controller();
                return call_user_func_array([$instance, $method], $params);
            }
        }

        return null;
    }
}

