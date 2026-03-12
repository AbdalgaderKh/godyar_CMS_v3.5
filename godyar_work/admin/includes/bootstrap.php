<?php

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 2));
}

require_once ROOT_PATH . '/includes/bootstrap.php';

if (file_exists(__DIR__ . '/lang.php')) {
    require_once __DIR__ . '/lang.php';
}

if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
    if (function_exists('gdy_session_start')) {
        gdy_session_start();
    } else {
        session_start();
    }
}
