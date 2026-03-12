<?php
declare(strict_types=1);

require_once __DIR__ . '/../_admin_guard.php';
require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'message' => 'Method not allowed']);
    exit;
}

if (function_exists('verify_csrf')) {
    try {
        verify_csrf();
    } catch (\Throwable $e) {
        echo json_encode(['ok' => false, 'message' => 'CSRF failed']);
        exit;
    }
}

$title = trim((string)($_POST['title'] ?? ''));
$content = (string)($_POST['content'] ?? '');
$slug = trim((string)($_POST['slug'] ?? ''));
$userId = (int)($_SESSION['user']['id'] ?? 0);

if ($userId <= 0) {
    echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
    exit;
}

$dir = dirname(__DIR__, 2) . '/storage/autosave';
if (!is_dir($dir)) {
    @mkdir($dir, 0755, true);
}

$file = $dir . '/news_create_user_' . $userId . '.json';

$data = [
    'title' => $title,
    'slug' => $slug,
    'content' => $content,
    'saved_at' => date('Y-m-d H:i:s'),
];

@file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo json_encode([
    'ok' => true,
    'saved_at' => $data['saved_at']
], JSON_UNESCAPED_UNICODE);