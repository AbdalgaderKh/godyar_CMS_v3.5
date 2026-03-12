<?php

$bootstrap = __DIR__ . '/../includes/bootstrap.php';
if (is_file($bootstrap)) {
    require_once $bootstrap;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    if (function_exists('gdy_session_start')) {
        gdy_session_start();
    } else {
        session_start();
    }
}

$loginUrl = (function_exists('admin_url') === true)
    ? admin_url('login.php')
    : ((function_exists('base_url') === true) ? base_url('/admin/login.php') : '/admin/login.php');

if (!function_exists('gdy_admin_normalize_url_value')) {
    function gdy_admin_normalize_url_value(string $value): string
    {
        $value = trim($value);

        if (
            $value === '' ||
            str_starts_with($value, '#') ||
            preg_match('~^(?:https?:|mailto:|tel:|javascript:|data:)~i', $value)
        ) {
            return $value;
        }

        $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/admin/index.php'));
        $dir = rtrim(dirname($script), '/.');
        if ($dir === '') {
            $dir = '/admin';
        }

        $base = function_exists('base_url') ? rtrim((string)base_url(), '/') : '';

        if (str_starts_with($value, '/')) {
            $abs = $value;
        } elseif (preg_match('~^admin/(.*)$~i', $value, $m)) {
            $abs = '/admin/' . ltrim((string)$m[1], '/');
        } else {
            $abs = $dir . '/' . $value;
        }

        $abs = preg_replace('~/+~', '/', $abs) ?? $abs;
        $abs = preg_replace('~(?:/admin){2,}/~i', '/admin/', $abs) ?? $abs;
        $abs = preg_replace('~^/admin/([^/]+)/admin/\1/~i', '/admin/$1/', $abs) ?? $abs;

        return $base !== '' ? $base . $abs : $abs;
    }
}

if (!function_exists('gdy_admin_response_normalizer')) {
    function gdy_admin_response_normalizer(string $html): string
    {
        
        
        
        
        
        
        return $html;
    }
}

try {
    if (function_exists('gdy_start_csrf_form_injector')) {
        gdy_start_csrf_form_injector();
    }

    if (!defined('GDY_ADMIN_HTML_NORMALIZER_STARTED')) {
        define('GDY_ADMIN_HTML_NORMALIZER_STARTED', true);
        ob_start('gdy_admin_response_normalizer');
    }
} catch (Throwable $e) {
    
}

