<?php

class PageCache
{
    public static function serveIfCached($key)
    {
        if (class_exists('Cache') === false) {
            return false;
        }
        if (function_exists('gdy_can_use_page_cache') && !gdy_can_use_page_cache()) {
            return false;
        }

        $html = Cache::get('page_' . $key);
        if (!is_string($html) || $html === '') {
            if (!headers_sent()) { header('X-Godyar-Cache: MISS'); }
            return false;
        }

        if (!headers_sent()) {
            header('X-Godyar-Cache: HIT');
            header('Vary: Accept-Encoding');
        }
        echo $html;
        return true;
    }

    public static function store($key, $ttl = null)
    {
        if (!class_exists('Cache')) {
            return;
        }
        if (function_exists('gdy_can_use_page_cache') && !gdy_can_use_page_cache()) {
            return;
        }

        $html = ob_get_contents();
        if ($html === false || $html === '') {
            return;
        }

        Cache::put('page_' . $key, $html, ($ttl === null ? 120 : (int)$ttl));
        if (!headers_sent()) { header('X-Godyar-Cache: STORE'); }
    }
}
