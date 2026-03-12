<?php
class GodyarI18n
{
    private static $lang = 'ar';
    private static $data = array();

    public static function init()
    {
        $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';

        if (isset($_GET['lang']) && in_array($_GET['lang'], array('ar', 'en', 'fr'), true)) {
            self::$lang = (string) $_GET['lang'];
        } elseif (isset($_SESSION['gdy_lang']) && in_array($_SESSION['gdy_lang'], array('ar', 'en', 'fr'), true)) {
            self::$lang = (string) $_SESSION['gdy_lang'];
        } elseif (isset($_COOKIE['gdy_lang']) && in_array($_COOKIE['gdy_lang'], array('ar', 'en', 'fr'), true)) {
            self::$lang = (string) $_COOKIE['gdy_lang'];
        } elseif (preg_match('#^/(en|fr)(/|$)#', $uri, $m)) {
            self::$lang = $m[1];
        } elseif (preg_match('#^/ar(/|$)#', $uri)) {
            self::$lang = 'ar';
        }

        if (function_exists('session_status') && session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['gdy_lang'] = self::$lang;
        }

        if (!headers_sent()) {
            @setcookie('gdy_lang', self::$lang, time() + 31536000, '/');
        }

        self::$data = array();
        $patches = [
            __DIR__ . '/../languages/' . self::$lang . '.php',
            __DIR__ . '/../languages/' . self::$lang . '_patch.php',
            __DIR__ . '/../languages/' . self::$lang . '_patch_v38.php',
        ];
        foreach ($patches as $file) {
            if (is_file($file)) {
                $loaded = include $file;
                if (is_array($loaded)) {
                    self::$data = array_merge(self::$data, $loaded);
                }
            }
        }
    }

    public static function lang()
    {
        return self::$lang;
    }

    public static function isRtl()
    {
        return self::$lang === 'ar';
    }

    public static function dir()
    {
        if (isset($_SERVER['REQUEST_URI']) && strpos((string)$_SERVER['REQUEST_URI'], '/admin') === 0) {
            return 'rtl';
        }
        return self::isRtl() ? 'rtl' : 'ltr';
    }

    public static function t($key, $fallback = '')
    {
        return isset(self::$data[$key]) ? self::$data[$key] : $fallback;
    }
}

if (!function_exists('__')) {
    function __($key, $fallback = '')
    {
        return GodyarI18n::t($key, $fallback);
    }
}
if (!function_exists('gdy_lang')) {
    function gdy_lang()
    {
        return GodyarI18n::lang();
    }
}
if (!function_exists('gdy_translate')) {
    function gdy_translate($key, $fallback = '')
    {
        return GodyarI18n::t($key, $fallback);
    }
}
if (!function_exists('gdy_dir')) {
    function gdy_dir()
    {
        return GodyarI18n::dir();
    }
}
if (!function_exists('gdy_is_rtl')) {
    function gdy_is_rtl()
    {
        return GodyarI18n::isRtl();
    }
}

GodyarI18n::init();
