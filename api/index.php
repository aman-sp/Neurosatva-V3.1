<?php

declare(strict_types=1);

// Vercel PHP entry point
// Forward all requests to the application's public/index.php

// Register fatal error shutdown handler
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_log(sprintf('PHP Fatal Error: %s in %s on line %d', $error['message'], $error['file'], $error['line']));
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
        }
        echo '<div style="font-family: sans-serif; padding: 2rem; background: #0f172a; color: #f8fafc; min-height: 100vh;">';
        echo '<h2 style="color: #ef4444;">Server Error (Fatal)</h2>';
        echo '<p><strong>Message:</strong> ' . htmlspecialchars($error['message']) . '</p>';
        echo '<p><strong>File:</strong> ' . htmlspecialchars($error['file']) . ':' . $error['line'] . '</p>';
        echo '</div>';
    }
});

try {
    $entry = dirname(__DIR__) . '/neurosatva/public/index.php';
    if (!file_exists($entry)) {
        throw new RuntimeException("Application entry point not found at {$entry}");
    }
    require $entry;
} catch (Throwable $e) {
    error_log(sprintf('Uncaught Exception: %s in %s:%d\n%s', $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()));
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    echo '<div style="font-family: sans-serif; padding: 2rem; background: #0f172a; color: #f8fafc; min-height: 100vh;">';
    echo '<h2 style="color: #ef4444;">Application Exception</h2>';
    echo '<p><strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><strong>File:</strong> ' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</p>';
    echo '<pre style="background: #1e293b; padding: 1rem; border-radius: 6px; overflow: auto;">' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '</div>';
}

