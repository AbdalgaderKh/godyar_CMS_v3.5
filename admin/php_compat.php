<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$checks = array();
$checks[] = array('PHP Version', PHP_VERSION, version_compare(PHP_VERSION, '7.4.0', '>=') ? 'ok' : 'warn');
$checks[] = array('PDO', extension_loaded('pdo') ? 'loaded' : 'missing', extension_loaded('pdo') ? 'ok' : 'fail');
$checks[] = array('PDO MySQL', extension_loaded('pdo_mysql') ? 'loaded' : 'missing', extension_loaded('pdo_mysql') ? 'ok' : 'fail');
$checks[] = array('mbstring', extension_loaded('mbstring') ? 'loaded' : 'missing', extension_loaded('mbstring') ? 'ok' : 'warn');
$checks[] = array('GD', extension_loaded('gd') ? 'loaded' : 'missing', extension_loaded('gd') ? 'ok' : 'warn');
$checks[] = array('OpenSSL', extension_loaded('openssl') ? 'loaded' : 'missing', extension_loaded('openssl') ? 'ok' : 'warn');
header('Content-Type: text/html; charset=UTF-8');
?><!doctype html>
<html lang="ar" dir="rtl"><head><meta charset="utf-8"><title>فحص التوافق</title>
<style>body{font-family:Arial,sans-serif;background:#f6f8fb;padding:24px}table{border-collapse:collapse;width:100%;background:#fff}th,td{border:1px solid 
<h1>فحص توافق البيئة</h1>
<p>هذه الصفحة تساعدك في معرفة حالة البيئة الحالية للموقع.</p>
<table><tr><th>العنصر</th><th>القيمة</th><th>الحالة</th></tr>
<?php foreach ($checks as $row): ?>
<tr><td><?php echo htmlspecialchars($row[0], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($row[1], ENT_QUOTES, 'UTF-8'); ?></td><td class="<?php echo htmlspecialchars($row[2], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($row[2], ENT_QUOTES, 'UTF-8'); ?></td></tr>
<?php endforeach; ?>
</table>
<p style="margin-top:16px">مهم: إذا كانت نسخة CLI مختلفة عن نسخة الويب، استخدم صفحة الترقية من لوحة التحكم بدل أوامر الطرفية.</p>
</body></html>
