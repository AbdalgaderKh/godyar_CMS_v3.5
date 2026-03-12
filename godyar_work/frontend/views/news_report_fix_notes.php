<?php


if (!function_exists('gdy_safe_updated_at')) {
    function gdy_safe_updated_at($news) {
        if (is_array($news)) {
            if (!empty($news['updated_at'])) return (string)$news['updated_at'];
            if (!empty($news['published_at'])) return (string)$news['published_at'];
            if (!empty($news['created_at'])) return (string)$news['created_at'];
        }
        return '';
    }
}

if (!function_exists('gdy_safe_canonical')) {
    function gdy_safe_canonical($canonical, $fallbackUrl) {
        $canonical = is_string($canonical) ? trim($canonical) : '';
        $fallbackUrl = is_string($fallbackUrl) ? trim($fallbackUrl) : '';
        return $canonical !== '' ? $canonical : $fallbackUrl;
    }
}





