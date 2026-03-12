<?php
declare(strict_types=1);

require_once __DIR__ . '/../_admin_guard.php';

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$checks = [];

$checks[] = ['PHP Version', PHP_VERSION, version_compare(PHP_VERSION, '8.1.0', '>=')];
$checks[] = ['GD Extension', extension_loaded('gd') ? 'Enabled' : 'Missing', extension_loaded('gd')];
$checks[] = ['DOM Extension', class_exists('DOMDocument') ? 'Enabled' : 'Missing', class_exists('DOMDocument')];
$checks[] = ['Uploads Writable', is_writable(__DIR__ . '/../../uploads') ? 'Writable' : 'Not writable', is_writable(__DIR__ . '/../../uploads')];
$checks[] = ['Cache Writable', is_dir(__DIR__ . '/../../cache') || @mkdir(__DIR__ . '/../../cache', 0755, true) ? (is_writable(__DIR__ . '/../../cache') ? 'Writable' : 'Not writable') : 'Not writable', is_writable(__DIR__ . '/../../cache')];
$checks[] = ['Editor JS', is_file(__DIR__ . '/../../assets/admin/editor/gdy-editor.js') ? 'Found' : 'Missing', is_file(__DIR__ . '/../../assets/admin/editor/gdy-editor.js')];
$checks[] = ['Editor CSS', is_file(__DIR__ . '/../../assets/admin/editor/gdy-editor.css') ? 'Found' : 'Missing', is_file(__DIR__ . '/../../assets/admin/editor/gdy-editor.css')];
$checks[] = ['Sitemap', is_file(__DIR__ . '/../../sitemap.php') ? 'Found' : 'Missing', is_file(__DIR__ . '/../../sitemap.php')];

?><!doctype html>
<html lang="ar" dir="rtl">
<head><meta charset="utf-8">
<title>فحص النظام</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{font-family:Tahoma,Arial,sans-serif;background:#f8fafc;margin:0;padding:24px;color:#0f172a}
.wrap{max-width:900px;margin:auto;background:#fff;border:1px solid #e5e7eb;border-radius:16px;overflow:hidden}
.head{padding:16px 20px;background:#0f172a;color:#fff;font-weight:700}
table{width:100%;border-collapse:collapse}
th,td{padding:12px;border-bottom:1px solid #e5e7eb;text-align:right}
.ok{color:#065f46;font-weight:700}
.bad{color:#991b1b;font-weight:700}
</style>
</head>
<body>
<div class="wrap">
<div class="head">فحص النظام</div>
<table>
<thead><tr><th>البند</th><th>الحالة</th><th>النتيجة</th></tr></thead>
<tbody>
<?php foreach ($checks as $row): ?>
<tr>
<td><?= e($row[0]) ?></td>
<td><?= e($row[1]) ?></td>
<td class="<?= $row[2] ? 'ok' : 'bad' ?>"><?= $row[2] ? 'سليم' : 'يحتاج إصلاح' ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</body>
</html>