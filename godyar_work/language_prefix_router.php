<?php

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH) ?: '/';
$path = '/' . ltrim($path, '/');

$lang = null;
$rest = $path;

if (preg_match('#^/(ar|en|fr)(?:/|$)#i', $path, $m)) {
    $lang = strtolower($m[1]);
    $rest = substr($path, 1 + strlen($m[1])); 
    $rest = $rest === '' ? '/' : $rest;
    if ($rest[0] !== '/') $rest = '/' . $rest;
}

if (!empty($lang) && empty($_GET['lang'])) {
    $_GET['lang'] = $lang;
}
define('GDY_LANG_PREFIX', $lang ?: '');
define('GDY_PATH_INFO', $rest);
