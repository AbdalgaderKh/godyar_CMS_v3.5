<?php

require_once __DIR__ . '/../../includes/bootstrap.php';
$fn = __DIR__ . '/../../includes/functions.php';
if (is_file($fn)) { require_once $fn; }
$rl = __DIR__ . '/../../includes/rate_limit.php';
if (is_file($rl)) { require_once $rl; }

if (function_exists('gdy_session_start')) {
    gdy_session_start();
} elseif (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($method !== 'POST') {
    http_response_code(405);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Method Not Allowed';
    exit;
}

if (function_exists('csrf_verify_or_die')) {
    csrf_verify_or_die('csrf_token');
}

if (function_exists('gody_rate_limit')) {
    if (!gody_rate_limit('comment_add', 10, 600)) {
        $retry = function_exists('gody_rate_limit_retry_after') ? gody_rate_limit_retry_after('comment_add') : 600;
        http_response_code(429);
        header('Retry-After: ' . (string)$retry);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Too Many Requests';
        exit;
    }
}

$pdo = function_exists('gdy_pdo_safe') ? gdy_pdo_safe() : null;
if (!($pdo instanceof PDO)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'DB unavailable';
    exit;
}

$newsId = 0;
if (isset($_POST['news_id']) && is_scalar($_POST['news_id'])) {
    $s = trim((string)$_POST['news_id']);
    if (ctype_digit($s)) { $newsId = (int)$s; }
}

$content = '';
if (isset($_POST['content']) && is_scalar($_POST['content'])) {
    $content = trim((string)$_POST['content']);
}

if ($newsId <= 0 || $content === '' || mb_strlen($content, 'UTF-8') > 4000) {
    http_response_code(422);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Invalid input';
    exit;
}

$userId = (int)($_SESSION['user']['id'] ?? ($_SESSION['user_id'] ?? 0));
$parentId = 0;
if (isset($_POST['parent_id']) && is_scalar($_POST['parent_id'])) {
    $p = trim((string)$_POST['parent_id']);
    if (ctype_digit($p)) { $parentId = (int)$p; }
}

$name = '';
$email = '';
if ($userId <= 0) {
    if (!empty($_POST['name']) && is_scalar($_POST['name'])) {
        $name = trim((string)$_POST['name']);
        if (mb_strlen($name, 'UTF-8') > 190) { $name = mb_substr($name, 0, 190, 'UTF-8'); }
    }
    if (!empty($_POST['email']) && is_scalar($_POST['email'])) {
        $email = trim((string)$_POST['email']);
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $email = ''; }
        if (strlen($email) > 190) { $email = substr($email, 0, 190); }
    }
}

try {
    $st = $pdo->prepare("SELECT status FROM news WHERE id = :id LIMIT 1");
    $st->execute([':id' => $newsId]);
    $status = (string)($st->fetchColumn() ?: '');
    if ($status === '') {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Not Found';
        exit;
    }
} catch (Throwable $e) {
    
}

$table = 'news_comments';
try {
    if (function_exists('gdy_table_exists_safe') ? !gdy_table_exists_safe($pdo, $table) : (function_exists('gdy_table_exists') && !gdy_table_exists($pdo, $table))) {
        $table = 'comments';
    }
} catch (Throwable $e) {
    
}

try {
    if ($table === 'news_comments') {
        $sql = "INSERT INTO news_comments (news_id, user_id, parent_id, name, email, status, score, created_at, body)
                VALUES (:news_id, :user_id, :parent_id, :name, :email, :status, 0, CURRENT_TIMESTAMP, :body)";
        $st = $pdo->prepare($sql);
        $st->execute([
            ':news_id' => $newsId,
            ':user_id' => $userId > 0 ? $userId : null,
            ':parent_id' => $parentId > 0 ? $parentId : null,
            ':name' => $name,
            ':email' => $email,
            ':status' => 'pending',
            ':body' => $content,
        ]);
    } else {
        
        $sql = "INSERT INTO comments (news_id, user_id, parent_id, content, status, created_at)
                VALUES (:news_id, :user_id, :parent_id, :content, :status, CURRENT_TIMESTAMP)";
        $st = $pdo->prepare($sql);
        $st->execute([
            ':news_id' => $newsId,
            ':user_id' => $userId > 0 ? $userId : null,
            ':parent_id' => $parentId > 0 ? $parentId : null,
            ':content' => $content,
            ':status' => 'pending',
        ]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'DB error';
    exit;
}

$ref = (string)($_SERVER['HTTP_REFERER'] ?? '');
if ($ref === '') {
    $base = function_exists('base_url') ? rtrim((string)base_url(), '/') : '';
    $ref = function_exists('gdy_route_news_url') ? gdy_route_news_url(['id'=>$newsId], function_exists('gdy_current_lang') ? gdy_current_lang() : 'ar') : (($base !== '' ? $base : '') . '/news/id/' . $newsId);
}

header('Location: ' . $ref . '#comments', true, 302);
exit;
