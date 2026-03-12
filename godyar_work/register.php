<?php

if (!function_exists('godyar_legacy_base_prefix')) {
    function godyar_legacy_base_prefix(): string
    {
        $script = (string)($_SERVER['SCRIPT_NAME'] ?? '');
        $dir = str_replace('\\', '/', dirname($script));
        if ($dir === '/' || $dir === '.' || $dir === '\\') {
            return '';
        }
        return rtrim($dir, '/');
    }
}

$base = godyar_legacy_base_prefix();
header('Location: ' . ($base !== '' ? $base : '') . '/register', true, 302);
exit;
