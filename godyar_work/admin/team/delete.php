<?php
require_once __DIR__ . '/../_admin_guard.php';

require_once __DIR__ . '/../../includes/bootstrap.php';

$authFile = __DIR__ . '/../../includes/auth.php';
if (is_file($authFile) === true) {
    require_once $authFile;
}

use Godyar\Auth;

$currentPage = 'team';
$pageTitle = __('t_05b4c0a9c3', 'حذف عضو');

try {
    if (class_exists(Auth::class) && method_exists(Auth::class,'isLoggedIn')) {
        if (!Auth::isLoggedIn()) {
            header('Location: ../login.php');
            exit;
        }
    } else {
        if (empty($_SESSION['user']) || (((!empty($_SESSION['user']['role']) ? $_SESSION['user']['role'] : '')) === 'guest')) {
            header('Location: ../login.php');
            exit;
        }
    }
} catch (\Throwable $e) {
    error_log('[Admin Team Delete] Auth: ' . $e->getMessage());
    header('Location: ../login.php');
    exit;
}

$pdo = gdy_pdo_safe();
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($method !== 'POST') {
    http_response_code(405);
    header('Location: index.php?error=method');
    exit;
}
$id = (int)($_POST['id'] ?? 0);
if (function_exists('csrf_verify_any_or_die')) { csrf_verify_any_or_die(); } else { verify_csrf(); }

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

if ($pdo instanceof PDO) {
    try {
        $stmt = $pdo->prepare("DELETE FROM team_members WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
    } catch (\Throwable $e) {
        error_log('[Admin Team Delete] delete: ' . $e->getMessage());
    }
}

$accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
$isJson = (stripos($accept, 'application/json') !== false) || (strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest');
if ($isJson) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    exit;
}
header('Location: index.php?deleted=1');
exit;
