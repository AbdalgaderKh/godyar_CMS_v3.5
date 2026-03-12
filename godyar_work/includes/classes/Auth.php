<?php

namespace Godyar;

class Auth
{
    
    private const SESSION_KEY = 'user';

    public static function user(): ?array
    {
        return (isset($_SESSION[self::SESSION_KEY]) && is_array($_SESSION[self::SESSION_KEY]))
            ? $_SESSION[self::SESSION_KEY]
            : null;
    }

    public static function id(): int
    {
        $u = self::user();
        return $u && isset($u['id']) ? (int)$u['id'] : 0;
    }

    public static function role(): string
    {
        $u = self::user();
        $r = $u['role'] ?? '';
        return is_string($r) ? $r : '';
    }

    public static function isLoggedIn(): bool
    {
        return self::id() > 0;
    }

    public static function isAdmin(): bool
    {
        $role = self::role();
        return in_array($role, ['admin', 'superadmin', 'owner'], true);
    }

    public static function isWriter(): bool
    {
        return self::role() === 'writer';
    }

    
    public static function hasPermission(string $perm): bool
    {
        if (!self::isLoggedIn()) {
            return false;
        }

        if (self::isAdmin()) {
            return true;
        }

        
        if (self::isWriter()) {
            $perm = strtolower(trim($perm));
            $allowedPrefixes = [
                'posts.',
                'news.',
                'comments.',
                'media.',
                'uploads.',
                'profile.',
            ];
            foreach ($allowedPrefixes as $pfx) {
                if (str_starts_with($perm, $pfx)) {
                    return true;
                }
            }

            
            $allowedExact = [
                'dashboard.view',
                'home.view',
            ];
            return in_array($perm, $allowedExact, true);
        }

        return false;
    }

    public static function requireLogin(?string $redirectTo = null): void
    {
        if (self::isLoggedIn()) {
            return;
        }

        $redirectTo = $redirectTo ?: (defined('GDY_ADMIN_URL') ? GDY_ADMIN_URL : '/admin');
        $login = rtrim($redirectTo, '/') . '/login.php';

        
        $next = $_SERVER['REQUEST_URI'] ?? '';
        if ($next) {
            $login .= (str_contains($login, '?') ? '&' : '?') . 'next=' . rawurlencode($next);
        }

        header('Location: ' . $login, true, 302);
        exit;
    }

    public static function requirePermission(string $perm): void
    {
        self::requireLogin();

        if (self::hasPermission($perm)) {
            return;
        }

        
        $home = (defined('GDY_ADMIN_URL') ? GDY_ADMIN_URL : '/admin') . '/index.php';
        header('Location: ' . $home, true, 302);
        exit;
    }

    public static function logout(): void
    {
        if (isset($_SESSION[self::SESSION_KEY])) {
            unset($_SESSION[self::SESSION_KEY]);
        }
    }
}
