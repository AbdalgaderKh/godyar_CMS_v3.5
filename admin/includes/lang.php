<?php

if (!defined('GDY_SUPPORTED_LANGS')) {
    define('GDY_SUPPORTED_LANGS', ['ar','en','fr']);
}

if (!function_exists('gdy_set_cookie_rfc')) {
    function gdy_set_cookie_rfc(string $name, string $value, int $ttlSeconds, string $path = '/', bool $secure = false, bool $httpOnly = true, string $sameSite = 'Lax'): void
    {
        if (headers_sent()) {
            return;
        }
        $ttlSeconds = max(0, $ttlSeconds);
        $expires = gmdate('D, d M Y H:i:s', time() + $ttlSeconds) . ' GMT';
        $cookie = $name . '=' .rawurlencode($value)
            . '; Expires=' . $expires
            . '; Max-Age=' . $ttlSeconds
            . '; Path=' . $path
            . '; SameSite=' . $sameSite
            . ($secure ? '; Secure' : '')
            . ($httpOnly ? '; HttpOnly' : '');
        header('Set-Cookie: ' . $cookie, false);
    }
}

if (!function_exists('gdy_lang')) {
    function gdy_lang(): string
    {
        $supported = GDY_SUPPORTED_LANGS;

        $q = isset($_GET['lang']) ? strtolower(trim((string)$_GET['lang'])) : '';
        if ($q !== '' && in_array($q, $supported, true)) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                gdy_session_start();
            }
            $_SESSION['gdy_lang'] = $q;

            if (!headers_sent()) {
                 $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
 || ((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
 || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);
                 gdy_set_cookie_rfc('gdy_lang', $q, 60*60*24*90, '/', $isSecure, true, 'Lax');
            }
            return $q;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            gdy_session_start();
        }

        $s = isset($_SESSION['gdy_lang']) ? strtolower(trim((string)$_SESSION['gdy_lang'])) : '';
        if ($s !== '' && in_array($s, $supported, true)) {
            return $s;
        }

        $c = isset($_COOKIE['gdy_lang']) ? strtolower(trim((string)$_COOKIE['gdy_lang'])) : '';
        if ($c !== '' && in_array($c, $supported, true)) {
            $_SESSION['gdy_lang'] = $c;
            return $c;
        }

        $_SESSION['gdy_lang'] = 'ar';
        return 'ar';
    }
}

if (!function_exists('gdy_is_rtl')) {
    function gdy_is_rtl(): bool
    {
        return gdy_lang() === 'ar';
    }
}

if (!function_exists('gdy_locale_dict')) {
    function gdy_locale_dict(): array
    {
                static $cache = [];
        $lang = gdy_lang();
        
        $lang = strtolower(preg_replace('~[^a-z]~', '', (string)$lang));
        $allowed = ['ar','en','fr'];
        if (!in_array($lang, $allowed, true)) { $lang = 'ar'; }
        $langDir = realpath(ROOT_PATH . '/languages') ?: (ROOT_PATH . '/languages');
if (isset($cache[$lang])) return $cache[$lang];

         $file = rtrim($langDir, '/\\') . '/' . $lang . '.php';
        $fileReal = realpath($file);
         if ($fileReal === false || strpos($fileReal, rtrim($langDir, '/\\') . DIRECTORY_SEPARATOR) !== 0) { $fileReal = null; }
        $dict = [];
        if (is_file($file)) {
            $safeFile = gdy_i18n_safe_file($file, $langDir);
            if ($safeFile === null) { $tmp = null; } else { $tmp = require $safeFile; }
            if (is_array($tmp)) {
                $dict = $tmp;
            }
        }

        
        $patch = $langDir . '/' . $lang . '_patch.php';
        $patchReal = realpath($patch);
        if ($patchReal === false || strpos($patchReal, $langDir .DIRECTORY_SEPARATOR) !== 0) {
            $patchReal = null;
        }
        $patch = $patchReal;
        if ($patch !== null && is_file($patch)) {
            $safePatch = gdy_i18n_safe_file($patch, $langDir);
            if ($safePatch === null) { $tmp2 = null; } else { $tmp2 = require $safePatch; }
            if (is_array($tmp2)) {
                
                $dict = array_merge($dict, $tmp2);
            }
        }

        $cache[$lang] = $dict;
        return $dict;
    }
}

if (!function_exists('__')) {
    
    
function __(string $key, $varsOrFallback = [], array $vars2 = []): string
{
    $dict = gdy_locale_dict();

    $fallback = null;
    $vars = [];

    
    if (is_array($varsOrFallback)) {
        $vars = $varsOrFallback;
    } elseif (is_string($varsOrFallback) && $varsOrFallback !== '') {
        $fallback = $varsOrFallback;
        $vars = $vars2; 
    }

    $text = $dict[$key] ?? ($fallback ?? $key);

    if (!empty($vars)) {
        foreach ($vars as $k => $v) {
            $text = str_replace('{' . $k . '}', (string)$v, (string)$text);
        }
    }
    return (string)$text;
}

if (!function_exists('__t')) {
    function __t(string $key, string $fallback = '', array $vars = []): string
    {
        return __( $key, $fallback, $vars );
    }
}

}

if (!function_exists('gdy_lang_url')) {
    function gdy_lang_url(string $lang): string
    {
        $lang = strtolower(trim($lang));
        if (!preg_match('/^[a-z0-9_-]{2,15}$/', $lang)) {
            return '';
        }
        $langDir = realpath(ROOT_PATH . '/languages');
        if ($langDir === false) {
            return '';
        }
        if (!in_array($lang, GDY_SUPPORTED_LANGS, true)) {
            $lang = 'ar';
        }

        
        $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
        $parts = parse_url($uri) ?: [];
        $path = (string)($parts['path'] ?? '/');
        $queryStr = (string)($parts['query'] ?? '');

        $query = [];
        if ($queryStr !== '') {
            parse_str($queryStr, $query);
        }
        $query['lang'] = $lang;
        $qs = http_build_query($query);
        return $path . ($qs ? ('?' . $qs) : '');
    }
}

function gdy_i18n_safe_file(string $path, string $dir): ?string {
    $dirReal = realpath($dir);
    if ($dirReal === false) return null;
    $pReal = realpath($path);
    if ($pReal === false) return null;
    if (strpos($pReal, $dirReal .DIRECTORY_SEPARATOR) !== 0) return null;
    return $pReal;
}
