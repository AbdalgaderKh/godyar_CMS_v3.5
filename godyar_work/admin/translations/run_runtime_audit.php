<?php
require_once dirname(__DIR__, 2) . '/includes/runtime_translation_audit.php';

$lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';
$url  = isset($_GET['url']) ? $_GET['url'] : '';
$out  = array();

if ($url !== '') {
    $ctx = stream_context_create(array(
        'http' => array(
            'timeout' => 15,
            'user_agent' => 'GodyarRuntimeAudit/1.0'
        )
    ));
    $html = @file_get_contents($url, false, $ctx);

    if ($html !== false && gdy_runtime_audit_is_non_arabic_lang($lang)) {
        $items = gdy_runtime_audit_detect_strings($html);
        $out = gdy_runtime_audit_store(
            $url,
            $lang,
            $items,
            dirname(__DIR__, 2) . '/storage/runtime_translation_audit.json'
        );
    } else {
        $out = array(
            'url' => $url,
            'lang' => $lang,
            'error' => 'Unable to fetch page or page language is Arabic.'
        );
    }
}
?><!DOCTYPE html>
<html>
<head><meta charset="utf-8">
<title>Run Runtime Audit</title>
<style>
body{font-family:Arial,sans-serif;background:#f6f7f9;padding:32px}
.card{max-width:980px;margin:0 auto;background:#fff;border-radius:14px;padding:22px;box-shadow:0 8px 24px rgba(0,0,0,.06)}
pre{white-space:pre-wrap;background:#0f172a;color:#e2e8f0;padding:16px;border-radius:12px;overflow:auto}
a{display:inline-block;margin-top:16px;color:#127a52;text-decoration:none}
</style>
</head>
<body>
<div class="card">
<h1>Audit Result</h1>
<pre><?php echo htmlspecialchars(json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?></pre>
<a href="runtime_audit.php">العودة إلى شاشة المراجعة</a>
</div>
</body>
</html>
