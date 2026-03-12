<?php
require_once dirname(__DIR__, 2) . '/includes/runtime_translation_audit.php';

$storageFile = dirname(__DIR__, 2) . '/storage/runtime_translation_audit.json';
$rows = array();
if (is_file($storageFile)) {
    $raw = @file_get_contents($storageFile);
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) $rows = $decoded;
}
?><!DOCTYPE html>
<html>
<head><meta charset="utf-8">
<title>Runtime Translation Audit</title>
<style>
body{font-family:Arial,sans-serif;background:#f6f7f9;margin:0;padding:32px}
.wrap{max-width:1100px;margin:0 auto}
.card{background:#fff;border-radius:14px;padding:22px;box-shadow:0 8px 24px rgba(0,0,0,.06);margin-bottom:18px}
.url{font-weight:bold}
.meta{color:#5b6472;font-size:13px;margin:6px 0 14px}
.badge{display:inline-block;background:#eef7f3;color:#127a52;padding:4px 10px;border-radius:999px;font-size:12px}
ul{margin:8px 0 0 20px}
li{margin:4px 0}
code{background:#f2f4f8;padding:2px 6px;border-radius:6px}
.actions a{display:inline-block;margin:4px 8px 4px 0;padding:8px 12px;background:#127a52;color:#fff;text-decoration:none;border-radius:10px}
.small{font-size:13px;color:#666}
</style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <h1>Runtime Translation Audit</h1>
    <p>يفحص النصوص العربية الظاهرة فعليًا داخل صفحات <code>/en/</code> و<code>/fr/</code> من داخل الأدمن، بدون الاعتماد على <code>/tools</code>.</p>
    <div class="actions">
      <a href="run_runtime_audit.php?lang=en&url=https://example.com/en/">افحص /en/</a>
      <a href="run_runtime_audit.php?lang=en&url=https://example.com/en/login">افحص /en/login</a>
      <a href="run_runtime_audit.php?lang=en&url=https://example.com/en/register">افحص /en/register</a>
      <a href="run_runtime_audit.php?lang=en&url=https://example.com/en/search">افحص /en/search</a>
      <a href="run_runtime_audit.php?lang=fr&url=https://example.com/fr/">افحص /fr/</a>
    </div>
    <p class="small">بعد كل تشغيل ستُحفظ النتيجة داخل <code>storage/runtime_translation_audit.json</code>.</p>
  </div>

  <?php if (!$rows): ?>
    <div class="card"><span class="badge">No audit entries yet</span></div>
  <?php else: ?>
    <?php foreach (array_reverse($rows) as $entry): ?>
      <div class="card">
        <div class="url"><?php echo htmlspecialchars($entry['url'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="meta">
          Language: <?php echo htmlspecialchars($entry['lang'], ENT_QUOTES, 'UTF-8'); ?> ·
          Detected: <?php echo htmlspecialchars($entry['detected_at'], ENT_QUOTES, 'UTF-8'); ?> ·
          Count: <?php echo isset($entry['items']) && is_array($entry['items']) ? count($entry['items']) : 0; ?>
        </div>
        <?php if (!empty($entry['items'])): ?>
          <ul>
            <?php foreach ($entry['items'] as $item): ?>
              <li><?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <span class="badge">No untranslated Arabic strings detected</span>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
</body>
</html>
