<?php
/**
 * Router - Handles routing and dispatching requests
 */

class Router {
    private array $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => [],
        'PATCH' => []
    ];
    private $middleware = [];
    private ?string $currentController = null;

    /**
     * Register GET route
     */
    public function get(string $path, $callback): void {
        $this->addRoute('GET', $path, $callback);
    }

    /**
     * Register POST route
     */
    public function post(string $path, $callback): void {
        $this->addRoute('POST', $path, $callback);
    }

    /**
     * Register PUT route
     */
    public function put(string $path, $callback): void {
        $this->addRoute('PUT', $path, $callback);
    }

    /**
     * Register DELETE route
     */
    public function delete(string $path, $callback): void {
        $this->addRoute('DELETE', $path, $callback);
    }

    /**
     * Register PATCH route
     */
    public function patch(string $path, $callback): void {
        $this->addRoute('PATCH', $path, $callback);
    }

    /**
     * Register resource routes
     */
    public function resource(string $path, string $controller): void {
        $this->get("$path", "$controller@index");
        $this->get("$path/create", "$controller@create");
        $this->post("$path", "$controller@store");
        $this->get("$path/{id}", "$controller@show");
        $this->get("$path/{id}/edit", "$controller@edit");
        $this->put("$path/{id}", "$controller@update");
        $this->delete("$path/{id}", "$controller@destroy");
    }

    /**
     * Add middleware
     */
    public function middleware(string $middleware): self {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * Add a route
     */
    private function addRoute(string $method, string $path, $callback): void {
        $pattern = $this->convertPathToPattern($path);
        $this->routes[$method][$pattern] = [
            'callback' => $callback,
            'middleware' => $this->middleware
        ];
    }

    /**
     * Convert path to regex pattern
     */
    private function convertPathToPattern(string $path): string {
        $pattern = preg_replace('/\{([^}]+)\}/', '(?P<$1>[^/]+)', $path);
        return '/^' . str_replace('/', '\\/', $pattern) . '\/?$/';
    }

    /**
     * Dispatch the request
     */
    public function dispatch(): void {
        $method = Request::method();
        $path = '/' . Request::path();

        $route = $this->matchRoute($method, $path);

        if ($route === null) {
            Response::notFound('Route not found');
        }

        // Execute middleware
        if (!empty($route['middleware'])) {
            foreach ($route['middleware'] as $middleware) {
                $this->executeMiddleware($middleware);
            }
        }

        // Execute callback
        $this->executeCallback($route['callback'], $route['params'] ?? []);
    }

    /**
     * Match route to request
     */
    private function matchRoute(string $method, string $path): ?array {
        foreach ($this->routes[$method] ?? [] as $pattern => $route) {
            if (preg_match($pattern, $path, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, fn($k) => !is_numeric($k), ARRAY_FILTER_USE_KEY);
                return [
                    'callback' => $route['callback'],
                    'middleware' => $route['middleware'],
                    'params' => $params
                ];
            }
        }
        return null;
    }

    /**
     * Execute middleware
     */
    private function executeMiddleware(string $middleware): void {
        $middlewareClass = 'App\\Middleware\\' . ucfirst($middleware);
        if (class_exists($middlewareClass)) {
            $instance = new $middlewareClass();
            $instance->handle();
        }
    }

    /**
     * Execute callback
     */
    private function executeCallback($callback, array $params): void {
        if (is_callable($callback)) {
            call_user_func_array($callback, array_values($params));
        } elseif (is_string($callback) && strpos($callback, '@') !== false) {
            [$controller, $method] = explode('@', $callback);
            $controllerClass = 'App\\Controllers\\' . $controller;
            
            if (class_exists($controllerClass)) {
                $instance = new $controllerClass();
                if (method_exists($instance, $method)) {
                    call_user_func_array([$instance, $method], array_values($params));
                } else {
                    Response::notFound('Method not found');
                }
            } else {
                Response::notFound('Controller not found');
            }
        }
    }
}
