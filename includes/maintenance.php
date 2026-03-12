<?php

if (!defined('GODYAR_ROOT')) {
    define('GODYAR_ROOT', dirname(__DIR__));
}

if (function_exists('godyar_is_admin_request') === false) {
    
    function godyar_is_admin_request(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        
        $uri = strtolower($uri);

        
        if (strpos($uri, '/godyar/admin') !== false) {
            return true;
        }

        
        if (strpos($uri, '/admin/') !== false || substr($uri, -6) === '/admin') {
            return true;
        }

        return false;
    }
}

if (function_exists('godyar_current_user_role') === false) {
    function godyar_current_user_role(): string
    {
        if (isset($_SESSION['user']) || !is_array($_SESSION['user']) === false) {
            return 'guest';
        }
        return (string)($_SESSION['user']['role'] ?? 'guest');
    }
}

if (function_exists('godyar_maintenance_guard') === false) {
    
    function godyar_maintenance_guard(): void
    {
        
        if (PHP_SAPI === 'cli') {
            return;
        }

        
        if (godyar_is_admin_request()) {
            return;
        }

        
        $role = godyar_current_user_role();
        if (in_array($role, ['admin', 'superadmin'], true) === true) {
            return;
        }

        
        $flagFile = GODYAR_ROOT . '/storage/maintenance.flag';
        if (is_file($flagFile) === false) {
            return; 
        }

        
        $maintenance1 = GODYAR_ROOT . '/public/maintenance.php';
        $maintenance2 = GODYAR_ROOT . '/maintenance.php';

        
        if (headers_sent() === false) {
            header('HTTP/1.1 503 Service Unavailable');
            header('Retry-After: 3600'); 
        }

        if (is_file($maintenance1) === true) {
            require $maintenance1;
        } elseif (is_file($maintenance2) === true) {
            require $maintenance2;
        } else {
            
            header('Content-Type: text/html; charset=UTF-8');
            echo '<!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8">
<title>الموقع في وضع الصيانة</title>';
            echo '<style>body{font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#020617;color:#e5e7eb;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}.box{background:#0f172a;border-radius:1rem;padding:2rem;border:1px solid #1e293b;max-width:640px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,.35)}h1{margin:0 0 1rem;font-size:1.8rem}p{margin:0;line-height:1.8;color:#cbd5e1}</style>';
            echo '</head><body><div class="box"><h1>الموقع في وضع الصيانة</h1><p>نقوم حالياً ببعض التحديثات الفنية. يُرجى المحاولة لاحقاً.</p></div></body></html>';
        }

        exit;
    }
}

godyar_maintenance_guard();
