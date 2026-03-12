<?php
require_once dirname(__DIR__, 2) . '/includes/assisted_patch_generator.php';
$rows = gdy_assisted_patch_rows();
$patch = gdy_generate_unified_patch();
?><!DOCTYPE html>
<html>
<head><meta charset="utf-8">
<title>Assisted Patch Generator</title>
<style>
body{font-family:Arial,sans-serif;background:#f6f7f9;margin:0;padding:32px}
.card{background:#fff;border-radius:14px;padding:22px;box-shadow:0 8px 24px rgba(0,0,0,.06);max-width:1200px;margin:0 auto 18px}
table{width:100%;border-collapse:collapse;font-size:14px}
th,td{padding:10px;border-bottom:1px solid 
code,pre{background:#f2f4f8;padding:2px 6px;border-radius:6px}
pre{display:block;padding:14px;white-space:pre-wrap}
.small{color:#667085;font-size:13px}
</style>
</head>
<body>
<div class="card">
<h1>v3.8.7 Assisted Patch Generator</h1>
<p class="small">مولّد باتشات إرشادي مبني على النصوص غير المترجمة ذات الأولوية.</p>
<table>
<thead><tr><th>File</th><th>Before</th><th>After</th><th>Note</th></tr></thead>
<tbody>
<?php foreach ($rows as $row): ?>
<tr>
<td><code><?php echo htmlspecialchars($row['file'], ENT_QUOTES, 'UTF-8'); ?></code></td>
<td><?php echo htmlspecialchars($row['before'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><code><?php echo htmlspecialchars($row['after'], ENT_QUOTES, 'UTF-8'); ?></code></td>
<td><?php echo htmlspecialchars($row['note'], ENT_QUOTES, 'UTF-8'); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<div class="card">
<h2>Patch Preview</h2>
<pre><?php echo htmlspecialchars($patch, ENT_QUOTES, 'UTF-8'); ?></pre>
</div>
</body>
</html>
