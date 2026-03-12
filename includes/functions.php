<?php

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/../');
}

if (!function_exists('gdy_session_start')) {
    function gdy_session_start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        @session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        @session_start();
    }
}

if (!function_exists('asset_url')) {
    function asset_url(string $path = ''): string
    {
        $base = function_exists('base_url') ? rtrim((string)base_url(), '/') : '';
        $path = ltrim($path, '/');
        if ($base === '') {
            return '/' . $path;
        }
        return $base . '/' . $path;
    }
}

if (!function_exists('u')) {
    function u(string $url): string
    {
        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('gdy_sql_ident')) {
    function gdy_sql_ident(PDO $pdo, string $name, array $allowed, string $fallback = ''): string
    {
        $name = trim($name);
        if ($name === '' || in_array($name, $allowed, true) === false) {
            $name = ($fallback !== '' && in_array($fallback, $allowed, true)) ? $fallback : (string)($allowed[0] ?? '');
        }

        $driver = '';
        try { $driver = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME); } catch (Throwable $e) { $driver = ''; }
        $isPg = (stripos($driver, 'pgsql') !== false);

        if ($isPg) {
            return '"' . str_replace('"', '""', $name) . '"';
        }
        return '`' . str_replace('`', '', $name) . '`';
    }
}

if (!function_exists('gdy_settings_value_column')) {
    function gdy_settings_value_column(PDO $pdo): string
    {
        static $cached = null;
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }
        try {
            $st = $pdo->query('SHOW COLUMNS FROM `settings`');
            $cols = $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
            $fields = [];
            foreach ($cols as $row) {
                if (!empty($row['Field'])) {
                    $fields[] = (string)$row['Field'];
                }
            }
            $candidates = ['setting_value', 'value', 'val', 'v', 'value_text', 'setting_val', 'setting_data'];
            foreach ($candidates as $c) {
                if (in_array($c, $fields, true)) {
                    $cached = $c;
                    return $c;
                }
            }
        } catch (Throwable $e) {
            
        }
        $cached = 'setting_value';
        return $cached;
    }
}

if (!function_exists('gdy_load_settings')) {
    function gdy_load_settings(PDO $pdo, bool $preferFlat = true): array
    {
        
        $col = gdy_settings_value_column($pdo);
        if (function_exists('gdy_sql_ident')) {
            $colIdent = gdy_sql_ident($pdo, $col, ['setting_value','value','val','v','value_text','setting_val','setting_data']);
        } else {
            
            $colIdent = '`' . str_replace('`', '', $col) . '`';
        }
        $sql = "SELECT `setting_key`, {$colIdent} AS `v` FROM `settings`";
        $st = $pdo->query($sql);
        $pairs = $st ? ($st->fetchAll(PDO::FETCH_KEY_PAIR) ?: []) : [];
        if (!is_array($pairs)) {
            return [];
        }
        foreach ($pairs as $k => $v) {
            $pairs[$k] = is_scalar($v) ? (string)$v : json_encode($v, JSON_UNESCAPED_UNICODE);
        }
        return $pairs;
    }
}

function gdy_path_within(string $path, string $baseDir): bool {
    $base = realpath($baseDir);
    $real = realpath($path);
    if ($base === false || $real === false) {
        return false;
    }
    $base = rtrim(str_replace('\\', '/', $base), '/') . '/';
    return strncmp($real . '/', $base, strlen($base)) === 0;
}

