<?php

if (!defined('GODYAR_ROOT')) {
    define('GODYAR_ROOT', dirname(__DIR__));
}

$__boot = __DIR__ . '/bootstrap.php';
if (is_file($__boot)) { require_once $__boot; }
if (function_exists('in') === false) {
    
    function in($v): string {
        $v = is_string($v) ? $v : (is_null($v) ? '' : (string)$v);
        return trim($v);
    }
}

if (function_exists('out') === false) {
    
    function out($v): string {
        $v = is_string($v) ? $v : (is_null($v) ? '' : (string)$v);
        return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    }
}

if (function_exists('get_str') === false) {
    function get_str(string $key, string $default = ''): string {
        return isset($_GET[$key]) ? in($_GET[$key]) : $default;
    }
}

if (function_exists('post_str') === false) {
    function post_str(string $key, string $default = ''): string {
        return isset($_POST[$key]) ? in($_POST[$key]) : $default;
    }
}

