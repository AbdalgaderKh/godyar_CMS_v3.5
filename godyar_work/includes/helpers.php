<?php

if (function_exists('h') === false) {
    function h($v): string {
        return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (function_exists('str_starts_with') === false) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

$gdyAiConfigFile = __DIR__ . '/ai_config.php';
if (is_file($gdyAiConfigFile) === true) {
    require_once $gdyAiConfigFile;
}

if (function_exists('gdy_apply_glossary') === false) {
    function gdy_apply_glossary(PDO $pdo, string $html): string
    {
        static $cache = null;

        if ($html === '') {
            return $html;
        }

        if ($cache === null) {
            try {
                $stmt = $pdo->query("SELECT term, short_definition FROM gdy_glossary WHERE is_active = 1 ORDER BY CHAR_LENGTH(term) DESC");
                $cache = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (\Throwable $e) {
                error_log('[gdy_glossary] fetch terms failed: ' . $e->getMessage());
                $cache = [];
            }
        }

        if ($cache === false) {
            return $html;
        }

        foreach ($cache as $row) {
            $term = trim((string)($row['term'] ?? ''));
            $def = trim((string)($row['short_definition'] ?? ''));

            if ($term === '' || $def === '') {
                continue;
            }

            $pattern = '/(' . preg_quote($term, '/') . ')/u';

            $html = preg_replace_callback($pattern, function ($m) use ($def) {
                $text = $m[1];

                if (preg_match('/^<|>$/', $text)) {
                    return $text;
                }

                return '<span class="gdy-glossary-term" data-definition="' . h($def) . '">' . $text . '</span>';
            }, $html, 3);
        }

        return $html;
    }
}

if (function_exists('gdy_ai_glossary_suggest_terms') === false) {
    function gdy_ai_glossary_suggest_terms(string $plainText): array
    {
        $plainText = trim($plainText);
        if ($plainText === '') {
            return [];
        }

        if (!defined('OPENAI_API_KEY') || OPENAI_API_KEY === '') {
            error_log('[gdy_ai_glossary] OPENAI_API_KEY is not defined');
            return [];
        }

        if (mb_strlen($plainText, 'UTF-8') > 4000) {
            $plainText = mb_substr($plainText, 0, 4000, 'UTF-8');
        }

        $endpoint = 'https://api.openai.com/v1/chat/completions';

        $systemPrompt = 'أنت خبير لغة عربية لموقع أخبار.
مهمتك قراءة نص خبر أو مقال، واختيار المصطلحات أو الأسماء أو الاختصارات
التي قد تحتاج شرحاً للقارئ العربي العادي، ثم إعطاء تعريف مبسط لكل مصطلح .

القواعد:
- اختر من 3 إلى 8 مصطلحات كحد أقصى .
- تجنّب الكلمات السهلة والواضحة جداً .
- اكتب التعريف بجملتين أو ثلاث جمل قصيرة .
- أرجِع النتيجة بصيغة JSON فقط، بدون أي كلام إضافي .
- مثال:
[{"term": "الفيدرالي الأمريكي", "definition": "هو البنك المركزي للولايات المتحدة..."}]';

        $userPrompt = "النص:\n" . $plainText;

        $payload = [
            'model' => 'gpt-4.1-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemPrompt,
                ],
                [
                    'role' => 'user',
                    'content' => $userPrompt,
                ],
            ],
            'max_tokens' => 320,
            'temperature' => 0.3,
        ];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json; charset=utf-8',
                'Authorization: ' . 'Bearer ' . OPENAI_API_KEY,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        if ($response === false) {
            error_log('[gdy_ai_glossary] curl error: ' . curl_error($ch));
            curl_close($ch);
            return [];
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);
        if ($status >= 400 || !is_array($data)) {
            error_log('[gdy_ai_glossary] bad status: ' . $status . ' body: ' . $response);
            return [];
        }

        $content = $data['choices'][0]['message']['content'] ?? '';
        $content = trim((string)$content);

        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```[a-zA-Z0-9]*\s*/', '', $content);
            $content = preg_replace('/```$/', '', $content);
            $content = trim($content);
        }

        $terms = json_decode($content, true);
        if (is_array($terms) === false) {
            error_log('[gdy_ai_glossary] JSON decode failed: ' . $content);
            return [];
        }

        $clean = [];
        foreach ($terms as $row) {
            $term = trim((string)($row['term'] ?? ''));
            $def = trim((string)($row['definition'] ?? ''));

            if ($term === '' || $def === '') {
                continue;
            }

            $clean[] = [
                'term' => $term,
                'definition' => $def,
            ];
        }

        return $clean;
    }
}

