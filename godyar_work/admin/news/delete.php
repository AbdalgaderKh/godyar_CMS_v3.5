<?php
require_once __DIR__ . '/../_admin_guard.php';
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/indexnow.php';

use Godyar\Auth;

if (session_status() !== PHP_SESSION_ACTIVE) {
    gdy_session_start();
}

try {
    if (class_exists(Auth::class) && method_exists(Auth::class, 'isLoggedIn')) {
        if (!Auth::isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

Auth::requirePermission('posts.delete');

} else {
        if (empty($_SESSION['user']) || (($_SESSION['user']['role'] ?? '') === 'guest')) {
            header('Location: ../login.php');
            exit;
        }
    }
} catch (\Throwable $e) {
    error_log('[Godyar News] Auth check error in delete.php: ' . $e->getMessage());
    if (empty($_SESSION['user']) || (($_SESSION['user']['role'] ?? '') === 'guest')) {
        header('Location: ../login.php');
        exit;
    }
}

$role = (string)($_SESSION['user']['role'] ?? 'guest');
if (in_array($role, ['writer','author'], true)) {
    http_response_code(403);
    header('Location: index.php?error=forbidden');
    exit;
}

$pdo = gdy_pdo_safe();
if (($pdo instanceof PDO) === false) {
    error_log('[Godyar News] delete.php: PDO not available');
    header('Location: index.php?error=db');
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
    header('Location: index.php?error=bad_id');
    exit;
}

try {
    
    $stmt = $pdo->prepare("SELECT id, deleted_at FROM news WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $news = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$news) {
        header('Location: index.php?error=not_found');
        exit;
    }

    
    if (!empty($news['deleted_at'])) {
        header('Location: index.php?msg=already_deleted');
        exit;
    }

    
    $stmtDel = $pdo->prepare("UPDATE news SET deleted_at = NOW() WHERE id = :id LIMIT 1");
    $stmtDel->execute([':id' => (int)$news['id']]);

    
    if ($stmtDel->rowCount() === 0) {
        error_log('[Godyar News] delete.php: soft delete affected 0 rows for id=' . $news['id']);
        $stmtHard = $pdo->prepare("DELETE FROM news WHERE id = :id LIMIT 1");
        $stmtHard->execute([':id' => (int)$news['id']]);

        if ($stmtHard->rowCount() === 0) {
            error_log('[Godyar News] delete.php: hard delete also affected 0 rows for id=' . $news['id']);
            header('Location: index.php?error=no_rows');
            exit;
        }
    }

$baseUrl = '';
if (function_exists('base_url')) {
    $baseUrl = rtrim((string)base_url(), '/');
}
if ($baseUrl === '' && defined('BASE_URL')) {
    $baseUrl = rtrim((string)BASE_URL, '/');
}
if ($baseUrl === '') {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $baseUrl = rtrim($scheme . '://' . $host, '/');
}

$nid = (int)$news['id'];
$url = $baseUrl . '/news/id/' . $nid;
if (function_exists('gdy_indexnow_submit')) {
    gdy_indexnow_submit_safe($pdo, [$url, $baseUrl . '/sitemap.xml']);
}

$root = dirname(__DIR__, 2);
gdy_unlink($root . '/cache/sitemap.xml');
gdy_unlink($root . '/cache/rss.xml');

    header('Location: index.php?msg=deleted');
    exit;

} catch (\Throwable $e) {
    error_log('[Godyar News] delete.php error: ' . $e->getMessage());
    header('Location: index.php?error=exception');
    exit;
}