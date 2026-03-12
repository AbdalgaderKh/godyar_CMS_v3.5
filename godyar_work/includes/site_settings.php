<?php

if (!function_exists('gdy_normalize_site_logo_value')) {
    function gdy_normalize_site_logo_value(string $raw, string $baseUrl = ''): string {
        $v = trim($raw);
        if ($v === '') return '';

        
        if (stripos($v, 'data:') === 0) return $v;

        
        $v = trim($v, " \t\n\r\0\x0B\"'");
        if ($v === '') return '';

        
        $baseUrl = trim($baseUrl);

        
        if (preg_match('~^https?://~i', $v)) {
            $u = @parse_url($v);
            if (is_array($u)) {
                $path = $u['path'] ?? '';
                $host = strtolower((string)($u['host'] ?? ''));
                $baseHost = '';
                if ($baseUrl !== '') {
                    $bu = @parse_url($baseUrl);
                    if (is_array($bu)) $baseHost = strtolower((string)($bu['host'] ?? ''));
                }
                if ($baseHost !== '' && $host === $baseHost && $path !== '') {
                    $v = $path;
                }
            }
        }

        
        if (strpos($v, '//') === 0 && $baseUrl !== '') {
            $maybe = 'https:' . $v;
            $u = @parse_url($maybe);
            $bu = @parse_url($baseUrl);
            if (is_array($u) && is_array($bu)) {
                $host = strtolower((string)($u['host'] ?? ''));
                $baseHost = strtolower((string)($bu['host'] ?? ''));
                $path = $u['path'] ?? '';
                if ($baseHost !== '' && $host === $baseHost && $path !== '') {
                    $v = $path;
                }
            }
        }

        
        if ($baseUrl !== '') {
            $b = rtrim($baseUrl, '/');
            if ($b !== '' && stripos($v, $b) === 0) {
                $v = substr($v, strlen($b));
            }
        }

        
        $v = ltrim($v);
        if ($v !== '' && $v[0] !== '/') {
            $v = '/' . $v;
        }

        
        $v = preg_replace('~/{2,}~', '/', $v) ?? $v;

        return $v;
    }
}

