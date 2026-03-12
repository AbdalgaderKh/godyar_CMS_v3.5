<?php

$__root = realpath(__DIR__ . '/..');
if ($__root === false) {
    $__root = dirname(__DIR__);
}

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', $__root);
}

require_once ROOT_PATH . '/includes/bootstrap.php';

if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
    if (function_exists('gdy_session_start')) {
        gdy_session_start();
    } else {
        session_start();
    }
}

if (!function_exists('__')) {
    function __(string $key, $vars = null, ?string $fallback = null): string {
        if (is_string($vars) && $fallback === null) {
            $fallback = $vars;
            $vars = null;
        }
        $text = $fallback ?? $key;
        if (is_array($vars)) {
            foreach ($vars as $k => $v) {
                $text = str_replace('{' . $k . '}', (string)$v, $text);
            }
        }
        return $text;
    }
}

if (!function_exists('h')) {
    function h(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    if (function_exists('gdy_pdo_safe')) {
        $pdo = gdy_pdo_safe();
    } elseif (function_exists('db')) {
        $pdo = db();
    }
}

$__isLogged = false;

if (!empty($_SESSION['user']) && is_array($_SESSION['user'])) {
    $__isLogged = true;
}
if (!empty($_SESSION['admin']) && is_array($_SESSION['admin'])) {
    $__isLogged = true;
}

if (!$__isLogged) {
    
    $login = '/login';
    if (is_file(ROOT_PATH . '/admin/login.php')) {
        $login = '/admin/login.php';
    } elseif (is_file(ROOT_PATH . '/login.php')) {
        $login = '/login.php';
    }
    header('Location: ' . $login);
    exit;
}

$userRole = (string)($_SESSION['user']['role'] ?? ($_SESSION['admin']['role'] ?? ''));