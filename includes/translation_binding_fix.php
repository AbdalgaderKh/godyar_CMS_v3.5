<?php

if (!function_exists('__')) {
    function __($key, $fallback = '') {
        static $map = null;
        if ($map === null) {
            $lang = isset($_GET['lang']) ? strtolower(trim($_GET['lang'])) : 'ar';
            $root = dirname(__DIR__);
            $baseFile = $root . '/languages/' . $lang . '_patch.php';
            $map = array();
            if (is_file($baseFile)) {
                $loaded = include $baseFile;
                if (is_array($loaded)) $map = $loaded;
            }
        }
        if (isset($map[$key]) && $map[$key] !== '') {
            return $map[$key];
        }
        return $fallback !== '' ? $fallback : $key;
    }
}

if (!function_exists('gdy_ui_text')) {
    function gdy_ui_text($key, $fallback = '') {
        return __($key, $fallback);
    }
}