if (!function_exists('gdy_pdo_is_pgsql')) {
    function gdy_pdo_is_pgsql(PDO $pdo): bool {
        try {
            return stripos((string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME), 'pgsql') !== false;
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('gdy_settings_value_column')) {
    
    function gdy_settings_value_column(PDO $pdo): string {
        static $cache = null;
        if (is_string($cache) && $cache !== '') {
            return $cache;
        }

        $cache = 'value';
        try {
            $isPg = gdy_pdo_is_pgsql($pdo);
            if ($isPg) {
                $cols = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_schema='public' AND table_name='settings'")
                            ->fetchAll(PDO::FETCH_COLUMN);
                $cols = is_array($cols) ? $cols : [];
                if (in_array('setting_value', $cols, true)) return $cache = 'setting_value';
                if (in_array('value', $cols, true)) return $cache = 'value';
            } else {
                $cols = $pdo->query("SHOW COLUMNS FROM settings")->fetchAll(PDO::FETCH_COLUMN);
                $cols = is_array($cols) ? $cols : [];
                if (in_array('setting_value', $cols, true)) return $cache = 'setting_value';
                if (in_array('value', $cols, true)) return $cache = 'value';
            }
        } catch (\Throwable $e) {
            
        }

        return $cache;
    }
}

if (!function_exists('gdy_ensure_settings_table')) {
    function gdy_ensure_settings_table(PDO $pdo): void {
        $isPg = gdy_pdo_is_pgsql($pdo);

        if ($isPg) {
            
            $stmt = $pdo->prepare("SELECT to_regclass('public.settings')");
            $stmt->execute();
            $exists = (string)$stmt->fetchColumn();
            if ($exists === '' || strtolower($exists) === 'null') {
                $pdo->exec("CREATE TABLE settings (
                    setting_key VARCHAR(191) PRIMARY KEY,
                    setting_value TEXT NOT NULL
                )");
            }
            
            $cols = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_schema='public' AND table_name='settings'")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('setting_key', $cols, true)) {
                $pdo->exec("ALTER TABLE settings ADD COLUMN setting_key VARCHAR(191)");
            }
            if (!in_array('setting_value', $cols, true)) {
                $pdo->exec("ALTER TABLE settings ADD COLUMN setting_value TEXT");
            }
        } else {
            
            $stmt = $pdo->prepare("SHOW TABLES LIKE 'settings'");
            $stmt->execute();
            $exists = (bool)$stmt->fetchColumn();

            if (!$exists) {
                $pdo->exec("CREATE TABLE settings (
                    setting_key VARCHAR(191) NOT NULL PRIMARY KEY,
                    setting_value TEXT NOT NULL
                ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci");
            } else {
                
                $cols = $pdo->query("SHOW COLUMNS FROM settings")->fetchAll(PDO::FETCH_COLUMN);
                if (!in_array('setting_key', $cols, true)) {
                    
                    $pdo->exec("ALTER TABLE settings ADD COLUMN setting_key VARCHAR(191) NULL");
                }
                if (!in_array('setting_value', $cols, true)) {
                    
                    if (in_array('value', $cols, true)) {
                        $pdo->exec("ALTER TABLE settings CHANGE COLUMN value setting_value TEXT NOT NULL");
                    } else {
                        $pdo->exec("ALTER TABLE settings ADD COLUMN setting_value TEXT NOT NULL");
                    }
                }

                
                try {
                    $pdo->exec("ALTER TABLE settings ADD PRIMARY KEY (setting_key)");
                } catch (\Throwable $e) {
                    
                }
            }
        }
    }
}

if (!function_exists('gdy_load_settings')) {
    
    function gdy_load_settings($pdo, bool $forceRefresh = false): array {
        static $cache = null;

        
        if (!($pdo instanceof PDO)) {
            return [];
        }

        if ($cache !== null && !$forceRefresh) {
            return $cache;
        }

if (!$forceRefresh && class_exists('Cache')) {
    $cached = Cache::get('site_settings_all_v1');
    if (is_array($cached)) {
        $cache = $cached;
        return $cache;
    }
}

        gdy_ensure_settings_table($pdo);

        $rows = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) {
            $k = (string)($r['setting_key'] ?? '');
            if ($k === '') { continue; }
            $out[$k] = (string)($r['setting_value'] ?? '');
        }

	    
	    
	    
	    
	    
	    
	    $aliases = [
            'site.logo' => 'site_logo',
            'site.name' => 'site_name',
            'site.desc' => 'site_desc',
            'site.url' => 'site_url',
            'site.email' => 'site_email',
            'site.phone' => 'site_phone',
            'site.address' => 'site_address',
            'site.favicon' => 'site_favicon',
            'site.theme_color' => 'theme_color',
        ];
	    foreach ($aliases as $from => $to) {
	        if ((!array_key_exists($to, $out) || trim((string)$out[$to]) === '') && array_key_exists($from, $out)) {
	            $out[$to] = (string)$out[$from];
	        }
	    }
        if ((!array_key_exists('logo', $out) || trim((string)$out['logo']) === '') && array_key_exists('site_logo', $out)) {
            $out['logo'] = (string)$out['site_logo'];
        }
        if ((!array_key_exists('site_logo', $out) || trim((string)$out['site_logo']) === '') && array_key_exists('site.logo', $out)) {
            $out['site_logo'] = (string)$out['site.logo'];
        }
        if ((!array_key_exists('logo', $out) || trim((string)$out['logo']) === '') && array_key_exists('site.logo', $out)) {
            $out['logo'] = (string)$out['site.logo'];
        }
        if ((!array_key_exists('site.logo', $out) || trim((string)$out['site.logo']) === '') && array_key_exists('site_logo', $out)) {
            $out['site.logo'] = (string)$out['site_logo'];
        }

        
        
        
        if (array_key_exists('site_favicon', $out)) {
            $base = defined('BASE_URL') ? (string)BASE_URL : '';
            $fixedFav = gdy_normalize_site_logo_value((string)$out['site_favicon'], $base);
            if ($fixedFav !== (string)$out['site_favicon']) {
                try {
                    $stmt = $pdo->prepare('UPDATE settings SET setting_value = :v WHERE setting_key = :k');
                    $stmt->execute([':v' => $fixedFav, ':k' => 'site_favicon']);
                    if ((int)$stmt->rowCount() === 0) {
                        $ins = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v)');
                        $ins->execute([':k' => 'site_favicon', ':v' => $fixedFav]);
                    }
                    $out['site_favicon'] = $fixedFav;
                } catch (\Throwable $e) {}
            }
            if ((!array_key_exists('site.favicon', $out) || trim((string)$out['site.favicon']) === '')) {
                $out['site.favicon'] = (string)$out['site_favicon'];
            }
        }

        if (array_key_exists('site_logo', $out)) {
            $base = defined('BASE_URL') ? (string)BASE_URL : '';
            $fixed = gdy_normalize_site_logo_value((string)$out['site_logo'], $base);
            if ($fixed !== (string)$out['site_logo']) {
                try {
	                
	                $stmt = $pdo->prepare('UPDATE settings SET setting_value = :v WHERE setting_key = :k');
	                $stmt->execute([':v' => $fixed, ':k' => 'site_logo']);
	                if ((int)$stmt->rowCount() === 0) {
	                    $ins = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v)');
	                    $ins->execute([':k' => 'site_logo', ':v' => $fixed]);
	                }
	                $out['site_logo'] = $fixed;
                } catch (\Throwable $e) {
                    
                }
            }
        }

        $cache = $out;
        if (class_exists('Cache')) { Cache::put('site_settings_all_v1', $out, 3600); }
        return $out;
    }
}

