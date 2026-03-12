<?php
declare(strict_types=1);

require_once __DIR__ . '/../_admin_guard.php';
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth.php';

use Godyar\Auth;

if (!Auth::isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$pdo = function_exists('gdy_pdo_safe') ? gdy_pdo_safe() : null;
if (!($pdo instanceof PDO)) {
    die('Database not available');
}

$table = (string)($_GET['table'] ?? '');
$id = (int)($_GET['id'] ?? 0);
$action = (string)($_GET['action'] ?? '');

if (!in_array($table, ['news_comments', 'comments'], true) || $id <= 0) {
    header('Location: index.php');
    exit;
}

try {
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE {$table} SET status = 'approved' WHERE id = ?");
        $stmt->execute([$id]);
    } elseif ($action === 'spam') {
        $stmt = $pdo->prepare("UPDATE {$table} SET status = 'spam' WHERE id = ?");
        $stmt->execute([$id]);
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM {$table} WHERE id = ?");
        $stmt->execute([$id]);
    }
} catch (Throwable $e) {
    error_log('Comment moderate failed: ' . $e->getMessage());
}

header('Location: index.php');
exit;