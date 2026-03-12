<?php

declare(strict_types=1);

require_once __DIR__ . '/../_admin_guard.php';
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/classes/SafeUploader.php';

gdy_header('Content-Type: application/json; charset=utf-8');

$respond = static function (int $status, array $payload): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
};

$authOk = class_exists('Godyar\\Auth')
    ? (\Godyar\Auth::isLoggedIn() === true)
    : (!empty($_SESSION['user_id']));

if ($authOk !== true) {
    $respond(401, ['success' => false, 'error' => 'غير مصرح']);
}

$requestMethod = gdy_request_method();
if ($requestMethod !== 'POST') {
    $respond(405, ['success' => false, 'error' => 'Method not allowed']);
}

$csrf = trim(gdy_post_var('csrf_token', ''));
if ($csrf === '') {
    $csrf = trim(gdy_server_var('HTTP_X_CSRF_TOKEN', ''));
}
if (function_exists('verify_csrf_token') === true && verify_csrf_token($csrf) !== true) {
    $respond(403, ['success' => false, 'error' => 'CSRF']);
}

$file = function_exists('gdy_uploaded_file') ? gdy_uploaded_file('image') : [];
if ($file === [] || ($file['tmp_name'] ?? '') === '') {
    $respond(400, ['success' => false, 'error' => 'لا يوجد ملف مرفوع']);
}

$root = defined('ROOT_PATH') === true ? (string)ROOT_PATH : gdy_dirname(__DIR__, 2);
$root = rtrim(str_replace('\\', '/', $root), '/');
$destAbs = $root . '/uploads/news';
$urlPrefix = '/uploads/news';

if (gdy_is_dir($destAbs) === false) {
    gdy_mkdir($destAbs, 0755, true);
}

$htaccess = $destAbs . '/.htaccess';
if (gdy_is_file($htaccess) === false) {
    $rules = "Options -Indexes\n<FilesMatch \"\\.(php|phtml|php\\d|phar)\\$\">\n  Require all denied\n</FilesMatch>\n";
    gdy_file_put_contents($htaccess, $rules, LOCK_EX);
}

$res = \Godyar\SafeUploader::upload($file, [
    'dest_abs_dir' => $destAbs,
    'url_prefix' => $urlPrefix,
    'max_bytes' => 5 * 1024 * 1024,
    'allowed_ext' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
    'allowed_mime' => [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
        'gif' => ['image/gif'],
    ],
    'prefix' => 'news_',
]);

if (($res['success'] ?? false) !== true) {
    $respond(400, ['success' => false, 'error' => (string)($res['error'] ?? 'failed')]);
}

$absPath = (string)($res['abs_path'] ?? '');
$imageOk = false;
if ($absPath !== '' && gdy_is_file($absPath) === true) {
    $imageInfo = gdy_getimagesize($absPath);
    $imageOk = ($imageInfo !== false);
}
if ($imageOk !== true) {
    if ($absPath !== '' && gdy_is_file($absPath) === true) {
        gdy_unlink($absPath);
    }
    $respond(400, ['success' => false, 'error' => 'Invalid image']);
}

$base = function_exists('base_url') === true ? rtrim((string)base_url(), '/') : '';
$relUrl = (string)($res['rel_url'] ?? '');
$url = $base !== '' ? ($base . $relUrl) : $relUrl;

$respond(200, [
    'success' => true,
    'url' => $url,
    'path' => $relUrl,
]);
