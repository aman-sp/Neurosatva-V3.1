<?php

function app_config(?string $key = null): mixed
{
    static $config = null;
    $config ??= require dirname(__DIR__, 2) . '/config/app.php';
    return $key ? ($config[$key] ?? null) : $config;
}

function path(string $uri = ''): string
{
    $base = parse_url(app_config('url'), PHP_URL_PATH) ?: '';
    return rtrim($base, '/') . '/' . ltrim($uri, '/');
}

function redirect(string $uri): never
{
    header('Location: ' . path($uri));
    exit;
}

function view(string $template, array $data = [], string $layout = 'app'): void
{
    extract($data, EXTR_SKIP);
    $viewPath = dirname(__DIR__) . '/Views/' . $template . '.php';
    $layoutPath = dirname(__DIR__) . '/Views/layouts/' . $layout . '.php';
    ob_start();
    require $viewPath;
    $content = ob_get_clean();
    require $layoutPath;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function active(string $route): string
{
    $current = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '';
    return str_ends_with($current, path($route)) ? 'active' : '';
}

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function input(string $key, mixed $default = null): mixed
{
    return trim((string) ($_POST[$key] ?? $_GET[$key] ?? $default));
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

function status_badge(string $status): string
{
    $normalized = strtolower($status);
    $class = match ($normalized) {
        'active', 'verified' => 'success',
        'pending', 'pending approval', 'pending gmail setup', 'awaiting email verification' => 'warning',
        'deactivated', 'rejected' => 'danger',
        'approved' => 'success',
        default => 'muted',
    };

    return '<span class="badge ' . $class . '">' . e(ucwords(str_replace('_', ' ', $status))) . '</span>';
}

function tutor_user_id(int|string $id): string
{
    return 'NS-TUT-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function storage_public_url(?string $path): ?string
{
    return $path ? path('/' . ltrim($path, '/')) : null;
}
