<?php
if (!defined('GDY_REQUEST_START')) {
    define('GDY_REQUEST_START', microtime(true));
}

if (!function_exists('gdy_request_ms')) {
    function gdy_request_ms()
    {
        return round((microtime(true) - GDY_REQUEST_START) * 1000, 2);
    }
}

if (!function_exists('gdy_can_use_page_cache')) {
    function gdy_can_use_page_cache()
    {
        if (PHP_SAPI === "cli") { return false; }
        if (!empty($_POST)) { return false; }
        if (!empty($_SESSION['user_id']) || !empty($_SESSION['admin_id'])) { return false; }
        $uri = isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : "";
        if ($uri !== "" && preg_match('~^/(admin|api|cron)(/|$)~i', $uri)) { return false; }
        if (!empty($_GET)) {
            foreach ($_GET as $k => $v) {
                $k = strtolower((string)$k);
                if (strpos($k, "utm_") === 0) { continue; }
                if (in_array($k, array('fbclid','gclid','lang'), true)) { continue; }
                return false;
            }
        }
        return true;
    }
}

if (!function_exists('gdy_perf_headers')) {
    function gdy_perf_headers()
    {
        if (headers_sent()) { return; }
        header('X-Godyar-Version: v3.4');
        header('X-Response-Time: '.gdy_request_ms().'ms');
    }
}

if (!function_exists('gdy_lazy_img_attrs')) {
    function gdy_lazy_img_attrs($extra = '')
    {
        $attrs = ' loading="lazy" decoding="async"';
        if ($extra) { $attrs .= ' ' . trim((string)$extra); }
        return $attrs;
    }
}

register_shutdown_function(function () {
    if (PHP_SAPI !== 'cli') {
        gdy_perf_headers();
    }
});
