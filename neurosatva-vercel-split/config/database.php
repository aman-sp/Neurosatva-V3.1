<?php

$dbUrl = env('DATABASE_URL') ?: env('MYSQL_URL');

if ($dbUrl) {
    $parsed = parse_url($dbUrl);
    return [
        'host' => $parsed['host'] ?? '127.0.0.1',
        'port' => (string) ($parsed['port'] ?? '3306'),
        'database' => ltrim($parsed['path'] ?? 'neurosatva', '/'),
        'username' => $parsed['user'] ?? 'root',
        'password' => $parsed['pass'] ?? '',
    ];
}

return [
    'host' => env('DB_HOST', env('MYSQLHOST', '127.0.0.1')),
    'port' => env('DB_PORT', env('MYSQLPORT', '3306')),
    'database' => env('DB_NAME', env('MYSQLDATABASE', 'neurosatva')),
    'username' => env('DB_USER', env('MYSQLUSER', 'root')),
    'password' => env('DB_PASS', env('MYSQLPASSWORD', '')),
];

