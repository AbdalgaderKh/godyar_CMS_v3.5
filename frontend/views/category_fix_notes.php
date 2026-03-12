<?php


if (!function_exists('gdy_safe_category_name')) {
    function gdy_safe_category_name($category) {
        $fallback = '';
        if (is_array($category) && isset($category['name'])) {
            $fallback = (string)$category['name'];
        }

        if (function_exists('gdy_tr')) {
            $translated = gdy_tr('category', isset($category['id']) ? $category['id'] : 0, 'name', $fallback);
            return $translated !== null ? (string)$translated : $fallback;
        }

        return $fallback;
    }
}



