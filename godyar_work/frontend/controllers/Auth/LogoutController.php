<?php

require_once __DIR__ . '/../../../includes/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    if (function_exists('gdy_session_start')) {
        gdy_session_start();
    } else {
        if (!headers_sent()) { session_start(); }
    }
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'] ?? '/',
        $params['domain'] ?? '',
        (bool)($params['secure'] ?? false),
        (bool)($params['httponly'] ?? true)
    );
}

session_destroy();

$baseUrl = function_exists('base_url') ? rtrim((string)base_url(), '/') : '';
header('Location: ' . ($baseUrl !== '' ? $baseUrl . '/' : '/'));
exit;
