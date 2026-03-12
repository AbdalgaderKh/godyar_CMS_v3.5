<?php

namespace Godyar\View;

class ViewComposer
{
    public static function compose(\PDO $pdo): array
    {
        
        $col = 'setting_value';
        try {
            if (function_exists('gdy_settings_value_column')) {
                $col = (string) gdy_settings_value_column($pdo);
            } else {
                
                $cols = [];
                foreach ($pdo->query("SHOW COLUMNS FROM settings")?->fetchAll(\PDO::FETCH_ASSOC) ?? [] as $r) {
                    if (!empty($r['Field'])) $cols[] = $r['Field'];
                }
                if (in_array('setting_value', $cols, true)) $col = 'setting_value';
                elseif (in_array('value', $cols, true)) $col = 'value';
            }
        } catch (\Throwable $e) {
            
        }

        $settings = [];
        $colIdent = function_exists('gdy_sql_ident')
            ? gdy_sql_ident($pdo, (string)$col, ['setting_value','value','val','v','value_text','setting_val','setting_data'], 'setting_value')
            : $col;
        try {
            if (class_exists('\\Cache')) {
                $settings = \Cache::remember('settings_all', 300, function () use ($pdo, $col) {
                    $out = [];
                    $colIdent = function_exists('gdy_sql_ident')
                        ? gdy_sql_ident($pdo, (string)$col, ['setting_value','value','val','v','value_text','setting_val','setting_data'], 'setting_value')
                        : $col;
                    $stmt = $pdo->query("SELECT setting_key, {$colIdent} AS value FROM settings");
                    foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                        $k = (string)($row['setting_key'] ?? '');
                        if ($k !== '') $out[$k] = (string)($row['value'] ?? '');
                    }
                    return $out;
                });
            } else {
                $stmt = $pdo->query("SELECT setting_key, {$colIdent} AS value FROM settings");
                foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                    $k = (string)($row['setting_key'] ?? '');
                    if ($k !== '') $settings[$k] = (string)($row['value'] ?? '');
                }
            }
        } catch (\Throwable $e) {}

        $decode = function (string $key) use ($settings): array {
            if (!isset($settings[$key])) return [];
            $arr = json_decode((string)$settings[$key], true);
            return is_array($arr) ? $arr : [];
        };

        $site_name = $settings['site_name'] ?? ($settings['site_title'] ?? 'Godyar');
        $main_menu = $decode('menu_main');
        $footer_links = $decode('menu_footer');
        $social_links = $decode('social_links');
        $footer_about = $settings['footer_about'] ?? '';

        
        if (class_exists('\\Menus')) {
            try {
                $menus = new \Menus();
                $maybe = $menus->get('main');
                if (is_array($maybe) && $maybe) $main_menu = $maybe;
                $maybe = $menus->get('footer');
                if (is_array($maybe) && $maybe) $footer_links = $maybe;
            } catch (\Throwable $e) {}
        }

        
        $sections = [];
        if (class_exists('\\Categories') && method_exists('\\Categories', 'activeWithArticles')) {
            try {
                if (class_exists('\\Cache')) {
                    $sections = \Cache::remember('home_sections', 300, function () {
                        return \Categories::activeWithArticles(limitPerCategory: 8);
                    });
                } else {
                    $sections = \Categories::activeWithArticles(limitPerCategory: 8);
                }
            } catch (\Throwable $e) { $sections = []; }
        }

        
        $ads_between_posts = [];
        if (class_exists('\\Ads') && method_exists('\\Ads', 'active')) {
            try {
                if (class_exists('\\Cache')) {
                    $ads_between_posts = \Cache::remember('ads_between_posts', 300, function () {
                        return \Ads::active('between_posts', limit: 2);
                    });
                } else {
                    $ads_between_posts = \Ads::active('between_posts', limit: 2);
                }
            } catch (\Throwable $e) { $ads_between_posts = []; }
        }

        return compact('site_name', 'main_menu', 'sections', 'ads_between_posts', 'footer_links', 'social_links', 'footer_about');
    }
}