try {
    $script = (string)($_SERVER['SCRIPT_NAME'] ?? '');
    $isApi = (stripos($script, '/admin/api/') !== false);

    if (
        !$isApi &&
        (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')
    ) {
        if (function_exists('verify_csrf_or_throw')) {
            verify_csrf_or_throw();
        } elseif (function_exists('verify_csrf')) {
            if (!verify_csrf()) {
                throw new RuntimeException('CSRF failed');
            }
        }
    }
} catch (Throwable $e) {
    if (defined('GDY_ADMIN_JSON') && GDY_ADMIN_JSON) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'csrf'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $_SESSION['admin_flash'] = [
        'type' => 'danger',
        'msg'  => 'فشل التحقق الأمني. حدّث الصفحة وحاول مجددًا.',
    ];

    $back = $_SERVER['HTTP_REFERER'] ?? $loginUrl;
    if (!headers_sent()) {
        header('Location: ' . $back);
    }
    exit;
}

$__i18n = __DIR__ . '/i18n.php';
if (is_file($__i18n)) {
    require_once $__i18n;
}

if (!function_exists('h')) {
    function h($v): string
    {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

$authFile = __DIR__ . '/../includes/auth.php';
if (is_file($authFile)) {
    require_once $authFile;
}

$__auditDb = __DIR__ . '/includes/audit_db.php';
if (is_file($__auditDb)) {
    require_once $__auditDb;
}

try {
    if (class_exists('Godyar\\Auth') && method_exists('Godyar\\Auth', 'isLoggedIn')) {
        $loggedIn = \Godyar\Auth::isLoggedIn();
    } else {
        $loggedIn = !empty($_SESSION['user']['id']) && (($_SESSION['user']['role'] ?? 'guest') !== 'guest');
    }

    if (!$loggedIn) {
        if (function_exists('gdy_security_log')) {
            gdy_security_log('admin_unauthorized', ['path' => ($_SERVER['REQUEST_URI'] ?? '')]);
        }

        if (defined('GDY_ADMIN_JSON') && GDY_ADMIN_JSON) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            http_response_code(401);
            echo json_encode(['ok' => false, 'msg' => 'auth'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (!headers_sent()) {
            header('Location: ' . $loginUrl);
        }
        exit;
    }
} catch (Throwable $e) {
    error_log('[Admin Guard] ' . $e->getMessage());

    if (empty($_SESSION['user']['id'])) {
        if (function_exists('gdy_security_log')) {
            gdy_security_log('admin_unauthorized', ['path' => ($_SERVER['REQUEST_URI'] ?? '')]);
        }

        if (defined('GDY_ADMIN_JSON') && GDY_ADMIN_JSON) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            http_response_code(401);
            echo json_encode(['ok' => false, 'msg' => 'auth'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (!headers_sent()) {
            header('Location: ' . $loginUrl);
        }
        exit;
    }
}

require_once __DIR__ . '/_role_guard.php';

try {
    $uid = (int)($_SESSION['user']['id'] ?? ($_SESSION['user_id'] ?? 0));

    if ($uid > 0) {
        $pdo = null;

        if (class_exists('Godyar\\DB') && method_exists('Godyar\\DB', 'pdo')) {
            $pdo = \Godyar\DB::pdo();
        } elseif (function_exists('gdy_pdo_safe')) {
            $pdo = gdy_pdo_safe();
        }

        if ($pdo instanceof PDO) {
            $st = $pdo->prepare("SELECT session_version FROM users WHERE id = ? LIMIT 1");
            $st->execute([$uid]);

            $dbSv = $st->fetchColumn();
            $dbSv = is_numeric($dbSv) ? (int)$dbSv : 0;

            if (!isset($_SESSION['session_version']) || !is_numeric($_SESSION['session_version'])) {
                $_SESSION['session_version'] = $dbSv;
            } else {
                $sessSv = (int)$_SESSION['session_version'];

                if ($sessSv !== $dbSv) {
                    if (function_exists('gdy_security_log')) {
                        gdy_security_log('admin_unauthorized', ['path' => ($_SERVER['REQUEST_URI'] ?? '')]);
                    }

                    if (defined('GDY_ADMIN_JSON') && GDY_ADMIN_JSON) {
                        if (!headers_sent()) {
                            header('Content-Type: application/json; charset=utf-8');
                        }
                        http_response_code(401);
                        echo json_encode(['ok' => false, 'msg' => 'session_expired'], JSON_UNESCAPED_UNICODE);
                        exit;
                    }

                    if (session_status() === PHP_SESSION_ACTIVE) {
                        session_destroy();
                    }

                    if (!headers_sent()) {
                        header('Location: ' . $loginUrl . '?msg=session_expired');
                    }
                    exit;
                }
            }
        }
    }
} catch (Throwable $e) {
    error_log('[Admin Guard] session_version: ' . $e->getMessage());
}

if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (function_exists('gdy_session_start')) {
                gdy_session_start();
            } else {
                session_start();
            }
        }

        if (empty($_SESSION['_csrf_token'])) {
            try {
                $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
            } catch (Throwable $e) {
                if (function_exists('openssl_random_pseudo_bytes')) {
                    $fallback = openssl_random_pseudo_bytes(32);
                    if ($fallback !== false) {
                        $_SESSION['_csrf_token'] = bin2hex($fallback);
                    } else {
                        $_SESSION['_csrf_token'] = bin2hex(hash('sha256', microtime(true) . '|' . getmypid() . '|' . session_id(), true));
                    }
                } else {
                    $_SESSION['_csrf_token'] = bin2hex(hash('sha256', microtime(true) . '|' . getmypid() . '|' . session_id(), true));
                }
            }
        }

        return (string)$_SESSION['_csrf_token'];
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token(?string $token): bool
    {
        $token = (string)($token ?? '');
        $sessionToken = (string)($_SESSION['_csrf_token'] ?? '');

        if ($token === '' || $sessionToken === '') {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return generate_csrf_token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        $t = csrf_token();
        echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($t, ENT_QUOTES, 'UTF-8') . '">';
        return '';
    }
}

if (!function_exists('verify_csrf')) {
    function verify_csrf(string $fieldName = 'csrf_token'): bool
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return true;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (function_exists('gdy_session_start')) {
                gdy_session_start();
            } else {
                session_start();
            }
        }

        $sent = (string)($_POST[$fieldName] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));

        if (!verify_csrf_token($sent)) {
            http_response_code(400);

            $accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
            $ctype  = (string)($_SERVER['CONTENT_TYPE'] ?? '');
            $isJson = (stripos($accept, 'application/json') !== false)
                || (stripos($ctype, 'application/json') !== false);

            if ($isJson) {
                if (function_exists('gdy_security_log')) {
                    gdy_security_log('admin_unauthorized', ['path' => ($_SERVER['REQUEST_URI'] ?? '')]);
                }

                if (!headers_sent()) {
                    header('Content-Type: application/json; charset=UTF-8');
                }

                echo json_encode(['ok' => false, 'error' => 'csrf_failed'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            die('CSRF validation failed');
        }

        return true;
    }
}

verify_csrf();