<?php

$MAX_W = 2400;
$CACHE_DIR = __DIR__ . '/cache/img';

$src = (string)($_GET['src'] ?? '');
$w   = (int)($_GET['w'] ?? 0);

if ($src === '') {
  http_response_code(400);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Missing src";
  exit;
}

$src = urldecode($src);
$src = str_replace('\\', '/', $src);
$src = ltrim($src, '/');

if (!preg_match('~^uploads/~', $src)) {
  http_response_code(403);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Forbidden";
  exit;
}

if (strpos($src, '..') !== false) {
  http_response_code(403);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Forbidden";
  exit;
}

$path = __DIR__ . '/' . $src;
if (!is_file($path)) {
  http_response_code(404);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Not found";
  exit;
}

$w = max(0, min($MAX_W, $w));

if ($w <= 0) {
  $mime = mime_content_type($path) ?: 'application/octet-stream';
  header('Content-Type: ' . $mime);
  header('Cache-Control: public, max-age=31536000, immutable');
  header('X-Content-Type-Options: nosniff');
  readfile($path);
  exit;
}

$mtime = (string)@filemtime($path);
$cacheKey = sha1($src . '|' . $w . '|' . $mtime);
if (!is_dir($CACHE_DIR)) {
  @mkdir($CACHE_DIR, 0775, true);
}
$cacheWebp = $CACHE_DIR . '/' . $cacheKey . '.webp';
$cacheJpg  = $CACHE_DIR . '/' . $cacheKey . '.jpg';

if (is_file($cacheWebp)) {
  header('Content-Type: image/webp');
  header('Cache-Control: public, max-age=31536000, immutable');
  header('X-Content-Type-Options: nosniff');
  readfile($cacheWebp);
  exit;
}
if (is_file($cacheJpg)) {
  header('Content-Type: image/jpeg');
  header('Cache-Control: public, max-age=31536000, immutable');
  header('X-Content-Type-Options: nosniff');
  readfile($cacheJpg);
  exit;
}

if (!extension_loaded('gd')) {
  $mime = mime_content_type($path) ?: 'application/octet-stream';
  header('Content-Type: ' . $mime);
  header('Cache-Control: public, max-age=31536000, immutable');
  header('X-Content-Type-Options: nosniff');
  readfile($path);
  exit;
}

$info = @getimagesize($path);
if (!$info || empty($info[0]) || empty($info[1])) {
  http_response_code(415);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Unsupported";
  exit;
}

[$ow, $oh] = [(int)$info[0], (int)$info[1]];
$nw = min($w, $ow);
$nh = (int)max(1, round(($oh * $nw) / $ow));

$type = $info[2] ?? IMAGETYPE_JPEG;

$srcImg = null;
switch ($type) {
  case IMAGETYPE_JPEG:
    $srcImg = @imagecreatefromjpeg($path);
    break;
  case IMAGETYPE_PNG:
    $srcImg = @imagecreatefrompng($path);
    break;
  case IMAGETYPE_GIF:
    $srcImg = @imagecreatefromgif($path);
    break;
  case IMAGETYPE_WEBP:
    if (function_exists('imagecreatefromwebp')) $srcImg = @imagecreatefromwebp($path);
    break;
}

if (!$srcImg) {
  $mime = mime_content_type($path) ?: 'application/octet-stream';
  header('Content-Type: ' . $mime);
  header('Cache-Control: public, max-age=31536000, immutable');
  header('X-Content-Type-Options: nosniff');
  readfile($path);
  exit;
}

$dstImg = imagecreatetruecolor($nw, $nh);

if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF], true)) {
  imagealphablending($dstImg, false);
  imagesavealpha($dstImg, true);
  $transparent = imagecolorallocatealpha($dstImg, 0, 0, 0, 127);
  imagefilledrectangle($dstImg, 0, 0, $nw, $nh, $transparent);
}

imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $nw, $nh, $ow, $oh);

if (function_exists('imagewebp')) {
  @imagewebp($dstImg, $cacheWebp, 82);
  if (is_file($cacheWebp)) {
    imagedestroy($srcImg);
    imagedestroy($dstImg);
    header('Content-Type: image/webp');
    header('Cache-Control: public, max-age=31536000, immutable');
    header('X-Content-Type-Options: nosniff');
    readfile($cacheWebp);
    exit;
  }
}

@imagejpeg($dstImg, $cacheJpg, 85);

imagedestroy($srcImg);
imagedestroy($dstImg);

if (is_file($cacheJpg)) {
  header('Content-Type: image/jpeg');
  header('Cache-Control: public, max-age=31536000, immutable');
  header('X-Content-Type-Options: nosniff');
  readfile($cacheJpg);
  exit;
}

$mime = mime_content_type($path) ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Cache-Control: public, max-age=31536000, immutable');
header('X-Content-Type-Options: nosniff');
readfile($path);
