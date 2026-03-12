<?php

$envFile = '';

$existing = getenv('ENV_FILE');
if (is_string($existing) && $existing !== '') { return; }

$serverExisting = $_SERVER['ENV_FILE'] ?? '';
if (is_string($serverExisting) && $serverExisting !== '') { return; }

if (is_string($envFile) && $envFile !== '' && is_file($envFile)) {
    putenv('ENV_FILE=' . $envFile);
    $_SERVER['ENV_FILE'] = $envFile;
    $_ENV['ENV_FILE'] = $envFile;
}
