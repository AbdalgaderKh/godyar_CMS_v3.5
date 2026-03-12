<?php
$file = dirname(__DIR__, 2) . '/patches/direct_template_patch_pack.txt';
$content = is_file($file) ? file_get_contents($file) : 'Patch file not found.';
?><!DOCTYPE html>
<html>
<head><meta charset="utf-8">
<title>Direct Template Patch Pack</title>
<style>
body{font-family:Arial,sans-serif;background:#f6f7f9;margin:0;padding:32px}
.card{background:#fff;border-radius:14px;padding:22px;box-shadow:0 8px 24px rgba(0,0,0,.06);max-width:1100px;margin:0 auto}
pre{white-space:pre-wrap;background:#111827;color:#e5e7eb;padding:18px;border-radius:10px;overflow:auto}
.small{color:#667085;font-size:13px}
</style>
</head>
<body>
<div class="card">
<h1>v3.8.8 Direct Template Patch Pack</h1>
<p class="small">باتشات مباشرة موجّهة للملفات الأساسية التي ما زالت تعرض نصوصًا عربية داخل النسخة الإنجليزية.</p>
<pre><?php echo htmlspecialchars($content, ENT_QUOTES, 'UTF-8'); ?></pre>
</div>
</body>
</html>
