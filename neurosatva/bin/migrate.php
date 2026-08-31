<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/Core/Env.php';
require dirname(__DIR__) . '/app/Core/helpers.php';
require dirname(__DIR__) . '/app/Core/Database.php';

echo "==> Running Neurosatva Database Migrations...\n";

try {
    $pdo = Database::connection();
    echo "✔ Connected to database successfully.\n";

    $files = [
        dirname(__DIR__) . '/database/schema.sql',
        dirname(__DIR__) . '/database/seed.sql',
    ];

    foreach ($files as $file) {
        if (!file_exists($file)) {
            continue;
        }
        $filename = basename($file);
        echo "--> Processing {$filename}...\n";
        $sql = file_get_contents($file);
        if ($sql === false) {
            continue;
        }

        // Clean out CREATE DATABASE / USE statements so cloud-hosted single DBs run smoothly
        $sql = preg_replace('/CREATE\s+DATABASE\s+IF\s+NOT\s+EXISTS\s+[a-zA-Z0-9_]+.*?;/si', '', $sql);
        $sql = preg_replace('/USE\s+[a-zA-Z0-9_]+;/si', '', $sql);

        // Execute queries
        $pdo->exec($sql);
        echo "✔ {$filename} applied successfully.\n";
    }

    echo "==> All migrations applied successfully!\n";
} catch (PDOException $e) {
    echo "✖ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
