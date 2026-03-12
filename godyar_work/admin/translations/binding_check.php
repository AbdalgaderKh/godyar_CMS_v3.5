<?php
require_once dirname(__DIR__) . '/../includes/translation_binding_fix.php';
$checks = array(
    'auth.login' => __('auth.login', 'Login'),
    'auth.register' => __('auth.register', 'Create Account'),
    'share.copy_link' => __('share.copy_link', 'Copy Link'),
    'article.toc' => __('article.toc', 'Table of Contents'),
    'category.general_news' => __('category.general_news', 'General News'),
);
?><!DOCTYPE html>
<html>
<head><meta charset="utf-8">
<title>Translation Binding Check</title>
<style>
body{font-family:Arial,sans-serif;background:#f6f7f9;padding:32px}
.card{max-width:860px;background:#fff;border-radius:14px;padding:22px;margin:0 auto;box-shadow:0 8px 24px rgba(0,0,0,.06)}
table{width:100%;border-collapse:collapse}
td,th{padding:10px;border-bottom:1px solid 
code{background:#f2f4f8;padding:2px 6px;border-radius:6px}
</style>
</head>
<body>
<div class="card">
<h1>Translation Binding Check</h1>
<table>
<tr><th>Key</th><th>Resolved Text</th></tr>
<?php foreach ($checks as $k => $v): ?>
<tr><td><code><?php echo htmlspecialchars($k, ENT_QUOTES, 'UTF-8'); ?></code></td><td><?php echo htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); ?></td></tr>
<?php endforeach; ?>
</table>
</div>
</body>
</html>