function apply_theme_colors(): void {
    static $applied = false;
    if ($applied) {
        return;
    }
    $applied = true;
    $nonce = '';
    if (defined('GDY_CSP_NONCE') && (string)GDY_CSP_NONCE !== '') {
        $nonce = (string)GDY_CSP_NONCE;
    } elseif (!empty($GLOBALS['cspNonce'])) {
        $nonce = (string)$GLOBALS['cspNonce'];
    } elseif (!empty($_SESSION['csp_nonce'])) {
        $nonce = (string)$_SESSION['csp_nonce'];
    }
    $attr = $nonce !== '' ? ' nonce="' . htmlspecialchars($nonce, ENT_QUOTES, 'UTF-8') . '"' : '';
    echo '<style' . $attr . '>
            :root {
                --turquoise-60: rgba(64, 224, 208, 0.6);
                --turquoise-2: rgba(64, 224, 208, 0.02);
                --turquoise-3_5: rgba(64, 224, 208, 0.035);
                --turquoise-10: rgba(64, 224, 208, 0.1);
                --turquoise-20: rgba(64, 224, 208, 0.2);
            }
            .godyar-header,
            .godyar-footer {
                background: linear-gradient(135deg, var(--turquoise-60), rgba(32, 178, 170, 0.6)) !important;
                backdrop-filter: blur(10px);
            }
            .godyar-body {
                background-color: var(--turquoise-2) !important;
                background-image:
                    radial-gradient(var(--turquoise-10) 1px, transparent 1px),
                    radial-gradient(var(--turquoise-10) 1px, transparent 1px);
                background-size: 50px 50px;
                background-position: 0 0, 25px 25px;
            }
        </style>';
}

function load_dynamic_css(array $styles = []): void {
    $default_styles = [
        'primary_color' => '#40e0d0',
        'secondary_color' => '#20b2aa',
        'background_color' => 'rgba(64, 224, 208, 0.02)',
        'card_background' => 'rgba(64, 224, 208, 0.035)'
    ];
    $styles = array_merge($default_styles, $styles);
    $css = "
        <style>
            .dynamic-primary { color: {$styles['primary_color']} !important; }
            .dynamic-bg { background: {$styles['background_color']} !important; }
            .dynamic-card { background: {$styles['card_background']} !important; }
            .btn-dynamic {
                background: linear-gradient(135deg, {$styles['primary_color']}, {$styles['secondary_color']}) !important;
            }
        </style>
    ";
    echo $css;
}

if (function_exists('generate_csrf_token') === false) {
    function generate_csrf_token(): string {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            gdy_session_start();
        }
        $existing = (string)($_SESSION['csrf_token'] ?? $_SESSION['_csrf'] ?? '');
        if ($existing === '') {
            $existing = bin2hex(random_bytes(32));
            $_SESSION['csrf_time'] = time();
        }
        $_SESSION['csrf_token'] = $existing;
        $_SESSION['_csrf'] = $existing;
        if (empty($_SESSION['csrf_time'])) {
            $_SESSION['csrf_time'] = time();
        }
        return $existing;
    }
}

if (function_exists('csrf_token') === false) {
    function csrf_token(): string {
        return generate_csrf_token();
    }
}

if (function_exists('verify_csrf_token') === false) {
    function verify_csrf_token(string $token): bool {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            gdy_session_start();
        }
        $sessionToken = (string)($_SESSION['csrf_token'] ?? $_SESSION['_csrf'] ?? '');
        if ($sessionToken === '' || $token === '') {
            return false;
        }
        if (!hash_equals($sessionToken, (string)$token)) {
            return false;
        }
        $_SESSION['csrf_token'] = $sessionToken;
        $_SESSION['_csrf'] = $sessionToken;
        $token_time = (int)($_SESSION['csrf_time'] ?? 0);
        if ($token_time > 0 && (time() - $token_time) > 1800) {
            unset($_SESSION['csrf_token'], $_SESSION['_csrf'], $_SESSION['csrf_time']);
            return false;
        }
        return true;
    }
}

if (function_exists('csrf_field') === false) {
    function csrf_field(string $name = 'csrf_token'): string {
        $token = csrf_token();
        $html = '<input type="hidden" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') .
            '" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
        echo $html;
        return $html;
    }
}

if (function_exists('csrf_verify_or_die') === false) {
    function csrf_verify_or_die(string $fieldName = 'csrf_token'): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            gdy_session_start();
        }
        $sent = $_POST[$fieldName] ?? '';
        if (!verify_csrf_token((string)$sent)) {
            http_response_code(400);
            die('CSRF validation failed');
        }
    }
}

