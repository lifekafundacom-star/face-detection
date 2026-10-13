<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $path = $request->path();

        foreach ($this->routes[$method] ?? [] as $route) {
            $pattern = preg_replace('#\{([^/]+)\}#', '(?P<$1>[^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';

            if (!preg_match($pattern, $path, $matches)) {
                continue;
            }

            $params = array_filter($matches, static fn ($key) => !is_int($key), ARRAY_FILTER_USE_KEY);
            $handler = $route['handler'];

            if (is_array($handler)) {
                [$controllerClass, $methodName] = $handler;
                $controller = new $controllerClass();
                $controller->{$methodName}($request, $params);
                return;
            }

            $handler($request, $params);
            return;
        }

        http_response_code(404);
        echo 'Page not found.';
    }

    private function add(string $method, string $path, $handler): void
    {
        $this->routes[$method][] = [
            'path' => rtrim($path, '/') ?: '/',
            'handler' => $handler,
        ];
    }
}
