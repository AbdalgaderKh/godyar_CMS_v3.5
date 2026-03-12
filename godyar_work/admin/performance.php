<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
if (!headers_sent()) { header('Content-Type: text/html; charset=UTF-8'); }

$checks = array();
$checks[] = array('PHP Version', PHP_VERSION);
$checks[] = array('Memory Limit', ini_get('memory_limit'));
$checks[] = array('OPcache', function_exists('opcache_get_status') ? 'available' : 'not available');
$checks[] = array('PDO MySQL', extension_loaded('pdo_mysql') ? 'loaded' : 'missing');
$checks[] = array('mbstring', extension_loaded('mbstring') ? 'loaded' : 'missing');
$checks[] = array('GD', extension_loaded('gd') ? 'loaded' : 'missing');
$checks[] = array('Storage writable', is_writable(dirname(__DIR__) . '/storage') ? 'yes' : 'no');
$checks[] = array('Cache path', class_exists('Cache') ? Cache::getPath() : dirname(__DIR__) . '/cache');
$checks[] = array('Request time', function_exists('gdy_request_ms') ? gdy_request_ms().' ms' : 'n/a');

?><!doctype html>
<html lang="ar" dir="rtl">
<head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Godyar Performance</title>
<style>body{font-family:Tahoma,Arial,sans-serif;background:#f6f7fb;color:#111;margin:0;padding:24px} .wrap{max-width:980px;margin:0 auto} .card{background:#fff;border-radius:16px;padding:22px;box-shadow:0 10px 28px rgba(0,0,0,.06)} table{width:100%;border-collapse:collapse} td,th{padding:12px;border-bottom:1px solid 
</head>
<body>
<div class="wrap">
  <div class="card">
    <h1>لوحة فحص الأداء والثبات</h1>
    <p class="muted">هذه الصفحة لا تعدّل أي شيء. تعرض حالة البيئة وبعض المؤشرات السريعة فقط.</p>
    <table>
      <thead><tr><th>العنصر</th><th>القيمة</th></tr></thead>
      <tbody>
      <?php foreach ($checks as $row): ?>
        <tr><td><?php echo htmlspecialchars($row[0], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($row[1], ENT_QUOTES, 'UTF-8'); ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <h2>اقتراحات v3.4</h2>
    <ul>
      <li>تفعيل OPcache من الاستضافة إن لم يكن مفعلاً.</li>
      <li>استخدام WebP للصور الكبيرة في الصفحة الرئيسية.</li>
      <li>مراجعة X-Godyar-Cache في أدوات المطور للتأكد من عمل الكاش.</li>
      <li>ترك .htaccess الخاص بالكاش والضغط كما هو في هذه النسخة.</li>
    </ul>
  </div>
</div>
</body>
</html>
