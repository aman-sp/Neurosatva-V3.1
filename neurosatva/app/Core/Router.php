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

    public function put(string $uri, array $handler): void
    {
        $this->routes['PUT'][$uri] = $handler;
    }

    public function delete(string $uri, array $handler): void
    {
        $this->routes['DELETE'][$uri] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $base = parse_url(app_config('url'), PHP_URL_PATH) ?: '';
        $path = '/' . trim(preg_replace('#^' . preg_quote(rtrim($base, '/'), '#') . '#', '', $uri), '/');
        $path = $path === '/' ? '/' : rtrim($path, '/');

        $handler = $this->routes[$method][$path] ?? null;
        $params = [];
        if (!$handler) {
            foreach ($this->routes[$method] ?? [] as $route => $candidate) {
                $paramNames = [];
                $patternSource = preg_replace_callback('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', function (array $match) use (&$paramNames): string {
                    $paramNames[] = $match[1];
                    return '___PARAM_' . count($paramNames) . '___';
                }, $route);
                $pattern = preg_quote($patternSource, '#');
                foreach ($paramNames as $index => $name) {
                    $pattern = str_replace('___PARAM_' . ($index + 1) . '___', '(?P<' . $name . '>[^/]+)', $pattern);
                }
                $pattern = '#^' . $pattern . '$#';
                if (preg_match($pattern, $path, $matches)) {
                    $handler = $candidate;
                    foreach ($matches as $key => $value) {
                        if (!is_int($key)) {
                            $params[$key] = $value;
                        }
                    }
                    break;
                }
            }
        }
        if (!$handler) {
            http_response_code(404);
            view('errors/404', ['title' => 'Page not found'], 'auth');
            return;
        }

        [$class, $methodName] = $handler;
        (new $class())->$methodName(...array_values($params));
    }
}
