<?php
declare(strict_types=1);

// Legacy category entry kept for compatibility with old links and includes.
$slug = isset($_GET['slug']) ? trim((string)$_GET['slug']) : '';
if ($slug !== '' && !headers_sent()) {
    $base = function_exists('base_url') ? rtrim((string)base_url(), '/') : '';
    if ($base === '') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string)($_SERVER['HTTP_HOST'] ?? '');
        $base = $host !== '' ? ($scheme . '://' . $host) : '';
    }
    header('Location: ' . rtrim($base, '/') . '/category/' . rawurlencode($slug), true, 301);
    exit;
}
require __DIR__ . '/frontend/category.php';
