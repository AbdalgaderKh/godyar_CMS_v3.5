
<?php
require_once dirname(__DIR__).'/../includes/smart_translation_engine.php';

$source = $_POST['source'] ?? '';
$lang = $_POST['lang'] ?? 'ar';
$result = [];

if($_SERVER['REQUEST_METHOD']==='POST'){
    $result = gdy_smart_translation_payload($source,$lang);
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8">
<title>Smart Translation (Editor)</title>
<style>
body{font-family:Arial;padding:40px;background:#f6f7f9}
.card{background:#fff;padding:20px;border-radius:10px;margin-bottom:20px}
textarea{width:100%;height:120px}
button{padding:10px 14px;background:#1a7f5a;color:#fff;border:0;border-radius:6px}
pre{background:#111;color:#eee;padding:15px;border-radius:6px}
</style>
</head>
<body>

<div class="card">
<h2>اقتراح ترجمة للمقال</h2>

<form method="post">
<textarea name="source" placeholder="أدخل نص المقال أو العنوان هنا"><?php echo htmlspecialchars($source); ?></textarea>

<br><br>

<select name="lang">
<option value="ar">Arabic</option>
<option value="en">English</option>
<option value="fr">French</option>
</select>

<button>اقتراح الترجمة</button>
</form>

</div>

<div class="card">
<h3>النتيجة</h3>
<pre><?php echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE); ?></pre>
</div>

</body>
</html>
