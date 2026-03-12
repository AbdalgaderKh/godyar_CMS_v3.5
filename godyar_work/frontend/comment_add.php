<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

if (function_exists('verify_csrf')) {
    try {
        verify_csrf();
    } catch (\Throwable $e) {
        $_SESSION['error_message'] = 'انتهت صلاحية الجلسة، أعد المحاولة.';
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
        exit;
    }
}

$pdo = function_exists('gdy_pdo_safe') ? gdy_pdo_safe() : null;
if (!($pdo instanceof PDO)) {
    $_SESSION['error_message'] = 'قاعدة البيانات غير متاحة.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
    exit;
}

$newsId = (int)($_POST['news_id'] ?? 0);
$content = trim((string)($_POST['content'] ?? ''));
$authorName = trim((string)($_POST['author_name'] ?? ($_SESSION['user']['name'] ?? 'زائر')));
$userId = (int)($_SESSION['user']['id'] ?? 0);

if ($newsId <= 0 || $content === '') {
    $_SESSION['error_message'] = 'يرجى كتابة التعليق أولًا.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
    exit;
}

try {
    $hasNewsComments = false;
    $hasComments = false;

    try {
        $st = $pdo->query("SHOW TABLES LIKE 'news_comments'");
        $hasNewsComments = (bool)$st->fetchColumn();
    } catch (\Throwable $e) {}

    try {
        $st = $pdo->query("SHOW TABLES LIKE 'comments'");
        $hasComments = (bool)$st->fetchColumn();
    } catch (\Throwable $e) {}

    if ($hasNewsComments) {
        $sql = "INSERT INTO news_comments (news_id, user_id, name, body, status, created_at)
                VALUES (:news_id, :user_id, :name, :body, :status, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':news_id' => $newsId,
            ':user_id' => $userId > 0 ? $userId : null,
            ':name'    => $authorName !== '' ? $authorName : 'زائر',
            ':body'    => $content,
            ':status'  => 'approved',
        ]);
    } elseif ($hasComments) {
        $sql = "INSERT INTO comments (news_id, author_name, content, status, created_at)
                VALUES (:news_id, :author_name, :content, :status, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':news_id'     => $newsId,
            ':author_name' => $authorName !== '' ? $authorName : 'زائر',
            ':content'     => $content,
            ':status'      => 'approved',
        ]);
    } else {
        throw new RuntimeException('No comments table found');
    }

    $_SESSION['success_message'] = 'تم إرسال التعليق بنجاح.';
} catch (\Throwable $e) {
    error_log('Comment add failed: ' . $e->getMessage());
    $_SESSION['error_message'] = 'تعذر حفظ التعليق.';
}

header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
exit;