if (function_exists('csrf_verify_any_or_die') === false) {
    function csrf_verify_any_or_die(string $fieldName = 'csrf_token'): void {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            gdy_session_start();
        }
        $token = (string)($_POST[$fieldName] ?? '');
        if ($token === '') {
            $token = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_SERVER['HTTP_X_CSRFTOKEN'] ?? '');
        }
        if (!verify_csrf_token($token)) {
            if (function_exists('gdy_security_log')) {
                gdy_security_log('csrf_block', [
                    'path' => ($_SERVER['REQUEST_URI'] ?? ''),
                    'ip' => ($_SERVER['REMOTE_ADDR'] ?? ''),
                    'ua' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 200)
                ]);
            }
            http_response_code(403);
            die('CSRF validation failed');
        }
    }
}

function gdy_origin_guard_or_die(): void {
    if ((string)($_ENV['GDY_ORIGIN_GUARD'] ?? getenv('GDY_ORIGIN_GUARD') ?? '1') === '0') {
        return;
    }
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        return;
    }
    if ($method === 'OPTIONS') {
        return;
    }
    $hasCookies = !empty($_COOKIE);
    if (!$hasCookies) {
        return;
    }
    $strict = ((string)($_ENV['GDY_ORIGIN_GUARD_STRICT'] ?? getenv('GDY_ORIGIN_GUARD_STRICT') ?? '0') === '1');
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    if ($host === '') {
        return;
    }
    $host = strtolower(preg_replace('/:\\d+$/', '', $host));
    $trusted = (string)($_ENV['GDY_TRUSTED_ORIGINS'] ?? getenv('GDY_TRUSTED_ORIGINS') ?? '');
    $trustedList = [];
    if ($trusted !== '') {
        foreach (explode(',', $trusted) as $o) {
            $o = trim($o);
            if ($o !== '') {
                $trustedList[] = $o;
            }
        }
    }
    $origin = (string)($_SERVER['HTTP_ORIGIN'] ?? '');
    $referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
    $ok = null;
    $matchesHost = static function (string $url) use ($host, $trustedList): bool {
        $u = parse_url($url);
        if (!is_array($u)) return false;
        $h = strtolower((string)($u['host'] ?? ''));
        if ($h === '') return false;
        if ($h === $host) return true;
        foreach ($trustedList as $t) {
            $tu = parse_url($t);
            if (is_array($tu)) {
                $th = strtolower((string)($tu['host'] ?? ''));
                if ($th !== '' && $th === $h) return true;
            }
        }
        return false;
    };
    if ($origin !== '') {
        $ok = $matchesHost($origin);
    } elseif ($referer !== '') {
        $ok = $matchesHost($referer);
    } else {
        $ok = null;
    }
    if ($ok === false || ($ok === null && $strict)) {
        if (function_exists('gdy_security_log')) {
            gdy_security_log('origin_block', [
                'path' => ($_SERVER['REQUEST_URI'] ?? ''),
                'ip' => ($_SERVER['REMOTE_ADDR'] ?? ''),
                'origin' => ($_SERVER['HTTP_ORIGIN'] ?? ''),
                'referer' => ($_SERVER['HTTP_REFERER'] ?? '')
            ]);
        }
        http_response_code(403);
        $accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
        $xhr = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
        $wantsJson = $xhr || stripos($accept, 'application/json') !== false;
        if ($wantsJson) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'origin_not_allowed'], JSON_UNESCAPED_UNICODE);
        } else {
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Forbidden';
        }
        exit;
    }
}

function safe_redirect(string $url, int $status_code = 302): void {
    if (headers_sent() === false) {
        header("Location: " . $url, true, $status_code);
        exit;
    }
    echo '<script>window.location.href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '";</script>';
    exit;
}

function log_error(string $message, array $context = []): void {
    $log_dir = ABSPATH . '/storage/logs';
    if (is_dir($log_dir) === false) {
        mkdir($log_dir, 0755, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    $context_str = !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) : '';
    $log_message = "[{$timestamp}] {$message} {$context_str}" . PHP_EOL;
    file_put_contents($log_dir . '/errors.log', $log_message, FILE_APPEND | LOCK_EX);
}

function log_event(string $event, string $type = 'info', array $data = []): void {
    $log_dir = ABSPATH . '/storage/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_id = $_SESSION['user_id'] ?? 'guest';
    $data_str = !empty($data) ? json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) : '';
    $log_message = "[{$timestamp}] [{$type}] [{$ip}] [user:{$user_id}] {$event} {$data_str}" . PHP_EOL;
    file_put_contents($log_dir . '/events.log', $log_message, FILE_APPEND | LOCK_EX);
}

