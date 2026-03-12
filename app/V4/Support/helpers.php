<?php
declare(strict_types=1);

use Godyar\DB;

if (!function_exists('godyar_v4_bootstrap_autoload')) {
    function godyar_v4_bootstrap_autoload(string $projectRoot): void
    {
        spl_autoload_register(static function (string $class) use ($projectRoot): void {
            $prefixes = [
                'GodyarV4\\' => $projectRoot . '/app/V4/',
                'Godyar\\' => $projectRoot . '/includes/classes/',
            ];
            foreach ($prefixes as $prefix => $baseDir) {
                if (!str_starts_with($class, $prefix)) {
                    continue;
                }
                $relative = substr($class, strlen($prefix));
                $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
                if (is_file($file)) {
                    require_once $file;
                }
            }
        });
    }
}

if (!function_exists('godyar_v4_project_root')) {
    function godyar_v4_project_root(): string
    {
        return dirname(__DIR__, 3);
    }
}

if (!function_exists('godyar_v4_config')) {
    function godyar_v4_config(string $key, mixed $default = null): mixed
    {
        static $loaded = [];
        [$file, $entry] = array_pad(explode('.', $key, 2), 2, null);
        if (!$file) {
            return $default;
        }
        if (!array_key_exists($file, $loaded)) {
            $path = godyar_v4_project_root() . '/app/V4/Config/' . $file . '.php';
            $loaded[$file] = is_file($path) ? require $path : [];
        }
        if ($entry === null || $entry === '') {
            return $loaded[$file] ?? $default;
        }
        return $loaded[$file][$entry] ?? $default;
    }
}

if (!function_exists('godyar_v4_base_url')) {
    function godyar_v4_base_url(string $path = ''): string
    {
        $base = rtrim((string) (godyar_v4_config('app.base_url', function_exists('base_url') ? base_url() : '')), '/');
        if ($path === '') {
            return $base !== '' ? $base : '/';
        }
        return ($base !== '' ? $base : '') . '/' . ltrim($path, '/');
    }
}


if (!function_exists('godyar_v4_runtime_theme_state')) {
    function godyar_v4_runtime_theme_state(): array
    {
        $file = godyar_v4_storage_path('v4/theme_state.json');
        if (is_file($file)) {
            $json = json_decode((string) file_get_contents($file), true);
            if (is_array($json)) {
                return $json;
            }
        }
        return [];
    }
}

if (!function_exists('godyar_v4_runtime_theme_key')) {
    function godyar_v4_runtime_theme_key(): string
    {
        $state = godyar_v4_runtime_theme_state();
        return (string)($state['theme'] ?? godyar_v4_config('theme.active', 'default'));
    }
}

