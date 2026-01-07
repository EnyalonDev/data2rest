<?php

namespace App\Core;

class Router {
    protected $routes = [];

    public function add($method, $path, $handler) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }

    public function dispatch($method, $uri) {
        $uri = parse_url($uri, PHP_URL_PATH);
        
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $baseDir = dirname($scriptName);
        
        if (strpos($uri, $baseDir) === 0) {
            $uri = substr($uri, strlen($baseDir));
        }
        
        $rootBase = dirname($baseDir); 
        if ($rootBase !== '/' && $rootBase !== '.' && strpos($uri, $rootBase) === 0) {
            $uri = substr($uri, strlen($rootBase));
        }

        if ($uri === '') $uri = '/';
        // Normalize: always have a leading slash, no trailing unless it's just /
        $uri = '/' . trim($uri, '/');

        foreach ($this->routes as $route) {
            if ($route['method'] === $method) {
                // Convert route path to regex
                // /api/v1/{db}/{table} -> ^\/api\/v1\/([^\/]+)\/([^\/]+)$
                $pattern = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([^/]+)', $route['path']);
                $pattern = '#^' . str_replace('/', '\/', $pattern) . '$#';

                if (preg_match($pattern, $uri, $matches)) {
                    array_shift($matches); // Remove full match
                    return $this->executeHandler($route['handler'], $matches);
                }
            }
        }
        
        http_response_code(404);
        echo "404 Not Found - Router: " . htmlspecialchars($uri);
    }

    protected function executeHandler($handler, $params = []) {
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        }
        
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
