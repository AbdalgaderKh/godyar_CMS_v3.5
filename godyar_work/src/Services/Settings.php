<?php
namespace App\Services;

use PDO;
use Throwable;

final class Settings
{
    
    public static function normalizeKey(string $key): string
    {
        $key = trim($key);
        if ($key === '') return $key;
        return str_contains($key, '.') ? str_replace('.', '_', $key) : $key;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $rawKey  = trim($key);
        $normKey = self::normalizeKey($rawKey);

        
        if (function_exists('settings_get')) {
            
            
            $v = settings_get($rawKey, null);
            if ($v !== null && $v !== '') return $v;
            if ($normKey !== $rawKey) {
                $v2 = settings_get($normKey, null);
                if ($v2 !== null && $v2 !== '') return $v2;
            }
            return $default;
        }

        
        $val = self::dbGet($rawKey);
        if ($val === null && $normKey !== $rawKey) {
            $val = self::dbGet($normKey);
        }
        if ($val !== null) return $val;

        
        $env = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($env !== false && $env !== null && $env !== '') {
            return $env;
        }

        return $default;
    }

    public static function set(string $key, mixed $value): bool
    {
        $rawKey  = trim($key);
        $normKey = self::normalizeKey($rawKey);

        if (function_exists('settings_set')) {
            
            $ok = (bool) settings_set($rawKey, $value);
            if ($normKey !== $rawKey) {
                $ok2 = (bool) settings_set($normKey, $value);
                return $ok && $ok2;
            }
            return $ok;
        }

        
        $ok = self::dbSet($rawKey, $value);
        if ($normKey !== $rawKey) {
            $ok2 = self::dbSet($normKey, $value);
            return $ok && $ok2;
        }
        return $ok;
    }

    private static function dbGet(string $key): ?string
    {
        try {
            $pdo = self::pdoOrNull();
            if (!$pdo) return null;

            $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
            $stmt->execute([$key]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) return null;
            return (string)($row['setting_value'] ?? '');
        } catch (Throwable) {
            return null;
        }
    }

    private static function dbSet(string $key, mixed $value): bool
    {
        try {
            $pdo = self::pdoOrNull();
            if (!$pdo) return false;

            $value = is_scalar($value) || $value === null ? (string)$value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            
            $stmt = $pdo->prepare(
                "INSERT INTO settings (setting_key, setting_value, updated_at)
                 VALUES (?, ?, NOW())
                 ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_at=NOW()"
            );
            return $stmt->execute([$key, $value]);
        } catch (Throwable) {
            return false;
        }
    }

    private static function pdoOrNull(): ?PDO
    {
        try {
            if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
                return $GLOBALS['pdo'];
            }
            if (class_exists('\\Godyar\\DB') && method_exists('\\Godyar\\DB', 'pdoOrNull')) {
                
                $pdo = \Godyar\DB::pdoOrNull();
                return $pdo instanceof PDO ? $pdo : null;
            }
        } catch (Throwable) {
            
        }
        return null;
    }
}
