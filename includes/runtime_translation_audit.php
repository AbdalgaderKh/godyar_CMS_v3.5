<?php
if (!function_exists('gdy_runtime_audit_is_non_arabic_lang')) {
    function gdy_runtime_audit_is_non_arabic_lang($lang) {
        $lang = strtolower(trim((string)$lang));
        return in_array($lang, array('en', 'fr'));
    }
}

if (!function_exists('gdy_runtime_audit_detect_strings')) {
    function gdy_runtime_audit_detect_strings($html) {
        $results = array();
        $html = (string)$html;
        if ($html === '') return $results;

        $html = preg_replace('~<script\b[^>]*>.*?</script>~is', ' ', $html);
        $html = preg_replace('~<style\b[^>]*>.*?</style>~is', ' ', $html);
        $html = preg_replace('~<!--.*?-->~s', ' ', $html);

        $text = trim(strip_tags($html));
        if ($text === '') return $results;

        $chunks = preg_split('/[\r\n]+/u', $text);
        foreach ($chunks as $chunk) {
            $chunk = trim(preg_replace('/\s+/u', ' ', $chunk));
            if ($chunk === '') continue;
            if (preg_match('/[\x{0600}-\x{06FF}]/u', $chunk)) {
                if (function_exists('mb_strlen')) {
                    if (mb_strlen($chunk, 'UTF-8') < 2) continue;
                } else {
                    if (strlen($chunk) < 2) continue;
                }
                $results[] = $chunk;
            }
        }

        $unique = array();
        $seen = array();
        foreach ($results as $row) {
            if (!isset($seen[$row])) {
                $seen[$row] = 1;
                $unique[] = $row;
            }
        }
        return $unique;
    }
}

if (!function_exists('gdy_runtime_audit_store')) {
    function gdy_runtime_audit_store($pageUrl, $lang, $items, $storageFile) {
        $payload = array(
            'url' => (string)$pageUrl,
            'lang' => (string)$lang,
            'detected_at' => date('c'),
            'items' => array_values($items),
        );

        $dir = dirname($storageFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $existing = array();
        if (is_file($storageFile)) {
            $raw = @file_get_contents($storageFile);
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) $existing = $decoded;
        }

        $existing[] = $payload;
        @file_put_contents($storageFile, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $payload;
    }
}
