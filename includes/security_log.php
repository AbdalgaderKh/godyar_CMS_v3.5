<?php

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

if (!function_exists('gdy_security_log')) {
    function gdy_security_log(string $event, array $context = []): void
    {
        $enabled = getenv('GDY_SECURITY_LOG');
        if ($enabled !== false && trim((string)$enabled) === '0') {
            return;
        }

        $dir = rtrim(ROOT_PATH, '/\\') . '/storage/logs';
        if (!is_dir($dir) && function_exists('gdy_mkdir')) {
            gdy_mkdir($dir, 0755, true);
        } elseif (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        
        $safeCtx = [];
        foreach ($context as $k => $v) {
            if (is_scalar($v) || $v === null) {
                $safeCtx[(string)$k] = $v;
            } else {
                $safeCtx[(string)$k] = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        $row = [
            'ts' => gmdate('c'),
            'event' => $event,
            'ip' => $ip,
            'method' => $method,
            'uri' => $uri,
            'ua' => mb_substr((string)$ua, 0, 220),
            'ctx' => $safeCtx,
        ];

        $file = $dir . '/security.log';
        $line = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";

        if (function_exists('gdy_file_put_contents')) {
            @gdy_file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
        } else {
            @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
        }
    }
}
