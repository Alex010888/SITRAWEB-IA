<?php

namespace App\Core;

/**
 * Router básico (GET/POST) con soporte de {param}.
 */
final class Router
{
    /** @var array<int, array{method:string, pattern:string, handler:string}> */
    private array $routes = [];

    public function get(string $path, string $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, string $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, string $handler): void
    {
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $path): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== strtoupper($method)) {
                continue;
            }

            if (!preg_match($route['pattern'], $path, $matches)) {
                continue;
            }

            [$controllerName, $action] = explode('@', $route['handler'], 2);
            $controllerClass = 'App\\Controllers\\' . $controllerName;

            if (!class_exists($controllerClass)) {
                break;
            }

            $controller = new $controllerClass();
            if (!method_exists($controller, $action)) {
                break;
            }

            $params = [];
            foreach ($matches as $k => $v) {
                if (is_string($k)) {
                    $params[$k] = $v;
                }
            }

            call_user_func_array([$controller, $action], [$params]);
            return;
        }

        http_response_code(404);
        $controller = new \App\Controllers\HomeController();
        $controller->notFound();
    }
}

