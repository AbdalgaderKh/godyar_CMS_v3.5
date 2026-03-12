<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/bootstrap.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['ok' => false, 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo = function_exists('gdy_pdo_safe') ? gdy_pdo_safe() : null;
    if (!$pdo instanceof PDO) {
        echo json_encode(['ok' => false, 'message' => 'DB unavailable'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $newsId = (int)($_POST['news_id'] ?? 0);
    $content = trim((string)($_POST['content'] ?? ''));

    if ($newsId <= 0 || $content === '') {
        echo json_encode(['ok' => false, 'message' => 'Invalid input'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $author = trim((string)($_POST['author'] ?? ''));

    
    if (function_exists('gdy_db_stmt_table_exists') && !gdy_db_stmt_table_exists($pdo, 'comments')) {
        echo json_encode(['ok' => false, 'message' => 'Comments disabled'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $pdo->prepare('INSERT INTO comments (news_id, user_id, author, content, created_at) VALUES (:n,:u,:a,:c,NOW())');
    $stmt->execute([
        ':n' => $newsId,
        ':u' => $userId,
        ':a' => $author,
        ':c' => $content,
    ]);

    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('[ajax.comments] ' . $e->getMessage());
    echo json_encode(['ok' => false, 'message' => 'Server error'], JSON_UNESCAPED_UNICODE);
}