if (!function_exists('godyar_v4_theme_path')) {
    function godyar_v4_theme_path(string $path = ''): string
    {
        $theme = godyar_v4_runtime_theme_key();
        $base = godyar_v4_project_root() . '/themes/' . $theme;
        return $path === '' ? $base : $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('godyar_v4_assets_url')) {
    function godyar_v4_assets_url(string $path = ''): string
    {
        $theme = godyar_v4_runtime_theme_key();
        $base = '/themes/' . $theme . '/assets';
        return $path === '' ? $base : $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('godyar_v4_storage_path')) {
    function godyar_v4_storage_path(string $path = ''): string
    {
        $base = godyar_v4_project_root() . '/storage';
        return $path === '' ? $base : $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('godyar_v4_db')) {
    function godyar_v4_db(): ?PDO
    {
        try {
            return DB::pdoOrNull();
        } catch (Throwable) {
            return null;
        }
    }
}

if (!function_exists('godyar_v4_url')) {
    function godyar_v4_url(string $locale, string $path = ''): string
    {
        $prefix = '/' . trim($locale, '/');
        $suffix = $path === '' ? '' : '/' . ltrim($path, '/');
        return godyar_v4_base_url($prefix . $suffix);
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('godyar_v4_session_start')) {
    function godyar_v4_session_start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
    }
}

if (!function_exists('godyar_v4_csrf_token')) {
    function godyar_v4_csrf_token(): string
    {
        godyar_v4_session_start();
        if (empty($_SESSION['_godyar_v4_csrf'])) {
            $_SESSION['_godyar_v4_csrf'] = bin2hex(random_bytes(16));
        }
        return (string) $_SESSION['_godyar_v4_csrf'];
    }
}

if (!function_exists('godyar_v4_csrf_field')) {
    function godyar_v4_csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(godyar_v4_csrf_token()) . '">';
    }
}

if (!function_exists('godyar_v4_csrf_valid')) {
    function godyar_v4_csrf_valid(?string $token): bool
    {
        godyar_v4_session_start();
        $known = (string) ($_SESSION['_godyar_v4_csrf'] ?? '');
        return $known !== '' && is_string($token) && hash_equals($known, $token);
    }
}

if (!function_exists('godyar_v4_flash_set')) {
    function godyar_v4_flash_set(string $key, string $message, string $type = 'info'): void
    {
        godyar_v4_session_start();
        $_SESSION['_godyar_v4_flash'][$key] = ['message' => $message, 'type' => $type];
    }
}

if (!function_exists('godyar_v4_flash_get')) {
    function godyar_v4_flash_get(string $key): ?array
    {
        godyar_v4_session_start();
        $value = $_SESSION['_godyar_v4_flash'][$key] ?? null;
        unset($_SESSION['_godyar_v4_flash'][$key]);
        return is_array($value) ? $value : null;
    }
}

if (!function_exists('godyar_v4_admin_user')) {
    function godyar_v4_admin_user(): ?array
    {
        godyar_v4_session_start();
        $user = $_SESSION['user'] ?? null;
        return is_array($user) && !empty($user['id']) ? $user : null;
    }
}

if (!function_exists('godyar_v4_is_admin')) {
    function godyar_v4_is_admin(): bool
    {
        $user = godyar_v4_admin_user();
        if (!$user) { return false; }
        $role = strtolower((string)($user['role'] ?? ''));
        return in_array($role, ['admin','administrator','superadmin','editor'], true);
    }
}

if (!function_exists('godyar_v4_admin_login_url')) {
    function godyar_v4_admin_login_url(): string
    {
        return function_exists('admin_url') ? admin_url('login.php') : godyar_v4_base_url('/admin/login.php');
    }
}

if (!function_exists('godyar_v4_media_url')) {
    function godyar_v4_media_url(string $path): string
    {
        $path = ltrim(trim($path), '/');
        if ($path === '') { return ''; }
        if (preg_match('#^https?://#i', $path)) { return $path; }
        if (str_starts_with($path, 'public/')) { $path = substr($path, 7); }
        return godyar_v4_base_url('/' . $path);
    }
}


if (!function_exists('godyar_v4_active_theme_css')) {
    function godyar_v4_active_theme_css(): string
    {
        $state = godyar_v4_runtime_theme_state();
        $css = basename((string)($state['css'] ?? 'theme-default.css'));
        return $css !== '' ? $css : 'theme-default.css';
    }
}

if (!function_exists('godyar_v4_theme_settings')) {
    function godyar_v4_theme_settings(): array
    {
        $file = godyar_v4_storage_path('v4/theme_customizer.json');
        if (is_file($file)) {
            $json = json_decode((string) file_get_contents($file), true);
            if (is_array($json)) {
                return $json;
            }
        }
        return [];
    }
}

if (!function_exists('godyar_v4_theme_setting')) {
    function godyar_v4_theme_setting(string $key, mixed $default = null): mixed
    {
        $settings = godyar_v4_theme_settings();
        return $settings[$key] ?? $default;
    }
}

if (!function_exists('godyar_v4_theme_inline_vars')) {
    function godyar_v4_theme_inline_vars(): string
    {
        $vars = [];
        $map = [
            'primary_color' => '--g-primary',
            'background_color' => '--g-bg',
            'text_color' => '--g-text',
            'card_color' => '--g-card',
            'muted_color' => '--g-muted',
            'border_color' => '--g-border',
            'shell_width' => '--g-shell-width',
            'radius' => '--g-radius-xl',
        ];
        foreach ($map as $key => $cssVar) {
            $value = trim((string) godyar_v4_theme_setting($key, ''));
            if ($value !== '') {
                $vars[] = $cssVar . ':' . $value;
            }
        }
        return $vars ? ':root{' . implode(';', $vars) . ';}' : '';
    }
}


if (!function_exists('godyar_v4_hooks')) {
    function godyar_v4_hooks(): \GodyarV4\Services\HookBus
    {
        if (!isset($GLOBALS['godyar_v4_hook_bus']) || !$GLOBALS['godyar_v4_hook_bus'] instanceof \GodyarV4\Services\HookBus) {
            $GLOBALS['godyar_v4_hook_bus'] = new \GodyarV4\Services\HookBus();
        }
        return $GLOBALS['godyar_v4_hook_bus'];
    }
}

if (!function_exists('godyar_v4_hook_render')) {
    function godyar_v4_hook_render(string $event, array $payload = []): string
    {
        return godyar_v4_hooks()->render($event, $payload);
    }
}
