<?php
namespace Godyar;

final class EnvStore {
    public static array $data = [];
    public static bool $loaded = false;
}

if (!function_exists(__NAMESPACE__ . '\\env')) {
    function env(string $key, $default = null) {
        if (!EnvStore::$loaded) {
            load_env_once();
        }

        if (array_key_exists($key, EnvStore::$data)) {
            return cast_env_value(EnvStore::$data[$key]);
        }

        $v = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($v !== false && $v !== null && $v !== '') {
            return cast_env_value((string)$v);
        }

        return $default;
    }
}

if (!function_exists(__NAMESPACE__ . '\\load_env_once')) {
    function load_env_once(): void {
        EnvStore::$loaded = true;

        $candidates = [
            dirname(__DIR__) . '/.env',
            __DIR__ . '/../.env',
        ];

        $path = null;
        foreach ($candidates as $p) {
            if (is_file($p) && is_readable($p)) { $path = $p; break; }
        }
        if (!$path) return;

        $lines = @file($path, FILE_IGNORE_NEW_LINES);
        if (!is_array($lines)) return;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;

            $pos = strpos($line, '=');
            if ($pos === false) continue;

            $k = trim(substr($line, 0, $pos));
            $v = trim(substr($line, $pos + 1));

            if ($k === '') continue;

            if (strlen($v) >= 2) {
                $first = $v[0];
                $last  = $v[strlen($v) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $v = substr($v, 1, -1);
                }
            }

            $v = str_replace(['\\n','\\r','\\t'], ["\n","\r","\t"], $v);

            EnvStore::$data[$k] = $v;
        }
    }
}

if (!function_exists(__NAMESPACE__ . '\\cast_env_value')) {
    function cast_env_value(string $v) {
        $lv = strtolower(trim($v));
        if ($lv === 'true' || $lv === '(true)') return true;
        if ($lv === 'false' || $lv === '(false)') return false;
        if ($lv === 'null' || $lv === '(null)') return null;
        if ($lv === 'empty' || $lv === '(empty)') return '';
        if (is_numeric($v)) {
            return (str_contains($v, '.') ? (float)$v : (int)$v);
        }
        return $v;
    }
}
