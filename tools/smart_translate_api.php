<?php
require_once dirname(__DIR__) . '/includes/smart_translation_engine.php';

header('Content-Type: application/json; charset=utf-8');

$source = isset($_GET['source']) ? (string)$_GET['source'] : '';
$lang = isset($_GET['lang']) ? (string)$_GET['lang'] : 'en';

echo json_encode(gdy_smart_translation_payload($source, $lang), JSON_UNESCAPED_UNICODE);
