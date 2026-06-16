<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $path, callable|array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = rtrim($path, '/') ?: '/';

        if ($method === 'POST' && !Csrf::validateRequest()) {
            http_response_code(403);
            $errorPage = dirname(__DIR__, 2) . '/public/errors/403.php';
            if (is_file($errorPage)) {
                require $errorPage;
                return;
            }

            echo '403 - Invalid CSRF token';
            return;
        }

        foreach ($this->routes[$method] ?? [] as $route => $handler) {
            $pattern = '#^' . preg_replace('#\{[a-zA-Z_][a-zA-Z0-9_]*\}#', '([a-zA-Z0-9_-]+)', $route) . '$#';
            if (!preg_match($pattern, $path, $matches)) {
                continue;
            }

            array_shift($matches);
            $this->invoke($handler, $matches);
            return;
        }

        http_response_code(404);
        $errorPage = dirname(__DIR__, 2) . '/public/errors/404.php';
        if (is_file($errorPage)) {
            require $errorPage;
            return;
        }

        echo '404 - Page not found';
    }

    private function invoke(callable|array $handler, array $params): void
    {
        if (is_callable($handler)) {
            call_user_func_array($handler, $params);
            return;
        }

        [$controllerClass, $method] = $handler;
        $controller = new $controllerClass();
        call_user_func_array([$controller, $method], $params);
    }
}
