<?php
require_once __DIR__ . '/../_admin_guard.php';
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

$currentPage = 'pages';
$pageTitle = __('t_b10973702c', 'حذف صفحة');

try {
    if (class_exists(Auth::class) && method_exists(Auth::class, 'isLoggedIn')) {
        if (!Auth::isLoggedIn()) {
            header('Location: ' . (function_exists('gdy_base_url') ? rtrim((string)gdy_base_url(), '/') : '') . '/admin/login');
            exit;
        }
    } else {
        if (empty($_SESSION['user']) || (($_SESSION['user']['role'] ?? '') === 'guest')) {
            header('Location: ' . (function_exists('gdy_base_url') ? rtrim((string)gdy_base_url(), '/') : '') . '/admin/login');
            exit;
        }
    }
} catch (\Throwable $e) {
    error_log('[Godyar Pages Delete] Auth check error: ' . $e->getMessage());
    if (empty($_SESSION['user']) || (($_SESSION['user']['role'] ?? '') === 'guest')) {
        header('Location: ' . (function_exists('gdy_base_url') ? rtrim((string)gdy_base_url(), '/') : '') . '/admin/login');
        exit;
    }
}

$pdo = gdy_pdo_safe();
if (($pdo instanceof PDO) === false) {
    header('Location: index.php?dberror=1');
    exit;
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($method !== 'POST') {
    http_response_code(405);
    header('Location: index.php?error=method');
    exit;
}
if (function_exists('csrf_verify_any_or_die')) { csrf_verify_any_or_die(); }

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    header('Location: index.php');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM pages WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    header('Location: index.php?deleted=1');
    exit;
} catch (\Throwable $e) {
    error_log('[Godyar Pages Delete] Delete error: ' . $e->getMessage());
    header('Location: index.php?deleted=0');
    exit;
}