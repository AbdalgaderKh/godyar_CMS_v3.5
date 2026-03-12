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

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM news_revisions WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) die('Revision not found');

$newsId = (int)$row['news_id'];

try {
    $up = $pdo->prepare("
        UPDATE news
        SET title = :title,
            slug = :slug,
            excerpt = COALESCE(:excerpt, excerpt),
            content = COALESCE(:content, content),
            status = COALESCE(:status, status)
        WHERE id = :news_id
    ");
    $up->execute([
        ':title' => $row['title'],
        ':slug' => $row['slug'],
        ':excerpt' => $row['excerpt'],
        ':content' => $row['content'],
        ':status' => $row['status'],
        ':news_id' => $newsId,
    ]);
} catch (Throwable $e) {
    die('Restore failed');
}

header('Location: ../news/edit.php?id=' . $newsId . '&restored=1');
exit;