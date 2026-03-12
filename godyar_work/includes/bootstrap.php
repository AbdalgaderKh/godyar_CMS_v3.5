<?php

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__)); 
}

if (!defined('GDY_BOOTSTRAPPED')) {
    define('GDY_BOOTSTRAPPED', true);
}

if (!defined('GODYAR_ROOT')) {
    define('GODYAR_ROOT', ROOT_PATH);

$__safe = __DIR__ . '/safe_runtime.php';
if (is_file($__safe)) { require_once $__safe; }
$__fs = __DIR__ . '/fs.php';
if (is_file($__fs)) { require_once $__fs; }
$__logger = __DIR__ . '/logger.php';
if (is_file($__logger)) { require_once $__logger; if (function_exists('gdy_register_error_handlers')) { gdy_register_error_handlers(); } }

$__dbCompat = __DIR__ . '/db_compat.php';
if (is_file($__dbCompat)) { require_once $__dbCompat; }
$__siteSettings = __DIR__ . '/site_settings.php';
if (is_file($__siteSettings)) { require_once $__siteSettings; }
$__runtime = __DIR__ . '/gdy_runtime.php';
if (is_file($__runtime)) { require_once $__runtime; }
$__perf = __DIR__ . '/performance_bootstrap.php';
if (is_file($__perf)) { require_once $__perf; }

$__i18n = __DIR__ . '/i18n.php';
if (is_file($__i18n)) { require_once $__i18n; }
$__lang = __DIR__ . '/lang.php';
if (is_file($__lang)) { require_once $__lang; }
$__translation = __DIR__ . '/translation.php';
if (is_file($__translation)) { require_once $__translation; }
$__coreRefactor = __DIR__ . '/core_refactor.php';
if (is_file($__coreRefactor)) { require_once $__coreRefactor; }
$__arch = __DIR__ . '/architecture_cleanup.php';
if (is_file($__arch)) { require_once $__arch; }
$__plugins = __DIR__ . '/plugins.php';
if (is_file($__plugins)) { require_once $__plugins; }

if (function_exists('gdy_boot_error_logging')) {
    gdy_boot_error_logging();
}
}

$__secHeaders = __DIR__ . '/security_headers.php';
if (is_file($__secHeaders)) {
    require_once $__secHeaders;
    if (function_exists('gdy_apply_security_headers')) {
        gdy_apply_security_headers();
    }
}

if (session_status() === PHP_SESSION_NONE) {
    @ini_set('session.use_strict_mode', '1');
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    @session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => (getenv('GDY_SESSION_SAMESITE') ?: 'Strict'),
    ]);

    @session_start();
}

if (!defined('APP_DEBUG')) { define('APP_DEBUG', false); }
if (!APP_DEBUG) {
    @ini_set('display_errors', '0');
    @ini_set('display_startup_errors', '0');
    @ini_set('log_errors', '1');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        return (string)($_SESSION['csrf_token'] ?? '');
    }
}

