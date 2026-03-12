<?php

try {
    $isDirect = (PHP_SAPI !== 'cli')
        && isset($_SERVER['SCRIPT_FILENAME'])
        && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__);
} catch (\Throwable $e) {
    $isDirect = false;
}

if ($isDirect) {
    require_once __DIR__ . '/../../_admin_guard.php';

    
    if (class_exists('Godyar\\Auth') && method_exists('Godyar\\Auth', 'requireRole')) {
        \Godyar\Auth::requireRole('admin');
    } else {
        if (($_SESSION['user']['role'] ?? '') !== 'admin') {
            http_response_code(403);
            exit('403 Forbidden');
        }
    }

    http_response_code(403);
    exit('403 Forbidden');
}

function gdy_queue_token_path(): string {
    $dir = dirname(__DIR__, 3) . '/cache/queue';
    if (!is_dir($dir)) {
        gdy_mkdir($dir, 0775, true);
    }
    return $dir . '/token.txt';
}

function gdy_queue_get_token(): string {
    $p = gdy_queue_token_path();
    if (!is_file($p)) return '';
    return trim((string)gdy_file_get_contents($p));
}

function gdy_queue_rotate_token(): string {
    $token = bin2hex(random_bytes(16));
    gdy_file_put_contents(gdy_queue_token_path(), $token, LOCK_EX);
    return $token;
}

function gdy_queue_has_valid_token(): bool {
    $token = (string)($_GET['token'] ?? $_SERVER['HTTP_X_QUEUE_TOKEN'] ?? '');
    if ($token === '') return false;
    $saved = gdy_queue_get_token();
    return $saved !== '' && hash_equals($saved, $token);
}