function site_settings_load($pdo, bool $forceRefresh = false): array {
    return gdy_load_settings($pdo, $forceRefresh);
}

if (!function_exists('site_setting')) {
    
    function site_setting($pdo, string $key = '', $default = ''): string {
        
        
        

        
        if (!($pdo instanceof PDO)) {
            $keyArg = (string)$pdo;
            $defaultArg = $key;

            $pdo = $GLOBALS['pdo'] ?? null;
            if (!($pdo instanceof PDO)) { return (string)$defaultArg; }

            $key = $keyArg;
            $default = $defaultArg;
        }

        $key = trim((string)$key);
        if ($key === '') { return (string)$default; }

        $all = gdy_load_settings($pdo, false);
        return array_key_exists($key, $all) ? (string)$all[$key] : (string)$default;
    }
}

if (!function_exists('site_settings_all')) {
    function site_settings_all($pdo): array {
        if (!($pdo instanceof PDO)) { return []; }
        return gdy_load_settings($pdo, false);
    }
}

if (!function_exists('site_settings_set')) {
    
    function site_settings_set($pdo, string $key, string $value): bool {
        if (!($pdo instanceof PDO)) { return false; }
        $key = trim($key);
        if ($key === '') { return false; }

        gdy_ensure_settings_table($pdo);

        $isPg = gdy_pdo_is_pgsql($pdo);

        try {
            if ($isPg) {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value)
                    VALUES (:k, :v)
                    ON CONFLICT (setting_key) DO UPDATE SET setting_value = EXCLUDED .setting_value");
                $ok = $stmt->execute([':k' => $key, ':v' => $value]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value)
                    VALUES (:k, :v)
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                $ok = $stmt->execute([':k' => $key, ':v' => $value]);
            }

            
            gdy_load_settings($pdo, true);
            return (bool)$ok;
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('settings_set')) {
    
    function settings_set(string $key, string $value): bool {
        $pdo = $GLOBALS['pdo'] ?? null;
        return site_settings_set($pdo, $key, $value);
    }
}

if (!function_exists('settings_get')) {
    
    function settings_get(string $key, $default = ''): string {
        $pdo = $GLOBALS['pdo'] ?? null;
        return site_setting($pdo, $key, $default);
    }
}

if (!function_exists('settings_all')) {
    
    function settings_all(): array {
        $pdo = $GLOBALS['pdo'] ?? null;
        return site_settings_all($pdo);
    }
}

if (!function_exists('gdy_prepare_frontend_options')) {
    
    function gdy_prepare_frontend_options($settings = null): array {
        if (!is_array($settings)) {
            
            try {
                $pdo = function_exists('gdy_pdo_safe') ? gdy_pdo_safe() : null;
            } catch (\Throwable $e) {
                $pdo = null;
            }
            $settings = ($pdo instanceof PDO) ? gdy_load_settings($pdo, false) : [];
        }
        
        $baseUrl = function_exists('base_url') ? rtrim((string)base_url(), '/') : (defined('GODYAR_BASE_URL') ? (string)GODYAR_BASE_URL : '');
        if ($baseUrl === '') { $baseUrl = '/'; }

        
        $lang = function_exists('gdy_lang') ? (string)gdy_lang() : (string)($settings['site_lang'] ?? $settings['lang'] ?? 'ar');
        $lang = trim($lang);
        if ($lang === '') { $lang = 'ar'; }

        
        $siteName = (string)($settings['site_name'] ?? $settings['settings.site_name'] ?? 'Godyar');
        $siteTagline = (string)($settings['site_tagline'] ?? $settings['settings.site_tagline'] ?? '');
        $siteLogo = (string)($settings['site_logo'] ?? $settings['settings.site_logo'] ?? '');

        
        $frontPreset = (string)($settings['front_preset'] ?? $settings['settings.front_preset'] ?? 'default');
        $frontPreset = strtolower(trim($frontPreset)) ?: 'default';

        $primaryColor = (string)($settings['primary_color']
 ?? $settings['theme_primary']
 ?? $settings['settings.primary_color']
 ?? '#111111');

        
        if ($frontPreset !== 'custom') {
            $primaryColor = '#111111';
        }

        $primaryDark = (string)($settings['primary_dark']
 ?? $settings['theme_primary_dark']
 ?? $settings['settings.primary_dark']
 ?? '');

        if ($primaryDark === '') {
            $hex = ltrim($primaryColor, '#');
            if (preg_match('/^[0-9a-f]{6}$/i', $hex)) {
                $r = max(0, hexdec(substr($hex, 0, 2))-40);
                $g = max(0, hexdec(substr($hex, 2, 2))-40);
                $b = max(0, hexdec(substr($hex, 4, 2))-40);
                $primaryDark = sprintf('#%02x%02x%02x', $r, $g, $b);
            } else {
                $primaryDark = '#000000';
            }
        }

        $primaryRgb = '17, 17, 17';
        try {
            $hex = ltrim($primaryColor, '#');
            if (preg_match('/^[0-9a-f]{6}$/i', $hex)) {
                $r = hexdec(substr($hex, 0, 2));
                $g = hexdec(substr($hex, 2, 2));
                $b = hexdec(substr($hex, 4, 2));
                $primaryRgb = $r . ', ' . $g . ', ' . $b;
            }
        } catch (\Throwable $e) {
            $primaryRgb = '17, 17, 17';
        }

        $themeClass = (string)($settings['theme_class'] ?? 'theme-default');
        if ($themeClass === '') { $themeClass = 'theme-default'; }

        
        $headerBgEnabled = ((string)($settings['theme_header_bg_enabled'] ?? $settings['settings.theme_header_bg_enabled'] ?? '0') === '1');

        
        $searchPlaceholder = (string)($settings['search_placeholder'] ?? $settings['settings.search_placeholder'] ?? 'ابحث...');

        
        $navBaseUrl = rtrim($baseUrl, '/') . '/' .trim($lang, '/');
        if ($baseUrl === '/' || $baseUrl === '') { $navBaseUrl = '/' .trim($lang, '/'); }

        return [
            
            'baseUrl' => $baseUrl,
            'navBaseUrl' => $navBaseUrl,
            '_gdyLang' => $lang,
            'siteName' => $siteName,
            'siteTagline' => $siteTagline,
            'siteLogo' => $siteLogo,
            'frontPreset' => $frontPreset,
            'primaryColor' => $primaryColor,
            'primaryDark' => $primaryDark,
            'primaryRgb' => $primaryRgb,
            'themeClass' => $themeClass,
            'headerBgEnabled' => $headerBgEnabled,
            'searchPlaceholder' => $searchPlaceholder,
            
            'settings' => $settings,
        ];
    }
}
