<?php

if (!function_exists('gdy_base_origin')) {
    function gdy_base_origin(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string)($_SERVER['HTTP_HOST'] ?? '');
        return $host !== '' ? ($scheme . '://' . $host) : '';
    }
}

if (!function_exists('gdy_base_url')) {
    function gdy_base_url(): string
    {
        $base = $GLOBALS['baseUrl'] ?? '';
        if (is_string($base) && $base !== '') return rtrim($base, '/');
        return rtrim(gdy_base_origin(), '/');
    }
}

if (!function_exists('gdy_current_url')) {
    function gdy_current_url(): string
    {
        $base = gdy_base_origin();
        $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
        if ($base === '') return $uri;
        if ($uri === '') $uri = '/';
        if ($uri[0] !== '/') $uri = '/' . $uri;
        return $base . $uri;
    }
}

if (!function_exists('gdy_clean_url')) {
    function gdy_clean_url(string $url): string
    {
        $url = trim($url);
        if ($url === '') return '';

        $parts = @parse_url($url);
        if (!is_array($parts)) return $url;

        $scheme = $parts['scheme'] ?? '';
        $host = $parts['host'] ?? '';
        $path = $parts['path'] ?? '';

        $clean = '';
        if ($scheme && $host) {
            $clean = $scheme . '://' . $host;
        }
        $clean .= $path ?: '/';

        return $clean;
    }
}

if (!function_exists('gdy_current_url_clean')) {
    function gdy_current_url_clean(): string
    {
        return gdy_clean_url(gdy_current_url());
    }
}

if (!function_exists('gdy_hreflang_map')) {
    
    function gdy_hreflang_map(string $path = ''): array
    {
        $base = rtrim(gdy_base_url(), '/');
        $path = '/' . ltrim($path, '/');

        return [
            'ar' => $base . '/ar' . $path,
            'en' => $base . '/en' . $path,
            'fr' => $base . '/fr' . $path,
        ];
    }
}

if (!function_exists('gdy_optimize_html_images')) {
    function gdy_optimize_html_images(string $html): string
    {
        
        return preg_replace('~<img\b(?![^>]*\bloading=)([^>]*?)>~i', '<img loading="lazy"$1>', $html) ?? $html;
    }
}

if (!function_exists('gdy_extract_faq')) {
    
    function gdy_extract_faq(string $html): array
    {
        
        $out = [];
        if ($html === '') return $out;

        
        if (preg_match_all('~<h3[^>]*>(.*?)</h3>\s*<p[^>]*>(.*?)</p>~is', $html, $m, PREG_SET_ORDER)) {
            foreach ($m as $row) {
                $q = trim(strip_tags($row[1] ?? ''));
                $a = trim(strip_tags($row[2] ?? ''));
                if ($q !== '' && $a !== '') $out[] = ['q' => $q, 'a' => $a];
            }
        }

        return $out;
    }
}

if (!function_exists('gdy_jsonld_faq')) {
    
    function gdy_jsonld_faq(array $faq): string
    {
        if (!$faq) return '';

        $mainEntity = [];
        foreach ($faq as $row) {
            $mainEntity[] = [
                '@type' => 'Question',
                'name' => (string)$row['q'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => (string)$row['a'],
                ],
            ];
        }

        $obj = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $mainEntity,
        ];

        return json_encode($obj, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
    }
}