if (!function_exists('csrf_verify')) {
    function csrf_verify(string $token): bool {
        $sess = (string)($_SESSION['csrf_token'] ?? '');
        return ($sess !== '') && hash_equals($sess, (string)$token);
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(string $name = 'csrf_token'): string {
        $t = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
        $n = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="' . $n . '" value="' . $t . '">';
    }
}

if (!function_exists('verify_csrf')) {
    function verify_csrf(): bool {
        $token = '';
        if (isset($_POST['csrf_token'])) {
            $token = (string)$_POST['csrf_token'];
        } elseif (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = (string)$_SERVER['HTTP_X_CSRF_TOKEN'];
        } elseif (!empty($_SERVER['HTTP_X_CSRF'])) {
            $token = (string)$_SERVER['HTTP_X_CSRF'];
        }
        return csrf_verify($token);
    }
}

if (!function_exists('verify_csrf_or_throw')) {
    function verify_csrf_or_throw(): void {
        if (!verify_csrf()) {
            throw new RuntimeException('CSRF verification failed');
        }
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token(?string $token = null): bool {
        $token = (string)($token ?? '');
        if ($token === '' && isset($_POST['csrf_token'])) {
            $token = (string)$_POST['csrf_token'];
        }
        return csrf_verify($token);
    }
}

if (!function_exists('gdy_inject_csrf_forms')) {
    function gdy_inject_csrf_forms(string $html): string {
        if (stripos($html, '<form') === false) return $html;

        $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');

        
        
        return preg_replace_callback('~<form\b([^>]*)>(.*?)</form>~is', function($m) use ($token) {
            $attrs = $m[1] ?? '';
            $inner = $m[2] ?? '';

            if (!preg_match('~\bmethod\s*=\s*([\'\"])?post\1~i', $attrs)) {
                return $m[0];
            }

            
            if (preg_match('~name\s*=\s*([\'\"])(csrf_token|_csrf)\1~i', $inner)) {
                return $m[0];
            }

            $open  = '<form' . $attrs . '>';
            $field = "\n" . '<input type="hidden" name="csrf_token" value="' . $token . '">';
            return $open . $field . $inner . '</form>';
        }, $html);
    }
}

if (!function_exists('gdy_start_csrf_form_injector')) {
    function gdy_start_csrf_form_injector(): void {
        if (defined('GDY_CSRF_INJECTOR_STARTED')) return;
        define('GDY_CSRF_INJECTOR_STARTED', true);
        ob_start('gdy_inject_csrf_forms');
    }
}

if (!function_exists('gdy_detect_base_url')) {
    function gdy_detect_base_url(): string {
        $candidates = [];
        foreach (['APP_URL','BASE_URL','GODYAR_BASE_URL','SITE_URL'] as $k) {
            $v = getenv($k);
            if (is_string($v) && trim($v) !== '' && stripos($v, 'change_me') === false) {
                $candidates[] = trim($v);
            }
            if (isset($_ENV[$k]) && is_string($_ENV[$k]) && trim((string)$_ENV[$k]) !== '' && stripos((string)$_ENV[$k], 'change_me') === false) {
                $candidates[] = trim((string)$_ENV[$k]);
            }
        }
        if (!empty($GLOBALS['baseUrl']) && is_string($GLOBALS['baseUrl'])) {
            $candidates[] = (string)$GLOBALS['baseUrl'];
        }
        foreach ($candidates as $u) {
            $u = trim((string)$u);
            if ($u === '' || !preg_match('~^https?://~i', $u)) continue;
            $parts = parse_url($u) ?: [];
            $scheme = $parts['scheme'] ?? 'https';
            $host = $parts['host'] ?? '';
            if ($host === '') continue;
            $port = isset($parts['port']) ? (':' . (int)$parts['port']) : '';
            $path = isset($parts['path']) ? rtrim((string)$parts['path'], '/') : '';
            $path = preg_replace('~/(admin)(?:/.*)?$~i', '', $path) ?? $path;
            return rtrim($scheme . '://' . $host . $port . $path, '/');
        }
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        $scheme = $https ? 'https' : 'http';
        $host   = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
        $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
        $dir = str_replace('\\', '/', dirname($script));
        if ($dir === '/' || $dir === '.' || $dir === '\\') { $dir = ''; }
        $dir = preg_replace('~/(admin)(?:/.*)?$~i', '', rtrim($dir, '/')) ?? $dir;
        return rtrim($scheme . '://' . $host . $dir, '/');
    }
}

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string {
        $base = gdy_detect_base_url();
        if ($path === '') return $base;
        if (preg_match('~^https?://~i', $path)) return $path;
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('site_url')) {
    function site_url(string $path = ''): string {
        return base_url($path);
    }
}

if (!function_exists('admin_url')) {
    function admin_url(string $path = ''): string {
        $path = ltrim($path, '/');
        if (stripos($path, 'admin/') === 0) $path = substr($path, 6);
        $base = rtrim(base_url(), '/');
        return $base . '/admin' . ($path !== '' ? '/' . $path : '');
    }
}

if (!function_exists('gdy_parse_ini_file')) {
    function gdy_parse_ini_file(string $file): array {
        if (!is_file($file)) return [];
        $data = @parse_ini_file($file, true, INI_SCANNER_TYPED);
        return is_array($data) ? $data : [];
    }
}

if (!function_exists('gdy_session_regenerate')) {
    function gdy_session_regenerate(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_regenerate_id(true);
        }
    }
}

spl_autoload_register(function (string $class): void {
    $class = ltrim($class, '\\');

    
    if (strpos($class, 'App\\') === 0) {
        $rel = str_replace('\\', '/', $class) . '.php';
        $candidates = [
            ROOT_PATH . '/' . $rel,
            ROOT_PATH . '/app/' . substr($rel, 4),
            ROOT_PATH . '/src/' . substr($rel, 4),
        ];
        foreach ($candidates as $file) {
            if (is_file($file)) { require_once $file; return; }
        }
    }

    
    if (strpos($class, 'Godyar\\') === 0) {
        $short = substr($class, 6); 
        $relShort = str_replace('\\', '/', $short) . '.php';
        $candidates = [
            ROOT_PATH . '/includes/classes/' . $relShort,
            ROOT_PATH . '/includes/' . $relShort,
            ROOT_PATH . '/app/' . $relShort,
            ROOT_PATH . '/src/' . $relShort,
            ROOT_PATH . '/' . str_replace('\\', '/', $class) . '.php',
        ];
        foreach ($candidates as $file) {
            if (is_file($file)) { require_once $file; return; }
        }
    }
});

$functionsFile = ROOT_PATH . '/includes/functions.php';
if (is_file($functionsFile)) {
    require_once $functionsFile;

$__hardening = __DIR__ . '/security_hardening.php';
if (is_file($__hardening)) { require_once $__hardening; }

} else {
    error_log("Warning: functions.php not found at " . $functionsFile);
}

try {
    if (!isset($GLOBALS['pdo']) && class_exists('\\Godyar\\DB')) {
        $GLOBALS['pdo'] = \Godyar\DB::pdoOrNull();
    }
} catch (Throwable $e) {
    
}

$GLOBALS['translations'] = $GLOBALS['translations'] ?? [];
$GLOBALS['current_language'] = $GLOBALS['current_language'] ?? 'ar';
$lang = (string)($GLOBALS['current_language'] ?? 'ar');
$langFile = ROOT_PATH . '/includes/languages/' . $lang . '.php';
if (is_file($langFile)) {
    $t = require $langFile;
    if (is_array($t)) $GLOBALS['translations'] = $t;
}

if (!function_exists('__')) {
    function __(string $key, $vars = []): string {
        $out = $GLOBALS['translations'][$key] ?? $key;

        if (!is_array($vars)) {
            $alt = trim((string)$vars);
            if ($alt !== '') $out = $alt;
            $vars = [];
        }

        foreach ($vars as $k => $v) {
            $out = str_replace('{' . $k . '}', (string)$v, $out);
        }
        return (string)$out;
    }
}

if (!function_exists('_')) {
    function _(string $key, $vars = []): string {
        return __($key, $vars);
    }
}

if (!function_exists('current_language')) {
    function current_language(): string {
        return (string)($GLOBALS['current_language'] ?? 'ar');
    }
}

if (!function_exists('is_rtl')) {
    function is_rtl(): bool {
        return in_array(current_language(), ['ar','he','fa','ur'], true);
    }
}

if (!function_exists('dir_attr')) {
    function dir_attr(): string {
        return is_rtl() ? 'rtl' : 'ltr';
    }
}

if (function_exists('gdy_csrf_ob_callback') && PHP_SAPI !== 'cli') {
    
    if (!headers_sent() && !ob_get_level()) {
        @ob_start('gdy_csrf_ob_callback');
    }
}

if (PHP_SAPI !== 'cli' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $uri = (string)($_SERVER['REQUEST_URI'] ?? '');
    $isApi = (stripos($uri, '/admin/api/') === 0) || (stripos($uri, '/api/') === 0);

    if (!$isApi) {
        $token = $_POST['_csrf'] ?? $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        $token = is_string($token) ? $token : null;
        $ok = false;
        if (function_exists('gdy_csrf_verify')) {
            $ok = gdy_csrf_verify($token);
        }
        if (!$ok && function_exists('verify_csrf_token') && is_string($token)) {
            $ok = verify_csrf_token($token);
        }
        if (!$ok) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=UTF-8');
            echo "CSRF validation failed";
            exit;
        }
    }
}

