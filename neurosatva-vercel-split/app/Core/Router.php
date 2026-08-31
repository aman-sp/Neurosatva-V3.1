<?php

final class Router
{
    private array $routes = [];

    public function get(string $uri, array $handler): void
    {
        $this->routes['GET'][$uri] = $handler;
    }

    public function post(string $uri, array $handler): void
    {
        $this->routes['POST'][$uri] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $base = parse_url(app_config('url'), PHP_URL_PATH) ?: '';
        $path = '/' . trim(preg_replace('#^' . preg_quote(rtrim($base, '/'), '#') . '#', '', $uri), '/');
        $path = $path === '/' ? '/' : rtrim($path, '/');

        $handler = $this->routes[$method][$path] ?? null;
        if (!$handler) {
            http_response_code(404);
            view('errors/404', ['title' => 'Page not found'], 'auth');
            return;
        }

        [$class, $methodName] = $handler;
        (new $class())->$methodName();
    }
}
