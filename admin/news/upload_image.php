<?php

declare(strict_types=1);

require_once __DIR__ . '/../_admin_guard.php';
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/image_optimizer.php';

use Godyar\Auth;
use Godyar\SafeUploader;

if (function_exists('gdy_news_upload_json') !== true) {
    function gdy_news_upload_json(int $code, array $payload): void
    {
        if (headers_sent() === false) {
            header('Content-Type: application/json; charset=utf-8');
        }
        http_response_code($code);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        exit;
    }
}

if (Auth::isLoggedIn() !== true) {
    gdy_news_upload_json(401, ['success' => false, 'message' => 'Unauthorized']);
}

$method = function_exists('gdy_get_server_raw') === true
    ? (string) gdy_get_server_raw('REQUEST_METHOD', 'GET')
    : 'GET';
if ($method !== 'POST') {
    gdy_news_upload_json(405, ['success' => false, 'message' => 'Method not allowed']);
}

$csrf = '';
if (function_exists('gdy_get_post_raw') === true) {
    $csrf = (string) gdy_get_post_raw('csrf_token', '');
}
if ($csrf === '' && function_exists('gdy_get_server_raw') === true) {
    $csrf = (string) gdy_get_server_raw('HTTP_X_CSRF_TOKEN', '');
}
if (function_exists('verify_csrf_token') === true && verify_csrf_token($csrf) !== true) {
    gdy_news_upload_json(419, ['success' => false, 'message' => 'Invalid CSRF token']);
}

$file = function_exists('gdy_get_uploaded_file') === true
    ? gdy_get_uploaded_file('image')
    : null;
if (is_array($file) !== true) {
    gdy_news_upload_json(400, ['success' => false, 'message' => 'No image uploaded']);
}

$rootPath = defined('ROOT_PATH') === true ? (string) ROOT_PATH : '';
if ($rootPath === '') {
    $resolvedRoot = realpath(__DIR__ . '/../../');
    $rootPath = is_string($resolvedRoot) === true ? $resolvedRoot : '';
}
if ($rootPath === '') {
    gdy_news_upload_json(500, ['success' => false, 'message' => 'Project root not resolved']);
}

$destAbs = rtrim(str_replace('\\', '/', $rootPath), '/') . '/uploads';
if (function_exists('gdy_mkdir') === true) {
    gdy_mkdir($destAbs, 0755, true);
}
if (is_dir($destAbs) !== true) {
    gdy_news_upload_json(500, ['success' => false, 'message' => 'Upload directory is not available']);
}

$htaccess = $destAbs . '/.htaccess';
if (file_exists($htaccess) !== true && function_exists('gdy_file_put_contents') === true) {
    gdy_file_put_contents(
        $htaccess,
        "Options -Indexes\n<FilesMatch \"\\.(php|phtml|php\\d|phar)\\$\">\n  Require all denied\n</FilesMatch>\n"
    );
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

if (($res['success'] ?? false) !== true) {
    gdy_news_upload_json(400, [
        'success' => false,
        'message' => (string) ($res['error'] ?? 'Upload failed'),
    ]);
}

$absPath = (string) ($res['abs_path'] ?? '');
$imageInfo = ($absPath !== '' && function_exists('gdy_getimagesize') === true)
    ? gdy_getimagesize($absPath)
    : false;
if ($absPath === '' || file_exists($absPath) !== true || $imageInfo === false) {
    if ($absPath !== '' && function_exists('gdy_unlink') === true) {
        gdy_unlink($absPath);
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
    'path' => (string) ($res['rel_url'] ?? ''),
]);
