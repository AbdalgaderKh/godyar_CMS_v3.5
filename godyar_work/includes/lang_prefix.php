<?php

if (defined('GDY_LANG')) {
    return;
}

$supported = ['ar', 'en', 'fr'];

if (defined('GDY_FORCE_LANG')) {
    $forced = (string)GDY_FORCE_LANG;
    if (in_array($forced, $supported, true)) {
        define('GDY_LANG', $forced);
        define('GDY_FORCE_PRETTY_URLS', true);
        return;
    }
}

$uri = filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_UNSAFE_RAW);
$uri = is_string($uri) && $uri !== '' ? $uri : '/';

$qpos = strpos($uri, '?');
$path = ($qpos === false) ? $uri : substr($uri, 0, $qpos);
$path = is_string($path) && $path !== '' ? $path : '/';

$trim = trim($path, '/');
$seg0 = '';
if ($trim !== '') {
    $parts = explode('/', $trim, 2);
    $seg0 = isset($parts[0]) ? strtolower((string)$parts[0]) : '';
}

$lang = in_array($seg0, $supported, true) ? $seg0 : 'ar'; 

define('GDY_LANG', $lang);
define('GDY_FORCE_PRETTY_URLS', true);
