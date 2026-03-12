<?php

if (!function_exists('gdy_supported_langs')) {
    function gdy_supported_langs(): array { return ['ar','en','fr']; }
}

if (!function_exists('gdy_normalize_lang')) {
    function gdy_normalize_lang(?string $lang = null): string {
        $lang = strtolower(trim((string)($lang ?? '')));
        return in_array($lang, gdy_supported_langs(), true) ? $lang : 'ar';
    }
}

if (!function_exists('gdy_request_lang')) {
    function gdy_request_lang(?string $fallback = null): string {
        foreach ([defined('GDY_LANG') ? GDY_LANG : null, $_GET['lang'] ?? null, $_SESSION['gdy_lang'] ?? null, $_COOKIE['gdy_lang'] ?? null, function_exists('gdy_lang') ? gdy_lang() : null] as $candidate) {
            $norm = gdy_normalize_lang(is_string($candidate) ? $candidate : null);
            if ($norm !== 'ar' || strtolower(trim((string)$candidate)) === 'ar') {
                return $norm;
            }
        }
        $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
        if (preg_match('~^/(ar|en|fr)(?=/|$)~i', $uri, $m)) {
            return gdy_normalize_lang((string)$m[1]);
        }
        return gdy_normalize_lang($fallback);
    }
}

if (!function_exists('gdy_base_url_safe')) {
    function gdy_base_url_safe(): string {
        return function_exists('base_url') ? rtrim((string)base_url(), '/') : '';
    }
}

if (!function_exists('gdy_lang_prefix_safe')) {
    function gdy_lang_prefix_safe(?string $lang = null): string {
        return '/' . gdy_normalize_lang($lang ?: gdy_request_lang());
    }
}

if (!function_exists('gdy_front_url')) {
    function gdy_front_url(string $path = '', ?string $lang = null): string {
        $base = gdy_base_url_safe();
        $prefix = gdy_lang_prefix_safe($lang);
        $path = '/' . ltrim($path, '/');
        if ($path === '//') { $path = '/'; }
        return $base . $prefix . ($path === '/' ? '/' : $path);
    }
}

if (!function_exists('gdy_shared_view_data')) {
    function gdy_shared_view_data(array $data = []): array {
        $lang = gdy_normalize_lang((string)($data['pageLang'] ?? gdy_request_lang()));
        $baseUrl = rtrim((string)($data['baseUrl'] ?? gdy_base_url_safe()), '/');
        $navBaseUrl = rtrim((string)($data['navBaseUrl'] ?? ($baseUrl . gdy_lang_prefix_safe($lang))), '/');
        $path = (string)($data['currentPath'] ?? (parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/'));
        $shared = [
            'pageLang' => $lang,
            'baseUrl' => $baseUrl,
            'rootUrl' => (string)($data['rootUrl'] ?? $baseUrl),
            'navBaseUrl' => $navBaseUrl,
            'homeUrl' => (string)($data['homeUrl'] ?? gdy_front_url('/', $lang)),
            'currentPath' => $path,
            'meta_title' => (string)($data['meta_title'] ?? $data['pageTitle'] ?? $data['title'] ?? 'Godyar News'),
            'meta_description' => (string)($data['meta_description'] ?? $data['metaDescription'] ?? ''),
            'canonical_url' => (string)($data['canonical_url'] ?? $data['canonicalUrl'] ?? (($baseUrl !== '' && $path !== '') ? ($baseUrl . $path) : '')),
        ];
        return array_merge($shared, $data);
    }
}

if (!function_exists('gdy_boot_legacy_front_plugins')) {
    function gdy_boot_legacy_front_plugins(): void {
        static $booted = false;
        if ($booted) return;
        $booted = true;

        $files = [
            dirname(__DIR__) . '/admin/plugins/hooks.php',
            dirname(__DIR__) . '/admin/plugins/loader.php',
        ];
        foreach ($files as $file) {
            if (is_file($file)) {
                require_once $file;
            }
        }

        if (function_exists('g_plugins')) {
            try { g_plugins(); } catch (Throwable $e) { error_log('[Godyar v2.4] plugin bootstrap failed: ' . $e->getMessage()); }
        }
    }
}
