<?php

require_once __DIR__ . '/_admin_guard.php';

if (!function_exists('h')) {
    function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

$root = realpath(__DIR__ . '/..') ?: (__DIR__ . '/..');

$checks = [];

$checks[] = [
    'label' => 'PHP Version (>= 8.0 recommended)',
    'ok'    => version_compare(PHP_VERSION, '8.0.0', '>='),
    'value' => PHP_VERSION,
];

$requiredExt = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'openssl'];
foreach ($requiredExt as $ext) {
    $checks[] = [
        'label' => 'Extension: ' . $ext,
        'ok'    => extension_loaded($ext),
        'value' => extension_loaded($ext) ? 'Loaded' : 'Missing',
    ];
}

$optionalExt = ['gd', 'fileinfo', 'curl'];
foreach ($optionalExt as $ext) {
    $checks[] = [
        'label' => 'Extension (optional): ' . $ext,
        'ok'    => extension_loaded($ext),
        'value' => extension_loaded($ext) ? 'Loaded' : 'Missing',
    ];
}

$paths = [
    'storage' => $root . '/storage',
    'storage/cache' => $root . '/storage/cache',
    'storage/logs'  => $root . '/storage/logs',
    'uploads'       => $root . '/uploads',
];
foreach ($paths as $name => $path) {
    $exists = is_dir($path);
    $writable = $exists && is_writable($path);
    $checks[] = [
        'label' => 'Writable: ' . $name,
        'ok'    => $writable,
        'value' => $exists ? ($writable ? 'Writable' : 'Not writable') : 'Missing directory',
    ];
}

$envFile = $root . '/.env';
$checks[] = [
    'label' => '.env exists (after install)',
    'ok'    => is_file($envFile),
    'value' => is_file($envFile) ? 'Present' : 'Not found',
];

$lock = $root . '/install/install.lock';
$checks[] = [
    'label' => 'Installer lock (install/install.lock)',
    'ok'    => is_file($lock),
    'value' => is_file($lock) ? 'Present' : 'Not found (install not completed?)',
];

$dbOk = null;
$dbMsg = 'Skipped';
if (function_exists('env')) {
    $dbName = (string)env('DB_NAME', '');
    $dbUser = (string)env('DB_USER', '');
    $dbHost = (string)env('DB_HOST', '127.0.0.1');
    $dbPort = (string)env('DB_PORT', '3306');

    if ($dbName !== '' && $dbUser !== '') {
        try {
            $dsn = 'mysql:host=' . $dbHost . ';port=' . $dbPort . ';dbname=' . $dbName . ';charset=utf8mb4';
            $pdo = new PDO($dsn, $dbUser, (string)env('DB_PASS', ''), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3,
            ]);
            $pdo->query('SELECT 1');
            $dbOk = true;
            $dbMsg = 'Connected';
        } catch (Throwable $e) {
            $dbOk = false;
            $dbMsg = 'Failed: ' . $e->getMessage();
        }
    } else {
        $dbOk = false;
        $dbMsg = 'DB_NAME/DB_USER not set';
    }
}
$checks[] = [
    'label' => 'Database connection',
    'ok'    => ($dbOk === null) ? true : $dbOk,
    'value' => $dbMsg,
];

$title = 'Godyar News Platform - Health Check';

?><!doctype html>
<html lang="ar" dir="rtl">
<head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($title) ?></title>
  <style nonce="<?= h($cspNonce) ?>">
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial; margin:24px; background:#f7f8fb;}
    .card{background:#fff; border:1px solid 
    h1{margin:0 0 10px; font-size:20px;}
    p{margin:0 0 14px; color:#555;}
    table{width:100%; border-collapse:collapse;}
    th,td{padding:10px 12px; border-bottom:1px solid 
    th{background:#fafbff; font-weight:600;}
    .ok{color:#0a7a32; font-weight:700;}
    .bad{color:#b00020; font-weight:700;}
    .muted{color:#666; font-size:12px;}
    code{background:#f2f4ff; padding:2px 6px; border-radius:8px;}
  </style>
</head>
<body>
  <div class="card">
    <h1><?= h($title) ?></h1>
    <p class="muted">هذه الصفحة داخل لوحة التحكم لمراجعة الجاهزية بعد التركيب (صلاحيات المجلدات، الامتدادات، الاتصال بقاعدة البيانات…)</p>

    <table>
      <thead>
        <tr>
          <th style="width:42%">الفحص</th>
          <th style="width:12%">الحالة</th>
          <th>تفاصيل</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($checks as $c): ?>
        <tr>
          <td><?= h($c['label']) ?></td>
          <td><?= $c['ok'] ? '<span class="ok">OK</span>' : '<span class="bad">FAIL</span>' ?></td>
          <td><code><?= h($c['value']) ?></code></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <p class="muted" style="margin-top:14px">
      توصية: بعد نجاح التركيب احذف مجلد <code>/install</code>، وفعّل HTTPS لضبط <code>Secure</code> للكوكيز.
    </p>
  </div>
</body>
</html>
