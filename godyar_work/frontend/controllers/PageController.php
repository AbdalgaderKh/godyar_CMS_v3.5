<?php

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/TemplateEngine.php';
require_once __DIR__ . '/../../includes/site_settings.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    gdy_session_start();
}

if (!function_exists('h')) {
    function h($v): string {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

$pdo = gdy_pdo_safe();

if (!function_exists('gdy_has_column')) {
    function gdy_has_column(PDO $pdo, string $table, string $column): bool {
        static $cache = [];
        $key = $table . '.' . $column;
        if (array_key_exists($key, $cache)) {
            return (bool)$cache[$key];
        }
        try {
            $db = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
            if ($db === '') {
                return $cache[$key] = false;
            }
            $stmt = $pdo->prepare('SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1');
            $stmt->execute([$db, $table, $column]);
            return $cache[$key] = (bool)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return $cache[$key] = false;
        }
    }
}
if (($pdo instanceof PDO) === false) {
    http_response_code(500);
    echo 'تعذّر الاتصال بقاعدة البيانات.';
    exit;
}

$settings = gdy_load_settings($pdo);
$frontendOptions = gdy_prepare_frontend_options($settings);

extract($frontendOptions, EXTR_OVERWRITE);

$isLoggedIn = !empty($_SESSION['user']) && !empty($_SESSION['user']['role']) && $_SESSION['user']['role'] !== 'guest';
$isAdmin = $isLoggedIn && ($_SESSION['user']['role'] === 'admin');

$headerCategories = [];
try {
    
    if (gdy_has_column($pdo, 'categories', 'sort_order')) {
        $stmt = $pdo->query("SELECT id, name, slug FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, id ASC LIMIT 8");
    } elseif (gdy_has_column($pdo, 'categories', 'name')) {
        $stmt = $pdo->query("SELECT id, name, slug FROM categories WHERE is_active = 1 ORDER BY name ASC, id ASC LIMIT 8");
    } else {
        $stmt = $pdo->query("SELECT id, name, slug FROM categories WHERE is_active = 1 ORDER BY id ASC LIMIT 8");
    }
    $headerCategories = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (\Throwable $e) {
    error_log('[PageController] categories load error: ' . $e->getMessage());
    $headerCategories = [];
}

$slug = trim((string)($_GET['slug'] ?? ''));

if ($slug === '') {
    
    $pageNotFound = true;
    $page = [
        'title' => 'الصفحة غير موجودة',
        'content' => '<p>عذراً، الصفحة التي تحاول الوصول إليها غير متوفرة.</p>',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => null,
    ];
} else {
    $pageNotFound = false;
    $page = null;

    try {
        $sql = "SELECT id, title, slug, content, status, created_at, updated_at
                FROM pages
                WHERE slug = :slug AND status = 'published'
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':slug' => $slug]);
        $page = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        
        if (!$page && in_array($slug, ['about', 'privacy', 'terms', 'contact'], true)) {
            $staticDir = realpath(__DIR__ . '/../views/page/static');
            $staticView = __DIR__ . '/../views/page/static/' . $slug . '.php';
            $staticViewReal = realpath($staticView);
            if ($staticDir && $staticViewReal && strpos($staticViewReal, $staticDir .DIRECTORY_SEPARATOR) === 0 && is_file($staticViewReal)) {
                ob_start();
                include $staticViewReal;
                $html = (string)ob_get_clean();

                $pageNotFound = false;
                $titles = ['about' => 'من نحن', 'privacy' => 'سياسة الخصوصية', 'terms' => 'الشروط والأحكام', 'contact' => 'اتصل بنا'];
                $page = [
                    'slug' => $slug,
                    'title' => $titles[$slug] ?? 'صفحة',
                    'content' => $slug === 'contact' ? '' : $html,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => null,
                ];
            }
        }

        if (!$page) {
            $pageNotFound = true;
            $page = [
                'title' => 'الصفحة غير موجودة',
                'content' => '<p>عذراً، الصفحة التي تحاول الوصول إليها غير متوفرة أو غير منشورة.</p>',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => null,
            ];
            http_response_code(404);
        }
    } catch (\Throwable $e) {
        error_log('[PageController] page load error: ' . $e->getMessage());
        $pageNotFound = true;
        $page = [
            'title' => 'خطأ في تحميل الصفحة',
            'content' => '<p>حدث خطأ أثناء محاولة تحميل الصفحة. الرجاء المحاولة لاحقاً.</p>',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => null,
        ];
        http_response_code(500);
    }
}

$baseUrl = base_url(); 

$template = new TemplateEngine();

$templateData = [
    
    'siteName' => $siteName ?? 'Godyar News',
    'siteTagline' => $siteTagline ?? 'منصة إخبارية متكاملة',
    'siteLogo' => $siteLogo ?? '',
    'primaryColor' => $primaryColor ?? '#0ea5e9',
    'primaryDark' => $primaryDark ?? '#0369a1',
    'baseUrl' => $baseUrl,
    'themeClass' => $themeClass ?? 'theme-default',

    
    'searchPlaceholder' => $searchPlaceholder ?? __('ابحث عن خبر أو موضوع...'),
    'homeLatestTitle' => $homeLatestTitle ?? '',
    'homeFeaturedTitle' => $homeFeaturedTitle ?? '',
    'homeTabsTitle' => $homeTabsTitle ?? '',
    'homeMostReadTitle' => $homeMostReadTitle ?? '',
    'homeMostCommentedTitle' => $homeMostCommentedTitle ?? '',
    'homeRecommendedTitle' => $homeRecommendedTitle ?? '',
    'carbonBadgeText' => $carbonBadgeText ?? '',

    
    'isLoggedIn' => $isLoggedIn,
    'isAdmin' => $isAdmin,

    
    'headerCategories' => $headerCategories,

    
    'enableMostRead' => $enableMostRead ?? false,
    'enableMostCommented' => $enableMostCommented ?? false,
    'showCarbonBadge' => $showCarbonBadge ?? false,

    
    'page' => $page,
    'pageNotFound' => $pageNotFound,
];

$template->render(__DIR__ . '/../views/page/content.php', $templateData);