if (!function_exists('sanitize_input')) {
    function sanitize_input($data) {
        if (is_array($data)) {
            return array_map('sanitize_input', $data);
        }
        if ($data === null) {
            return '';
        }
        $data = trim((string)$data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return $data;
    }
}

function is_valid_email(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function is_valid_number($number, ?int $min = null, ?int $max = null): bool {
    if (!is_numeric($number)) {
        return false;
    }
    $number = (int)$number;
    if ($min !== null && $number < $min) {
        return false;
    }
    if ($max !== null && $number > $max) {
        return false;
    }
    return true;
}

function truncate_text(string $text, int $length = 100, string $suffix = '...'): string {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . $suffix;
}

function format_arabic_date(string $date, bool $include_time = false): string {
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }
    $months = [
        'January' => 'يناير',
        'February' => 'فبراير',
        'March' => 'مارس',
        'April' => 'أبريل',
        'May' => 'مايو',
        'June' => 'يونيو',
        'July' => 'يوليو',
        'August' => 'أغسطس',
        'September' => 'سبتمبر',
        'October' => 'أكتوبر',
        'November' => 'نوفمبر',
        'December' => 'ديسمبر'
    ];
    $english_month = date('F', $timestamp);
    $arabic_month = $months[$english_month] ?? $english_month;
    $formatted = date('d', $timestamp) . ' ' . $arabic_month . ' ' . date('Y', $timestamp);
    if ($include_time) {
        $formatted .= '-' . date('H:i', $timestamp);
    }
    return $formatted;
}

function generate_slug(string $text): string {
    $text = trim($text);
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/\s+/', '-', $text);
    $text = preg_replace('/[^\p{L}\p{N}\-]/u', '', $text);
    $text = preg_replace('/-+/', '-', $text);
    $text = trim((string)$text, '-');
    return (string)$text;
}

function has_permission(string $permission): bool {
    if (!isset($_SESSION['user_role'])) {
        return false;
    }
    $user_role = $_SESSION['user_role'];
    $permissions = [
        'admin' => ['manage_users', 'manage_content', 'manage_settings', 'manage_security', 'manage_plugins'],
        'editor' => ['manage_content', 'manage_media'],
        'author' => ['manage_own_content'],
        'user' => ['view_content']
    ];
    return in_array($permission, $permissions[$user_role] ?? [], true);
}

function add_flash_message(string $message, string $type = 'info'): void {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'message' => $message,
        'type' => $type,
        'time' => time()
    ];
}

