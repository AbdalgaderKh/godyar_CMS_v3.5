<?php
require_once __DIR__ . '/../../_admin_guard.php';

if (!function_exists('h')) {
  function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

$root = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 4);
$logFile = rtrim((string)$root, '/\\') . '/storage/logs/php.log';
$lines = 200;

$content = '';
if (is_file($logFile)) {
  $data = @file($logFile, FILE_IGNORE_NEW_LINES);
  if (is_array($data)) {
    $tail = array_slice($data, -$lines);
    $content = implode("\n", $tail);
  }
}

$pageTitle = 'سجل الأخطاء';
include __DIR__ . '/../../layout/header.php';
?>
<div class="admin-content">
  <div class="gdy-page-header mb-3">
    <h1 class="gdy-page-title">سجل الأخطاء (آخر <?php echo (int)$lines; ?> سطر)</h1>
    <div class="gdy-page-subtitle">الملف: <?php echo h($logFile); ?></div>
  </div>

  <?php if ($content === ''): ?>
    <div class="gdy-card p-3">لا يوجد سجلات حتى الآن (أو لا يمكن قراءة الملف).</div>
  <?php else: ?>
    <div class="gdy-card p-0">
      <pre style="margin:0;padding:14px;white-space:pre-wrap;word-break:break-word;max-height:70vh;overflow:auto;"><?php echo h($content); ?></pre>
    </div>
  <?php endif; ?>

  <div class="mt-3">
    <a class="btn btn-gdy btn-gdy-outline" href="<?php echo h(($adminBase ?? '/admin') . '/'); ?>">رجوع</a>
  </div>
</div>
<?php include __DIR__ . '/../../layout/footer.php'; ?>
