<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @session_start();
}

$__gdy_visitors_file = dirname(__DIR__) . '/storage/runtime_visitors.json';
$__gdy_now = time();
$__gdy_data = [];

if (is_file($__gdy_visitors_file)) {
    $__raw = @file_get_contents($__gdy_visitors_file);
    $__gdy_data = json_decode((string)$__raw, true) ?: [];
}

$__gdy_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$__gdy_data[$__gdy_ip] = $__gdy_now;

foreach ($__gdy_data as $__ip => $__ts) {
    if ((int)$__ts < $__gdy_now - 900) {
        unset($__gdy_data[$__ip]);
    }
}

if (!is_dir(dirname($__gdy_visitors_file))) {
    @mkdir(dirname($__gdy_visitors_file), 0755, true);
}

@file_put_contents($__gdy_visitors_file, json_encode($__gdy_data, JSON_UNESCAPED_UNICODE));
$GLOBALS['GDY_LIVE_VISITORS'] = count($__gdy_data);