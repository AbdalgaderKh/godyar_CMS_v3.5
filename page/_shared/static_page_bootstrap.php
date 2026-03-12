<?php
declare(strict_types=1);

if (!function_exists('gdy_h')) {
    function gdy_h($v): string
    {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('gdy_static_bootstrap')) {
    function gdy_static_bootstrap(): void
    {
        static $booted = false;
        if ($booted) {
            return;
        }
        $booted = true;

        $root = dirname(__DIR__, 2);
        $bootstrap = $root . '/includes/bootstrap.php';
        if (is_file($bootstrap)) {
            require_once $bootstrap;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (function_exists('gdy_session_start')) {
                gdy_session_start();
            } else {
                @session_start();
            }
        }
    }
}

if (!function_exists('gdy_static_base_url')) {
    function gdy_static_base_url(): string
    {
        if (function_exists('base_url')) {
            return rtrim((string) base_url(), '/');
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        return $host !== '' ? $scheme . '://' . $host : '';
    }
}

if (!function_exists('gdy_static_lang')) {
    function gdy_static_lang(): string
    {
        if (function_exists('gdy_lang')) {
            $lang = (string) gdy_lang();
            if (in_array($lang, ['ar', 'en', 'fr'], true)) {
                return $lang;
            }
        }

        $uri = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        if (preg_match('#^/(ar|en|fr)(?:/|$)#i', $uri, $m)) {
            return strtolower((string) $m[1]);
        }

        return 'ar';
    }
}

if (!function_exists('gdy_static_dir')) {
    function gdy_static_dir(): string
    {
        if (function_exists('gdy_dir')) {
            return (string) gdy_dir();
        }
        return gdy_static_lang() === 'ar' ? 'rtl' : 'ltr';
    }
}

if (!function_exists('gdy_static_url')) {
    function gdy_static_url(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        $lang = gdy_static_lang();
        $base = gdy_static_base_url();
        $prefix = ($lang !== 'ar') ? '/' . $lang : '';
        return $base . $prefix . $path;
    }
}

if (!function_exists('gdy_static_get_flash')) {
    function gdy_static_get_flash(string $key): ?array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }
        $value = $_SESSION[$key] ?? null;
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
        return is_array($value) ? $value : null;
    }
}

if (!function_exists('gdy_static_csrf_input')) {
    function gdy_static_csrf_input(): string
    {
        if (function_exists('csrf_token')) {
            return '<input type="hidden" name="csrf_token" value="' . gdy_h((string) csrf_token()) . '">';
        }
        if (function_exists('csrf_field')) {
            return (string) csrf_field();
        }
        return '';
    }
}

if (!function_exists('gdy_static_render_header')) {
    function gdy_static_render_header(string $title, string $description): bool
    {
        gdy_static_bootstrap();

        $baseUrl = gdy_static_base_url();
        $requestPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        $canonical = ($baseUrl !== '' ? $baseUrl : '') . ($requestPath !== '' ? $requestPath : '/');

        $GLOBALS['siteTitle'] = $title;
        $GLOBALS['pageTitle'] = $title;
        $GLOBALS['meta_title'] = $title;
        $GLOBALS['siteDescription'] = $description;
        $GLOBALS['metaDescription'] = $description;
        $GLOBALS['meta_description'] = $description;
        $GLOBALS['pageCanonical'] = $canonical;
        $GLOBALS['canonical_url'] = $canonical;
        $GLOBALS['baseUrl'] = $baseUrl;
        $GLOBALS['lang'] = $GLOBALS['lang'] ?? gdy_static_lang();
        $GLOBALS['dir'] = $GLOBALS['dir'] ?? gdy_static_dir();

        foreach ([
            dirname(__DIR__, 2) . '/frontend/templates/header.php',
            dirname(__DIR__, 2) . '/frontend/views/partials/header.php',
            dirname(__DIR__, 2) . '/header.php',
        ] as $headerFile) {
            if (is_file($headerFile)) {
                require $headerFile;
                return true;
            }
        }

        echo '<!doctype html><html lang="' . gdy_h(gdy_static_lang()) . '" dir="' . gdy_h(gdy_static_dir()) . '"><head><meta charset="utf-8">
';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<title>' . gdy_h($title) . '</title>';
        echo '<meta name="description" content="' . gdy_h($description) . '">';
        echo '<link rel="stylesheet" href="' . gdy_h(gdy_static_base_url() . '/assets/css/themes/theme-core.css') . '">';
        echo '</head><body>';
        return false;
    }
}

if (!function_exists('gdy_static_render_footer')) {
    function gdy_static_render_footer(bool $headerIncluded): void
    {
        if ($headerIncluded) {
            foreach ([
                dirname(__DIR__, 2) . '/frontend/templates/footer.php',
                dirname(__DIR__, 2) . '/frontend/views/partials/footer.php',
            ] as $footerFile) {
                if (is_file($footerFile)) {
                    require $footerFile;
                    return;
                }
            }
        }

        echo '</body></html>';
    }
}