function display_flash_messages(): void {
    if (empty($_SESSION['flash_messages'])) {
        return;
    }
    foreach ($_SESSION['flash_messages'] as $message) {
        switch ((string)($message['type'] ?? 'info')) {
            case 'success':
                $alert_class = 'alert-success';
                break;
            case 'error':
                $alert_class = 'alert-danger';
                break;
            case 'warning':
                $alert_class = 'alert-warning';
                break;
            default:
                $alert_class = 'alert-info';
                break;
        }
        echo '<div class="alert ' . $alert_class . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($message['message'], ENT_QUOTES, 'UTF-8');
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
    $_SESSION['flash_messages'] = [];
}

function get_site_settings(): array {
    static $settings = null;
    if ($settings === null) {
        $settings_file = ABSPATH . '/storage/settings/site.json';
        if (file_exists($settings_file)) {
            $settings = json_decode((string)file_get_contents($settings_file), true) ?? [];
        } else {
            $settings = [
                'site_name' => 'Godyar',
                'site_description' => 'نظام إدارة المحتوى',
                'site_url' => base_url(),
                'timezone' => 'Asia/Riyadh',
                'language' => 'ar'
            ];
        }
    }
    return $settings;
}

function get_setting(string $key, $default = null) {
    $settings = get_site_settings();
    return $settings[$key] ?? $default;
}

function update_site_settings(array $new_settings): bool {
    $current_settings = get_site_settings();
    $updated_settings = array_merge($current_settings, $new_settings);
    $settings_dir = ABSPATH . '/storage/settings';
    if (!is_dir($settings_dir)) {
        mkdir($settings_dir, 0755, true);
    }
    $result = file_put_contents(
        $settings_dir . '/site.json',
        json_encode($updated_settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
    );
    return $result !== false;
}

if (!function_exists('current_user_subscription')) {
    function current_user_subscription(): ?array {
        if (empty($_SESSION['user']['id']) && empty($_SESSION['user_id'])) {
            return null;
        }
        $userId = !empty($_SESSION['user']['id'])
            ? (int)$_SESSION['user']['id']
            : (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            return null;
        }
        $pdo = gdy_pdo_safe();
        if (($pdo instanceof \PDO) === false) {
            return null;
        }
        $sql = "SELECT s.*, p.slug AS plan_slug, p.name AS plan_name
                FROM user_subscriptions s
                JOIN membership_plans p ON p.id = s.plan_id
                WHERE s.user_id = :uid
                  AND s.status = 'active'
                  AND s.starts_at <= NOW()
                  AND s.ends_at >= NOW()
                LIMIT 1";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':uid' => $userId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\Throwable $e) {
            error_log('[Godyar Membership] ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('user_has_plan')) {
    function user_has_plan(string $planSlug): bool {
        $sub = current_user_subscription();
        if (!$sub) {
            return false;
        }
        return ((string)$sub['plan_slug'] === $planSlug);
    }
}

if (!function_exists('require_plan')) {
    function require_plan(string $planSlug): void {
        if (empty($_SESSION['user']['id']) && empty($_SESSION['user_id'])) {
            header('Location: /godyar/login.php');
            exit;
        }
        if (!user_has_plan($planSlug)) {
            header('Location: /godyar/upgrade.php');
            exit;
        }
    }
}

$helpers_file = __DIR__ . '/helpers.php';
if (file_exists($helpers_file)) {
    include $helpers_file;
}

if (!function_exists('is_ajax_request')) {
    function is_ajax_request(): bool
    {
        $xrw = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        if (is_string($xrw) && strtolower($xrw) === 'xmlhttprequest') {
            return true;
        }
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (is_string($accept) && strpos($accept, 'application/json') !== false) {
            return true;
        }
        return false;
    }
}

if (!function_exists('gdy_comment_counts_for_news')) {
    function gdy_comment_counts_for_news(PDO $pdo, array $newsIds): array
    {
        $ids = [];
        foreach ($newsIds as $id) {
            $i = (int)$id;
            if ($i > 0) $ids[$i] = $i;
        }
        if (!$ids) return [];
        $ids = array_values($ids);
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $sqlApproved = "SELECT news_id, COUNT(*) AS c FROM news_comments WHERE status = 'approved' AND news_id IN ($ph) GROUP BY news_id";
        $sqlAny = "SELECT news_id, COUNT(*) AS c FROM news_comments WHERE news_id IN ($ph) GROUP BY news_id";
        try {
            $st = $pdo->prepare($sqlApproved);
            $st->execute($ids);
        } catch (\Throwable $e) {
            try {
                $st = $pdo->prepare($sqlAny);
                $st->execute($ids);
            } catch (Exception $e2) {
                return [];
            }
        }
        $map = [];
        while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $nid = (int)($row['news_id'] ?? 0);
            $cnt = (int)($row['c'] ?? 0);
            if ($nid > 0) $map[$nid] = $cnt;
        }
        return $map;
    }
}

if (!function_exists('gdy_attach_comment_counts_to_news_rows')) {
    function gdy_attach_comment_counts_to_news_rows(PDO $pdo, array $rows): array
    {
        if (!$rows) return $rows;
        $ids = [];
        foreach ($rows as $r) {
            if (is_array($r) && isset($r['id'])) $ids[] = (int)$r['id'];
        }
        $counts = gdy_comment_counts_for_news($pdo, $ids);
        if (!$counts) {
            foreach ($rows as &$r) {
                if (is_array($r) && !isset($r['comments_count'])) $r['comments_count'] = 0;
            }
            unset($r);
            return $rows;
        }
        foreach ($rows as &$r) {
            if (!is_array($r)) continue;
            $id = (int)($r['id'] ?? 0);
            $r['comments_count'] = $counts[$id] ?? 0;
        }
        unset($r);
        return $rows;
    }
}

if (!function_exists('gdy_should_bypass_list_cache')) {
    function gdy_should_bypass_list_cache(): bool
    {
        if (defined('APP_DEBUG') && APP_DEBUG) return true;
        if (!empty($_GET['nocache'])) return true;
        $cc = $_SERVER['HTTP_CACHE_CONTROL'] ?? '';
        if (is_string($cc) && stripos($cc, 'no-cache') !== false) return true;
        return false;
    }
}

if (!function_exists('gdy_list_cache_ttl')) {
    function gdy_list_cache_ttl(): int
    {
        $v = getenv('GDY_LIST_CACHE_TTL');
        if ($v === false || $v === '') return 120;
        return max(0, (int)$v);
    }
}

if (!function_exists('gdy_cache_key')) {
    function gdy_cache_key(string $prefix, array $parts): string
    {
        $raw = $prefix . '|' . json_encode($parts, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return $prefix . ':' . hash('sha256', (string)$raw);
    }
}

if (!function_exists('gdy_cache_remember')) {
    function gdy_cache_remember(string $key, int $ttlSeconds, callable $fn)
    {
        if ($ttlSeconds <= 0 || gdy_should_bypass_list_cache()) {
            return $fn();
        }
        if (class_exists('Cache') && method_exists('Cache', 'remember')) {
            return Cache::remember($key, $ttlSeconds, $fn);
        }
        return $fn();
    }
}

if (function_exists('gdy_output_cache_ttl') === false) {
    function gdy_output_cache_ttl(): int {
        $v = getenv('GDY_OUTPUT_CACHE_TTL');
        if ($v === false || $v === '') {
            return 60;
        }
        $n = (int)$v;
        return ($n < 0) ? 0 : $n;
    }
}

if (function_exists('gdy_should_bypass_output_cache') === false) {
    function gdy_should_bypass_output_cache(): bool {
        if (isset($_GET['nocache']) && (string)$_GET['nocache'] === '1') {
            return true;
        }
        return false;
    }
}

if (function_exists('gdy_should_output_cache') === false) {
    function gdy_should_output_cache(): bool {
        if (gdy_output_cache_ttl() <= 0) {
            return false;
        }
        if (gdy_should_bypass_output_cache()) {
            return false;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            return false;
        }
        $uri = (string)($_SERVER['REQUEST_URI'] ?? '');
        if ($uri !== '' && strpos($uri, '/admin') !== false) {
            return false;
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (!empty($_SESSION['user_id']) || !empty($_SESSION['admin'])) {
                return false;
            }
        }
        foreach ($_COOKIE as $k => $v) {
            $lk = strtolower((string)$k);
            if (strpos($lk, 'sess') !== false || strpos($lk, 'auth') !== false || strpos($lk, 'admin') !== false) {
                return false;
            }
        }
        return true;
    }
}

if (function_exists('gdy_page_cache_key') === false) {
    function gdy_page_cache_key(string $prefix, array $parts = []): string {
        $host = (string)($_SERVER['HTTP_HOST'] ?? 'host');
        $base = $prefix . '|' . $host . '|' . implode('|', array_map('strval', $parts));
        return substr(hash('sha256', $base), 0, 24);
    }
}

if (function_exists('gdy_output_cache_begin') === false) {
    function gdy_output_cache_begin(string $namespace, array $keyParams = []): array {
        $ttl = (function_exists('gdy_output_cache_ttl') === true) ? (int)gdy_output_cache_ttl() : 0;
        if ($ttl > 0
            && (function_exists('gdy_should_output_cache') === true)
            && (gdy_should_output_cache() === true)
            && (class_exists('PageCache') === true)
        ) {
            $key = $namespace . '_' . gdy_page_cache_key($namespace, $keyParams);
            if (PageCache::serveIfCached($key) === true) {
                return ['served' => true, 'did' => false, 'key' => '', 'ttl' => 0];
            }
            ob_start();
            return ['served' => false, 'did' => true, 'key' => $key, 'ttl' => $ttl];
        }
        return ['served' => false, 'did' => false, 'key' => '', 'ttl' => 0];
    }
}

if (function_exists('gdy_output_cache_end') === false) {
    function gdy_output_cache_end(array $ctx): void {
        if (
            (isset($ctx['did']) && ($ctx['did'] === true))
            && (isset($ctx['key']) && is_string($ctx['key']) && ($ctx['key'] !== ''))
            && (isset($ctx['ttl']) && (int)$ctx['ttl'] > 0)
            && (class_exists('PageCache') === true)
        ) {
            PageCache::store((string)$ctx['key'], (int)$ctx['ttl']);
            @ob_end_flush();
        }
    }
}

if (function_exists('gdy_unslash') !== true) {
    function gdy_unslash($value) {
        if (is_array($value) === true) {
            $out = [];
            foreach ($value as $k => $v) {
                $out[$k] = gdy_unslash($v);
            }
            return $out;
        }
        if (is_string($value) === true) {
            return stripslashes($value);
        }
        return $value;
    }
}

if (function_exists('gdy_get_query_raw') !== true) {
    function gdy_get_query_raw(string $key, $default = '') {
        $v = filter_input(INPUT_GET, $key, FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
        if ($v === null) {
            return $default;
        }
        return gdy_unslash($v);
    }
}

if (function_exists('gdy_get_query_int') !== true) {
    function gdy_get_query_int(string $key, int $default = 0): int {
        $v = filter_input(INPUT_GET, $key, FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
        if ($v === null) {
            return $default;
        }
        $v = gdy_unslash($v);
        if (is_string($v) === true && $v !== '') {
            return (int)$v;
        }
        return $default;
    }
}

if (function_exists('gdy_get_server_raw') !== true) {
    function gdy_get_server_raw(string $key, $default = '') {
        $v = filter_input(INPUT_SERVER, $key, FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
        if ($v === null) {
            return $default;
        }
        return gdy_unslash($v);
    }
}

if (function_exists('gdy_request_path') !== true) {
    function gdy_request_path(): string {
        $uri = (string)gdy_get_server_raw('REQUEST_URI', '/');
        $qpos = strpos($uri, '?');
        $path = ($qpos !== false) ? substr($uri, 0, $qpos) : $uri;
        if ($path === '') {
            $path = '/';
        }
        if ($path[0] !== '/') {
            $path = '/' . $path;
        }
        if (strlen($path) > 2048) {
            $path = substr($path, 0, 2048);
        }
        return $path;
    }
}

if (function_exists('gdy_sanitize_slug') !== true) {
    function gdy_sanitize_slug(string $slug): string {
        $slug = trim($slug);
        $slug = preg_replace('/[^a-zA-Z0-9_\-\/]/', '', $slug) ?? '';
        if (strlen($slug) > 128) {
            $slug = substr($slug, 0, 128);
        }
        return $slug;
    }
}

if (!function_exists('gdy_pdo_safe')) {
    function gdy_pdo_safe(): ?\PDO {
        try {
            if (class_exists('Godyar\\DB') && method_exists('Godyar\\DB', 'pdoOrNull')) {
                return \Godyar\DB::pdoOrNull();
            }
            if (class_exists('Godyar\\DB') && method_exists('Godyar\\DB', 'pdo')) {
                return \Godyar\DB::pdo();
            }
        } catch (\Throwable $e) {
            error_log('[Godyar DB] pdo_safe: ' . $e->getMessage());
        }
        return $GLOBALS['pdo'] ?? null;
    }
}

if (!function_exists('gdy_db_stmt_table_exists')) {
function gdy_db_stmt_table_exists(?\PDO $pdo, string $table): bool {
    if (!$pdo) return false;
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    if ($table === '') return false;
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        return (bool)$stmt->fetchColumn();
    } catch (\Throwable $e) {
        return false;
    }
}
}
