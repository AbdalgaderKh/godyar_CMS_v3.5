<?php
require_once dirname(__DIR__, 2) . '/includes/auto_binding_suggestions.php';
$rows = gdy_auto_binding_suggestions();
?><!DOCTYPE html>
<html><head><meta charset="utf-8">
<title>Auto Binding Suggestions</title>
<style>
body{font-family:Arial,sans-serif;background:#f6f7f9;margin:0;padding:32px}
.card{background:#fff;border-radius:14px;padding:22px;box-shadow:0 8px 24px rgba(0,0,0,.06);max-width:1200px;margin:0 auto}
table{width:100%;border-collapse:collapse;font-size:14px}
th,td{padding:10px;border-bottom:1px solid 
code{background:#f2f4f8;padding:2px 6px;border-radius:6px}
.small{color:#667085;font-size:13px}
</style></head><body>
<div class="card">
<h1>v3.8.5 Auto Binding Suggestions</h1>
<p class="small">اقتراحات مفاتيح ترجمة مبنية على نتائج runtime audit.</p>
<table>
<thead><tr><th>Arabic text</th><th>Suggested key</th><th>English</th><th>French</th></tr></thead>
<tbody>
<?php foreach ($rows as $row): ?>
<tr>
<td><?php echo htmlspecialchars($row['arabic'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><code><?php echo htmlspecialchars($row['key'], ENT_QUOTES, 'UTF-8'); ?></code></td>
<td><?php echo htmlspecialchars($row['en'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($row['fr'], ENT_QUOTES, 'UTF-8'); ?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div></body></html>