if (function_exists('gdy_ai_glossary_annotate') === false) {
    function gdy_ai_glossary_annotate(string $html): string
    {
        $html = (string)$html;
        $plain = trim(strip_tags($html));

        if (mb_strlen($plain, 'UTF-8') < 80) {
            return $html;
        }

        $terms = gdy_ai_glossary_suggest_terms($plain);
        if ($terms === false) {
            return $html;
        }

        foreach ($terms as $row) {
            $term = trim($row['term']);
            $def = trim($row['definition']);

            if ($term === '' || $def === '') {
                continue;
            }

            $pattern = '/' . preg_quote($term, '/') . '/u';
            $replacement = '<span class="gdy-glossary-term" data-definition="' . h($def) . '">$0</span>';
            $html = preg_replace($pattern, $replacement, $html, 3);
        }

        return $html;
    }
}

if (function_exists('u') === false) {
    function u($url): string {
        $url = (string)$url;
        $url = preg_replace('/[\x00-\x1F\x7F]/u', '', $url ?? '');
        $url = trim($url);
        if ($url === '') return '';

        if (preg_match('~^(https?:)?//~i', $url)) {
            if (preg_match('~^(https?://[^/]+)/(https?://)([^/]+)(/.*)?$~i', $url, $m)) {
                $host1 = $m[1];
                $proto2 = $m[2];
                $host2 = $m[3];
                $tail  = $m[4] ?? '';
                if (stripos($host1, $host2) !== false) {
                    $url = $host1 . ($tail !== '' ? $tail : '/');
                } else {
                    $url = $proto2 . $host2 . ($tail !== '' ? $tail : '/');
                }
            }
            return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        }

        $url = '/' . ltrim($url, '/');

        $base = '';
        if (function_exists('base_url')) {
            $base = rtrim(base_url(), '/');
        } else {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = (string)($_SERVER['HTTP_HOST'] ?? '');
            $base = $host !== '' ? ($scheme . '://' . $host) : '';
        }

        $full = $base !== '' ? ($base . $url) : $url;
        return htmlspecialchars($full, ENT_QUOTES, 'UTF-8');
    }
}

if (function_exists('gdy_jsonld_safe') === false) {
    function gdy_jsonld_safe($json): string {
        $json = (string)$json;
        $json = str_ireplace('</script', '<\/script', $json);
        return $json;
    }
}

if (function_exists('gdy_sanitize_basic_html') === false) {
    function gdy_sanitize_basic_html($html): string {
        $html = (string)$html;
        $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $html) ?? '';
        $allowed = '<p><br><b><strong><i><em><u><small><span><div><blockquote><ul><ol><li><h1><h2><h3><h4><h5><h6><a><img>';
        $html = strip_tags($html, $allowed);
        $html = preg_replace("/\s+on[a-z]+\s*=\s*(\"[^\"]*\"|'[^']*'|[^\s>]+)/i", '', $html) ?? '';
        $html = preg_replace("/\s+style\s*=\s*(\"[^\"]*\"|'[^']*'|[^\s>]+)/i", '', $html) ?? '';
        $html = preg_replace_callback("/\s+(href|src)\s*=\s*(\"([^\"]*)\"|'([^']*)')/i", function($m) {
            $attr = strtolower($m[1]);
            $val = $m[3] !== '' ? $m[3] : ($m[4] ?? '');
            $val = preg_replace('/[\x00-\x1F\x7F]/u', '', (string)$val) ?? '';
            $valt = trim($val);
            if ($valt === '') {
                return ' ' . $attr . '="#"';
            }
            if (preg_match('/^(\/|\.|\?|#)/', $valt) || !preg_match('/^[a-zA-Z][a-zA-Z0-9+.-]*:/', $valt)) {
                return ' ' . $attr . '="' . htmlspecialchars($valt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
            }
            $parts = parse_url($valt);
            $scheme = strtolower((string)($parts['scheme'] ?? ''));
            if (in_array($scheme, ['http', 'https', 'mailto', 'tel'], true)) {
                return ' ' . $attr . '="' . htmlspecialchars($valt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
            }
            return ' ' . $attr . '="#"';
        }, $html) ?? $html;
        return $html;
    }
}