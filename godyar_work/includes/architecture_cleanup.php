<?php

if (!function_exists('gdy_current_lang')) {
    function gdy_current_lang(): string {
        static $lang = null;
        if ($lang !== null) return $lang;
        $supported = ['ar','en','fr'];
        foreach ([defined('GDY_LANG') ? GDY_LANG : null, function_exists('gdy_lang') ? gdy_lang() : null, $_SESSION['gdy_lang'] ?? null, $_COOKIE['gdy_lang'] ?? null] as $v) {
            $v = strtolower(trim((string)($v ?? '')));
            if (in_array($v, $supported, true)) return $lang = $v;
        }
        $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
        if (preg_match('~^/(ar|en|fr)(?=/|$)~i', $uri, $m)) {
            return $lang = strtolower((string)$m[1]);
        }
        return $lang = 'ar';
    }
}

if (!function_exists('gdy_lang_prefix')) {
    function gdy_lang_prefix(?string $lang = null): string {
        $lang = strtolower(trim((string)($lang ?: gdy_current_lang())));
        return in_array($lang, ['ar','en','fr'], true) ? '/' . $lang : '/ar';
    }
}

if (!function_exists('gdy_lang_url')) {
    function gdy_lang_url(?string $lang = null, string $path = ''): string {
        $base = function_exists('base_url') ? rtrim((string)base_url(), '/') : '';
        $path = $path !== '' ? $path : (string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
        $path = preg_replace('~^/(ar|en|fr)(?=/|$)~i', '', $path) ?? $path;
        $path = '/' . ltrim((string)$path, '/');
        if ($path === '//') $path = '/';
        return $base . gdy_lang_prefix($lang) . ($path === '/' ? '/' : $path);
    }
}

if (!function_exists('gdy_route_home_url')) {
    function gdy_route_home_url(?string $lang = null): string {
        return gdy_lang_url($lang, '/');
    }
}

if (!function_exists('gdy_route_news_url')) {
    function gdy_route_news_url($news, ?string $lang = null): string {
        $lang = $lang ?: gdy_current_lang();
        $base = function_exists('base_url') ? rtrim((string)base_url(), '/') : '';
        $prefix = gdy_lang_prefix($lang);
        $id = is_array($news) ? (int)($news['id'] ?? 0) : (int)$news;
        $slug = is_array($news) ? trim((string)($news['slug'] ?? '')) : '';
        if ($id > 0) return $base . $prefix . '/news/id/' . $id;
        if ($slug !== '') return $base . $prefix . '/news/' . rawurlencode($slug);
        return $base . $prefix . '/news';
    }
}

if (!function_exists('gdy_route_category_url')) {
    function gdy_route_category_url($category, ?string $lang = null): string {
        $lang = $lang ?: gdy_current_lang();
        $base = function_exists('base_url') ? rtrim((string)base_url(), '/') : '';
        $prefix = gdy_lang_prefix($lang);
        $id = is_array($category) ? (int)($category['id'] ?? 0) : 0;
        $slug = is_array($category) ? trim((string)($category['slug'] ?? '')) : trim((string)$category);
        if ($slug !== '') return $base . $prefix . '/category/' . rawurlencode($slug);
        if ($id > 0) return $base . $prefix . '/category/id/' . $id;
        return $base . $prefix . '/categories';
    }
}

if (!function_exists('gdy_plugin_base_dir')) {
    function gdy_plugin_base_dir(): string {
        foreach ([dirname(__DIR__) . '/plugins', dirname(__DIR__) . '/admin/plugins'] as $dir) {
            if (is_dir($dir)) return $dir;
        }
        return dirname(__DIR__) . '/plugins';
    }
}
