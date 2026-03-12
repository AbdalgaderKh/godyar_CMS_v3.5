<?php
require_once dirname(__DIR__) . '/../includes/smart_translation_engine.php';

$source = isset($_POST['source']) ? trim($_POST['source']) : '';
$sourceLang = isset($_POST['source_lang']) ? trim($_POST['source_lang']) : 'en';
$result = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = gdy_smart_translation_payload($source, $sourceLang);
}
?><!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8">
<title>Smart Translation Engine</title>
<style>
body{font-family:Arial,sans-serif;background:#f6f7f9;margin:0;padding:30px}
.wrap{max-width:980px;margin:0 auto}
.card{background:#fff;border-radius:14px;padding:22px;box-shadow:0 8px 24px rgba(0,0,0,.06);margin-bottom:18px}
textarea,input,select{width:100%;padding:10px 12px;border:1px solid 
button{background:#127a52;color:#fff;border:0;border-radius:10px;padding:11px 16px;font-size:14px;cursor:pointer}
.grid{display:grid;grid-template-columns:1fr 180px;gap:12px}
.badge{display:inline-block;background:#eef7f3;color:#127a52;padding:4px 10px;border-radius:999px;font-size:12px}
pre{white-space:pre-wrap;background:#0f172a;color:#e2e8f0;padding:16px;border-radius:12px;overflow:auto}
</style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Smart Translation Engine</h1>
        <p>اقتراحات ترجمة مبدئية للنصوص التحريرية والواجهات، مع تعليم النتائج بأنها تحتاج مراجعة.</p>
    </div>

    <div class="card">
        <form method="post">
            <div class="grid">
                <div>
                    <label>Source text</label>
                    <textarea name="source" rows="6" placeholder="Enter source text here"><?php echo htmlspecialchars($source, ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div>
                    <label>Source language</label>
                    <select name="source_lang">
                        <option value="ar"<?php echo $sourceLang === 'ar' ? ' selected' : ''; ?>>Arabic</option>
                        <option value="en"<?php echo $sourceLang === 'en' ? ' selected' : ''; ?>>English</option>
                        <option value="fr"<?php echo $sourceLang === 'fr' ? ' selected' : ''; ?>>French</option>
                    </select>
                    <div style="height:12px"></div>
                    <button type="submit">Generate suggestions</button>
                </div>
            </div>
        </form>
    </div>

    <div class="card">
        <span class="badge">Review required</span>
        <h2>Output</h2>
        <pre><?php echo htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?></pre>
    </div>
</div>
</body>
</html>
