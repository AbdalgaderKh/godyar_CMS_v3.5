<?php

if (!function_exists('gdy_translation_enabled')) {
    function gdy_translation_enabled(): bool
    {
        return true;
    }
}

if (!function_exists('gdy_translation_current_lang')) {
    function gdy_translation_current_lang(): string
    {
        if (defined('GDY_LANG') && in_array((string)GDY_LANG, ['ar','en','fr'], true)) {
            return (string)GDY_LANG;
        }
        if (function_exists('gdy_lang')) {
            $l = (string)gdy_lang();
            if (in_array($l, ['ar','en','fr'], true)) return $l;
        }
        return 'ar';
    }
}

if (!function_exists('gdy_translation_pdo')) {
    function gdy_translation_pdo(): ?PDO
    {
        try {
            if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) return $GLOBALS['pdo'];
            if (function_exists('gdy_pdo_safe')) {
                $pdo = gdy_pdo_safe();
                if ($pdo instanceof PDO) return $pdo;
            }
        } catch (Throwable $e) {}
        return null;
    }
}

if (!function_exists('gdy_table_exists_safe')) {
    function gdy_table_exists_safe(PDO $pdo, string $table): bool
    {
        static $cache = [];
        $k = spl_object_id($pdo) . ':' . $table;
        if (array_key_exists($k, $cache)) return $cache[$k];
        try {
            $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
            $st->execute([$table]);
            return $cache[$k] = ((int)$st->fetchColumn() > 0);
        } catch (Throwable $e) {
            return $cache[$k] = false;
        }
    }
}

if (!function_exists('gdy_tr')) {
    function gdy_tr($scope, $itemId, $field, $fallback = null, ?string $lang = null)
    {
        $fallback = $fallback ?? '';
        $lang = $lang ?: gdy_translation_current_lang();
        if ($lang === 'ar') return $fallback;
        $itemId = (int)$itemId;
        if ($itemId <= 0) return $fallback;
        $scope = trim((string)$scope);
        $field = trim((string)$field);
        if ($scope === '' || $field === '') return $fallback;

        $pdo = gdy_translation_pdo();
        if (!$pdo) return $fallback;

        try {
            if (gdy_table_exists_safe($pdo, 'i18n_fields')) {
                $st = $pdo->prepare("SELECT value FROM i18n_fields WHERE scope = ? AND item_id = ? AND lang = ? AND field = ? LIMIT 1");
                $st->execute([$scope, $itemId, $lang, $field]);
                $val = $st->fetchColumn();
                if (is_string($val) && trim($val) !== '') return $val;
            }
        } catch (Throwable $e) {}

        if ($scope === 'news') {
            try {
                if (gdy_table_exists_safe($pdo, 'news_translations')) {
                    $map = [
                        'title' => 'title',
                        'excerpt' => 'excerpt',
                        'summary' => 'excerpt',
                        'content' => 'content',
                        'meta_title' => 'title',
                        'meta_description' => 'excerpt',
                    ];
                    $col = $map[$field] ?? null;
                    if ($col) {
                        $sql = "SELECT `$col` FROM news_translations WHERE news_id = ? AND lang = ? AND status IN ('published','approved','active','ready') ORDER BY id DESC LIMIT 1";
                        $st = $pdo->prepare($sql);
                        $st->execute([$itemId, $lang]);
                        $val = $st->fetchColumn();
                        if (is_string($val) && trim($val) !== '') return $val;
                    }
                }
            } catch (Throwable $e) {}
        }
        return $fallback;
    }
}
