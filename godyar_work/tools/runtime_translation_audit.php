<?php
require_once dirname(__DIR__) . '/includes/runtime_translation_audit.php';

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
            dirname(__DIR__) . '/storage/runtime_translation_audit.json'
        );
    } else {
        $out = array(
            'url' => $url,
            'lang' => $lang,
            'error' => 'Unable to fetch page or page language is Arabic.'
        );
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
