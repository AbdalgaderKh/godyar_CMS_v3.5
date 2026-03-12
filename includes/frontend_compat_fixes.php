<?php

if (!function_exists('gdy_safe_category_name')) {
    function gdy_safe_category_name($category) {
        $fallback = '';
        $categoryId = 0;

        if (is_array($category)) {
            if (isset($category['name'])) {
                $fallback = (string) $category['name'];
            }
            if (isset($category['id'])) {
                $categoryId = (int) $category['id'];
            }
        }

        if (function_exists('gdy_tr')) {
            $translated = gdy_tr('category', $categoryId, 'name', $fallback);
            return $translated !== null ? (string) $translated : $fallback;
        }

        return $fallback;
    }
}

if (!function_exists('gdy_safe_updated_at')) {
    function gdy_safe_updated_at($post) {
        if (is_array($post)) {
            if (!empty($post['updated_at'])) {
                return (string) $post['updated_at'];
            }
            if (!empty($post['published_at'])) {
                return (string) $post['published_at'];
            }
            if (!empty($post['created_at'])) {
                return (string) $post['created_at'];
            }
        }
        return '';
    }
}

if (!function_exists('gdy_safe_updated_at_iso')) {
    function gdy_safe_updated_at_iso($post) {
        $value = gdy_safe_updated_at($post);
        return $value !== '' ? date('c', strtotime($value)) : '';
    }
}

if (!function_exists('gdy_safe_canonical')) {
    function gdy_safe_canonical($canonical, $fallbackUrl) {
        $canonical = is_string($canonical) ? trim($canonical) : '';
        $fallbackUrl = is_string($fallbackUrl) ? trim($fallbackUrl) : '';
        return $canonical !== '' ? $canonical : $fallbackUrl;
    }
}
