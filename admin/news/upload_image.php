<?php

declare(strict_types=1);

require_once __DIR__ . '/../_admin_guard.php';
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/image_optimizer.php';

use Godyar\Auth;
use Godyar\SafeUploader;

header('Content-Type: application/json; charset=utf-8');

function gdy_news_upload_json(int $code, array $payload): void
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    exit;
}

if (!Auth::isLoggedIn()) {
    gdy_news_upload_json(401, ['success' => false, 'message' => 'Unauthorized']);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    gdy_news_upload_json(405, ['success' => false, 'message' => 'Method not allowed']);
}

$csrf = (string)($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
if (function_exists('verify_csrf_token') && !verify_csrf_token($csrf)) {
    gdy_news_upload_json(419, ['success' => false, 'message' => 'Invalid CSRF token']);
}

if (!isset($_FILES['image']) || !is_array($_FILES['image'])) {
    gdy_news_upload_json(400, ['success' => false, 'message' => 'No image uploaded']);
}

$file = $_FILES['image'];
$root = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2);
$destAbs = rtrim((string)$root, "/\\") . '/uploads';

if (!is_dir($destAbs)) {
    if (function_exists('gdy_mkdir')) {
        gdy_mkdir($destAbs, 0775, true);
    } else {
        @mkdir($destAbs, 0775, true);
    }
}

$htaccess = rtrim($destAbs, "/\\") . '/.htaccess';
if (!is_file($htaccess)) {
    @file_put_contents($htaccess, "Options -Indexes\n<FilesMatch \"\\.(php|phtml|php\\d|phar)\\$\">\n  Require all denied\n</FilesMatch>\n");
}

$res = SafeUploader::upload($file, [
    'max_bytes' => 8 * 1024 * 1024,
    'allowed_ext' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
    'allowed_mime' => [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp'],
    ],
    'dest_abs_dir' => $destAbs,
    'url_prefix' => '/uploads',
    'prefix' => 'news_',
]);

if (empty($res['success'])) {
    gdy_news_upload_json(400, [
        'success' => false,
        'message' => (string)($res['error'] ?? 'Upload failed'),
    ]);
}

$absPath = (string)($res['abs_path'] ?? '');
if ($absPath === '' || !is_file($absPath) || @getimagesize($absPath) === false) {
    if ($absPath !== '' && is_file($absPath)) {
        @unlink($absPath);
    }
    gdy_news_upload_json(400, ['success' => false, 'message' => 'Invalid image file']);
}

try {
    gdy_convert_to_webp($absPath);
} catch (Throwable $e) {
    // Keep the original uploaded image even if optimization fails.
}

gdy_news_upload_json(200, [
    'success' => true,
    'path' => (string)($res['rel_url'] ?? ''),
]);
