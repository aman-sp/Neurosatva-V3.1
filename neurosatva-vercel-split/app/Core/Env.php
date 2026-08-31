<?php

function env(string $key, mixed $default = null): mixed
{
    static $loaded = false;

    if (!$loaded) {
        $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';
        if (is_readable($path)) {
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }

                [$name, $value] = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value, " \t\n\r\0\x0B\"'");
                $_ENV[$name] = $value;
                putenv($name . '=' . $value);
            }
        }
        $loaded = true;
    }

    $value = $_ENV[$key] ?? getenv($key);
    return $value === false || $value === null || $value === '' ? $default : $value;
}
