<?php

require_once __DIR__ . '/migrate_lib.php';

$dbHost = gdy_env_get('DB_HOST', 'localhost');
$dbName = gdy_env_get('DB_NAME', '');
$dbUser = gdy_env_get('DB_USER', '');
$dbPass = gdy_env_get('DB_PASS', '');
$dbCharset = gdy_env_get('DB_CHARSET', 'utf8mb4');

if ($dbName === '' || $dbUser === '') {
    fwrite(STDERR, "Missing DB_NAME/DB_USER in .env\n");
    exit(1);
}

$dsn = "mysql:host={$dbHost};dbname={$dbName};charset={$dbCharset}";

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ));
} catch (Exception $e) {
    fwrite(STDERR, "DB connection failed: {$e->getMessage()}\n");
    exit(1);
}

$migrationsDir = realpath(__DIR__ . '/../database/migrations');
list($ok, $output) = gdy_run_migrations($pdo, $migrationsDir);
echo $output;
exit($ok ? 0 : 1);
