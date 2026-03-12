<?php

declare(strict_types=1);


if (!function_exists('godyar_v4_project_root')) {
    function godyar_v4_project_root(): string
    {
        return godyar_v4_project_root();
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
            $path = godyar_v4_project_root() . '/app/Config/' . $file . '.php';
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
        $base = rtrim((string) godyar_v4_config('app.base_url', ''), '/');
        if ($path === '') {
            return $base !== '' ? $base : '/';
        }
        return ($base !== '' ? $base : '') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('godyar_v4_storage_path')) {
    function godyar_v4_storage_path(string $path = ''): string
    {
        $base = dirname(__DIR__, 2) . '/storage';
        return $path === '' ? $base : $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('godyar_v4_theme_path')) {
    function godyar_v4_theme_path(string $path = ''): string
    {
        $theme = (string) godyar_v4_config('theme.active', 'default');
        $base = dirname(__DIR__, 2) . '/themes/' . $theme;
        return $path === '' ? $base : $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
