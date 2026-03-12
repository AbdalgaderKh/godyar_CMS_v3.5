<?php

if (!defined('GDY_HOTFIX_PREPEND_LOADED')) {
    define('GDY_HOTFIX_PREPEND_LOADED', true);
}

if (function_exists('mb_internal_encoding') === true) {
    
    mb_internal_encoding('UTF-8');
}

if (function_exists('gdy_regex_replace') === false) {
    function gdy_regex_replace($pattern, $replacement, $subject, $limit = -1, &$count = null)
    {
        
        if (is_string($pattern) && preg_match('/^(.)(?:\\\\.|(?!\1).)*\1([a-zA-Z]*)$/s', $pattern, $m)) {
            $mods = $m[2] ?? '';
            if (strpos($mods, 'e') !== false) {
                $count = 0;
                return $subject;
            }
        }

        if ($count === null) {
            return preg_replace($pattern, $replacement, $subject, (int)$limit);
        }
        $tmp = 0;
        $out = preg_replace($pattern, $replacement, $subject, (int)$limit, $tmp);
        $count = $tmp;
        return $out;
    }
}

if (function_exists('gdy_regex_replace_callback') === false) {
    function gdy_regex_replace_callback($pattern, $callback, $subject, $limit = -1, &$count = null)
    {
        if (is_string($pattern) && preg_match('/^(.)(?:\\\\.|(?!\1).)*\1([a-zA-Z]*)$/s', $pattern, $m)) {
            $mods = $m[2] ?? '';
            if (strpos($mods, 'e') !== false) {
                $count = 0;
                return $subject;
            }
        }

        if ($count === null) {
            return preg_replace_callback($pattern, $callback, $subject, (int)$limit);
        }
        $tmp = 0;
        $out = preg_replace_callback($pattern, $callback, $subject, (int)$limit, $tmp);
        $count = $tmp;
        return $out;
    }
}
