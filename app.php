<?php

use App\Core\Router;
use App\Core\FrontendRenderer;
use App\Http\Presenters\SeoPresenter;
use App\Http\Controllers\RedirectController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\LegacyIncludeController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\Api\NewsExtrasController;

$__reqUri  = (string)($_SERVER['REQUEST_URI'] ?? '/');
$__reqPath = (string)(parse_url($__reqUri, PHP_URL_PATH) ?: '/');
if ($__reqPath === '') $__reqPath = '/';

if (preg_match('~^/(ar|en|fr)$~i', $__reqPath, $__m)) {
    header('Location: /' . strtolower((string)$__m[1]) . '/', true, 301);
    exit;
}

$__auto = getenv('GDY_AUTO_LANG_REDIRECT');
if ($__auto === false || $__auto === '' || $__auto === '1' || strtolower((string)$__auto) === 'true') {
    if ($__reqPath === '/' && empty($_GET['lang'])) {
        $supported = ['ar', 'en', 'fr'];
        $pick = '';
        $cookieLang = strtolower(trim((string)($_COOKIE['gdy_lang'] ?? '')));
        if ($cookieLang !== '' && in_array($cookieLang, $supported, true)) {
            $pick = $cookieLang;
        } else {
            $al = strtolower((string)($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
            foreach ($supported as $L) {
                if (preg_match('~\b' . preg_quote($L, '~') . '\b~', $al)) { $pick = $L; break; }
            }
        }
        if ($pick === '') $pick = 'ar';
        header('Location: /' . $pick . '/', true, 302);
        exit;
    }
}

$lpFile = __DIR__ . '/language_prefix_router.php';
if (is_file($lpFile)) {
    require_once $lpFile;
}

require_once __DIR__ . '/includes/bootstrap.php';

$__uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
if (preg_match('~^/install(?:/|$)~', $__uri)) {
    require_once __DIR__ . '/install/index.php';
    exit;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    gdy_session_start();
}

if (!function_exists('gdy_qs_int')) {
    function gdy_qs_int(string $key, int $default = 0): int {
        $v = $_GET[$key] ?? null;
        if ($v === null) return $default;
        $s = is_scalar($v) ? (string)$v : '';
        return ctype_digit($s) ? (int)$s : $default;
    }
}
if (!function_exists('gdy_qs_str')) {
    function gdy_qs_str(string $key, string $default = ''): string {
        $v = $_GET[$key] ?? null;
        return $v === null ? $default : (is_scalar($v) ? trim((string)$v) : $default);
    }
}
if (!function_exists('gdy_allowlist')) {
    function gdy_allowlist(string $value, array $allowed, string $default): string {
        return in_array($value, $allowed, true) ? $value : $default;
    }
}
if (!function_exists('gdy_slug_clean')) {
    function gdy_slug_clean(string $slug, int $maxLen = 190): string {
        $slug = trim($slug);
        if ($slug === '') return '';
        $slug = preg_replace('/[^\pL\pN\-_]/u', '', $slug) ?? '';
        if (strlen($slug) > $maxLen) $slug = substr($slug, 0, $maxLen);
        return $slug;
    }
}
if (!function_exists('godyar_route_base_prefix')) {
    function godyar_route_base_prefix(): string {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir = str_replace('\\', '/', dirname((string)$script));
        if ($dir === '/' || $dir === '.' || $dir === '\\') return '';
        return rtrim($dir, '/');
    }
}
if (!function_exists('godyar_request_path')) {
    function godyar_request_path(): string {
        $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') $path = '/';
        return $path;
    }
}
if (!function_exists('godyar_render_404')) {
    function godyar_render_404(): void {
        http_response_code(404);
        $GLOBALS['siteTitle'] = '404-الصفحة غير موجودة';
        $GLOBALS['siteDescription'] = 'الصفحة التي طلبتها غير موجودة.';
        $headerFile = __DIR__ . '/frontend/templates/header.php';
        if (is_file($headerFile)) require $headerFile;
        echo '<main class="container my-5"><h1 style="margin-bottom:12px;">الصفحة غير موجودة (404)</h1><p>قد يكون الرابط غير صحيح أو تم نقل الصفحة.</p></main>';
        $footerFile = __DIR__ . '/frontend/templates/footer.php';
        if (is_file($footerFile)) require $footerFile;
        exit;
    }
}

$basePrefix = godyar_route_base_prefix();
$requestPath = godyar_request_path();
if ($basePrefix !== '' && str_starts_with($requestPath, $basePrefix . '/')) {
    $requestPath = substr($requestPath, strlen($basePrefix));
}
$requestPath = '/' . ltrim($requestPath, '/');

if (preg_match('#^/(ar|en|fr)(?:/|$)#i', $requestPath, $__m)) {
    $lp = strtolower((string)$__m[1]);
    if (empty($_GET['lang'])) $_GET['lang'] = $lp;
    $requestPath = substr($requestPath, 1 + strlen($lp));
    if ($requestPath === '') $requestPath = '/';
    if ($requestPath[0] !== '/') $requestPath = '/' . $requestPath;
}

if ($requestPath === '/' || $requestPath === '') {
    require __DIR__ . '/frontend/index.php';
    exit;
}

if ($requestPath === '/ad-click.php' || $requestPath === '/ad-click') {
    $qs = (string)($_SERVER['QUERY_STRING'] ?? '');
    header('Location: /ad_click.php' . ($qs !== '' ? ('?' . $qs) : ''), true, 301);
    exit;
}

if ($requestPath === '/oauth/github') { $f = __DIR__ . '/oauth/github.php'; if (is_file($f)) require $f; else { http_response_code(404); echo 'OAuth provider not installed'; } exit; }
if ($requestPath === '/oauth/github/callback') { $f = __DIR__ . '/oauth/github_callback.php'; if (is_file($f)) require $f; else { http_response_code(404); echo 'OAuth provider not installed'; } exit; }
if ($requestPath === '/oauth/google') { require __DIR__ . '/oauth/google.php'; exit; }
if ($requestPath === '/oauth/google/callback') { require __DIR__ . '/oauth/google_callback.php'; exit; }
if ($requestPath === '/oauth/facebook') { require __DIR__ . '/oauth/facebook.php'; exit; }
if ($requestPath === '/oauth/facebook/callback') { require __DIR__ . '/oauth/facebook_callback.php'; exit; }

$container = $GLOBALS['container'] ?? null;
if (($container instanceof \Godyar\Container) === false) {
    $pdoBoot = \Godyar\DB::pdoOrNull();
    if (!$pdoBoot instanceof \PDO) {
        http_response_code(503);
        echo 'Database not configured';
        exit;
    }
    $container = new \Godyar\Container($pdoBoot);
    $GLOBALS['container'] = $container;
}

$pdo = null;
if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
    $pdo = $GLOBALS['pdo'];
} elseif (function_exists('gdy_pdo_safe')) {
    $tmp = gdy_pdo_safe();
    if ($tmp instanceof PDO) $pdo = $tmp;
}

$redirectController = new RedirectController($container->news(), $container->categories(), godyar_route_base_prefix());
$categoryController = new CategoryController($container->categories(), godyar_route_base_prefix());
$newsController = new NewsController($container->pdo(), $container->news(), $container->categories(), $container->tags(), $container->ads(), godyar_route_base_prefix());
$basePrefix = godyar_route_base_prefix();
$renderer = new FrontendRenderer(__DIR__, $basePrefix);
$seo = new SeoPresenter($basePrefix);
$tagController = new TagController($container->tags(), $renderer, $seo);
$archiveController = new ArchiveController($container->news(), $renderer, $seo);
$searchController = new SearchController($container->news(), $container->categories(), $seo, __DIR__, $basePrefix);
$topicController = new TopicController($container->tags(), $renderer, $seo, $container->pdo());
$extrasApi = new NewsExtrasController($container->pdo(), $container->news(), $container->tags(), $container->categories());
$legacy = new LegacyIncludeController(__DIR__);
$router = new Router();

$router->get('#^/login/?$#', fn() => require __DIR__ . '/frontend/controllers/Auth/LoginController.php');
$router->post('#^/login/?$#', fn() => require __DIR__ . '/frontend/controllers/Auth/LoginController.php');
$router->get('#^/register/?$#', fn() => require __DIR__ . '/frontend/controllers/Auth/RegisterController.php');
$router->post('#^/register/?$#', fn() => require __DIR__ . '/frontend/controllers/Auth/RegisterController.php');
$router->get('#^/profile/?$#', fn() => require __DIR__ . '/profile.php');
$router->post('#^/profile/?$#', fn() => require __DIR__ . '/profile.php');
$router->get('#^/logout/?$#', fn() => require __DIR__ . '/logout.php');
$router->post('#^/logout/?$#', fn() => require __DIR__ . '/logout.php');
$router->get('#^/admin/login/?$#', fn() => require __DIR__ . '/admin/login.php');
$router->post('#^/admin/login/?$#', fn() => require __DIR__ . '/admin/login.php');

$router->get('#^/sitemap\.xml$#', function (): void { require __DIR__ . '/seo/sitemap.php'; });
$router->get('#^/sitemap-news\.xml$#', function (): void { require __DIR__ . '/seo/sitemap_news.php'; });
$router->get('#^/rss\.xml$#', function (): void { require __DIR__ . '/seo/rss.php'; });
$router->get('#^/rss/category/([^/]+)\.xml$#', function (array $m): void { $slug = rawurldecode((string)$m[1]); require __DIR__ . '/seo/rss_category.php'; });
$router->get('#^/rss/tag/([^/]+)\.xml$#', function (array $m): void { $slug = rawurldecode((string)$m[1]); require __DIR__ . '/seo/rss_tag.php'; });
$router->get('#^/og/news/([0-9]+)\.png$#', function (array $m): void { $id = (int)$m[1]; require __DIR__ . '/og_news.php'; });

$router->get('#^/category/([^/]+)/page/([0-9]+)/?$#', function (array $m) use ($categoryController): void {
    $sort = gdy_allowlist(gdy_qs_str('sort', 'latest'), ['latest','oldest','popular'], 'latest');
    $period = gdy_allowlist(gdy_qs_str('period', 'all'), ['all','day','week','month','year'], 'all');
    $categoryController->show(rawurldecode((string)$m[1]), (int)$m[2], $sort, $period);
});
$router->get('#^/category/([^/]+)/?$#', function (array $m) use ($categoryController): void {
    $sort = gdy_allowlist(gdy_qs_str('sort', 'latest'), ['latest','oldest','popular'], 'latest');
    $period = gdy_allowlist(gdy_qs_str('period', 'all'), ['all','day','week','month','year'], 'all');
    $categoryController->show(rawurldecode((string)$m[1]), 1, $sort, $period);
});

$router->get('#^/news/print/([0-9]+)/?$#', function (array $m) use ($newsController): void { $newsController->print((int)$m[1]); });
$router->get('#^/news/pdf/([0-9]+)/?$#', function (array $m) use ($newsController): void { $newsController->print((int)$m[1]); });
$router->get('#^/news/id/([0-9]+)/?$#', function (array $m) use ($newsController): void { $newsController->show((string)((int)$m[1]), false); });
$router->get('#^/category/id/([0-9]+)/?$#', fn(array $m) => $redirectController->categoryIdToSlug((int)$m[1]));
$router->get('#^/preview/news/([0-9]+)/?$#', fn(array $m) => $newsController->preview((int)$m[1]));
$router->get('#^/(?:news|article)/([^/]+)/?$#', function (array $m) use ($container, $newsController): void {
    $slug = rawurldecode((string)$m[1]);
    $id = $container->news()->idBySlug($slug);
    if ($id !== null && $id > 0) {
        $prefix = rtrim(godyar_route_base_prefix(), '/');
        header('Location: ' . $prefix . '/news/id/' . $id, true, 301);
        exit;
    }
    $newsController->show($slug, false);
});

$router->get('#^/page/about/?$#', function (): void { require __DIR__ . '/page/about/index.php'; exit; });
$router->get('#^/page/privacy/?$#', function (): void { require __DIR__ . '/page/privacy/index.php'; exit; });
$router->get('#^/page/terms/?$#', function (): void { require __DIR__ . '/page/terms/index.php'; exit; });
$router->get('#^/page/contact/?$#', function (): void { require __DIR__ . '/page/contact/index.php'; exit; });
$router->get('#^/contact/?$#', function (): void { require __DIR__ . '/contact/index.php'; exit; });
$router->post('#^/contact/?$#', function (): void {
    if (is_file(__DIR__ . '/contact/send.php')) { require __DIR__ . '/contact/send.php'; exit; }
    require __DIR__ . '/contact/index.php'; exit;
});
$router->get('#^/contact\.php$#', function (): void { require __DIR__ . '/contact/index.php'; exit; });
$router->post('#^/contact\.php$#', function (): void {
    if (is_file(__DIR__ . '/contact/send.php')) { require __DIR__ . '/contact/send.php'; exit; }
    require __DIR__ . '/contact/index.php'; exit;
});
$router->get('#^/contact-submit\.php$#', function (): void {
    if (is_file(__DIR__ . '/contact/send.php')) { require __DIR__ . '/contact/send.php'; exit; }
    require __DIR__ . '/contact/index.php'; exit;
});
$router->post('#^/contact-submit\.php$#', function (): void {
    if (is_file(__DIR__ . '/contact/send.php')) { require __DIR__ . '/contact/send.php'; exit; }
    require __DIR__ . '/contact/index.php'; exit;
});

$router->get('#^/page/([^/]+)/?$#', fn(array $m) => $legacy->include('frontend/controllers/PageController.php', [
    'slug' => rawurldecode((string)$m[1]),
]));

$router->get('#^/topic/([^/]+)/page/([0-9]+)/?$#', function (array $m) use ($topicController): void { $topicController->show(urldecode((string)$m[1]), (int)$m[2]); });
$router->get('#^/topic/([^/]+)/?$#', function (array $m) use ($topicController): void { $topicController->show(urldecode((string)$m[1]), 1); });
$router->get('#^/tag/([^/]+)/page/([0-9]+)/?$#', fn(array $m) => $tagController->show(rawurldecode((string)$m[1]), (int)$m[2]));
$router->get('#^/tag/([^/]+)/?$#', fn(array $m) => $tagController->show(gdy_slug_clean(rawurldecode((string)$m[1])), gdy_qs_int('page', 1)));
$router->get('#^/trending/?$#', fn() => $legacy->include('frontend/views/trending.php'));
$router->get('#^/my/?$#', fn() => $legacy->include('my.php'));
$router->get('#^/categories/?$#', fn() => $legacy->include('categories_list.php'));
$router->get('#^/saved/?$#', fn() => $legacy->include('saved.php'));

$router->get('#^/archive/page/([0-9]+)/?$#', fn(array $m) => $archiveController->index((int)$m[1]));
$router->get('#^/archive/([0-9]{4})/page/([0-9]+)/?$#', fn(array $m) => $archiveController->index((int)$m[2], (int)$m[1], null));
$router->get('#^/archive/([0-9]{4})/([0-9]{1,2})/page/([0-9]+)/?$#', fn(array $m) => $archiveController->index((int)$m[3], (int)$m[1], (int)$m[2]));
$router->get('#^/archive/([0-9]{4})/([0-9]{1,2})/?$#', fn(array $m) => $archiveController->index(1, (int)$m[1], (int)$m[2]));
$router->get('#^/archive/([0-9]{4})/?$#', fn(array $m) => $archiveController->index(1, (int)$m[1], null));
$router->get('#^/archive/?$#', fn() => $archiveController->index(gdy_qs_int('page', 1)));
$router->get('#^/news/page/([0-9]+)/?$#', fn(array $m) => $archiveController->index((int)$m[1]));
$router->get('#^/news/([0-9]{4})/page/([0-9]+)/?$#', fn(array $m) => $archiveController->index((int)$m[2], (int)$m[1], null));
$router->get('#^/news/([0-9]{4})/([0-9]{1,2})/page/([0-9]+)/?$#', fn(array $m) => $archiveController->index((int)$m[3], (int)$m[1], (int)$m[2]));
$router->get('#^/news/([0-9]{4})/([0-9]{1,2})/?$#', fn(array $m) => $archiveController->index(1, (int)$m[1], (int)$m[2]));
$router->get('#^/news/([0-9]{4})/?$#', fn(array $m) => $archiveController->index(1, (int)$m[1], null));
$router->get('#^/news/?$#', fn() => $archiveController->index(gdy_qs_int('page', 1)));

$router->get('#^/api/capabilities/?$#', fn() => $extrasApi->capabilities());
$router->get('#^/api/bookmarks/list/?$#', fn() => $extrasApi->bookmarksList());
$router->get('#^/api/bookmarks/status/?$#', fn() => $extrasApi->bookmarkStatus());
$router->get('#^/api/bookmarks/toggle/?$#', fn() => $extrasApi->bookmarksToggle());
$router->get('#^/api/bookmarks/import/?$#', fn() => $extrasApi->bookmarksImport());
$router->get('#^/api/news/reactions/?$#', fn() => $extrasApi->reactions());
$router->get('#^/api/news/react/?$#', fn() => $extrasApi->react());
$router->get('#^/api/news/poll/?$#', fn() => $extrasApi->poll());
$router->get('#^/api/news/poll/vote/?$#', fn() => $extrasApi->pollVote());
$router->get('#^/api/news/tts/?$#', fn() => $extrasApi->tts());
$router->get('#^/api/news/pdf/?$#', fn() => $extrasApi->pdf());
$router->get('#^/api/search/suggest/?$#', fn() => $extrasApi->suggest());
$router->get('#^/api/latest/?$#', fn() => $extrasApi->latest());
$router->post('#^/api/push/subscribe/?$#', fn() => $extrasApi->pushSubscribe());
$router->post('#^/api/push/unsubscribe/?$#', fn() => $extrasApi->pushUnsubscribe());
$router->get('#^/search/?$#', fn() => $searchController->index());
$router->post('#^/comment/add/?$#', fn() => require __DIR__ . '/frontend/controllers/CommentAddController.php');

$router->post('#^/api/newsletter/subscribe/?$#', function () use ($pdo): void {
    if (($pdo instanceof PDO) === false) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => 'PDO غير متاح'], JSON_UNESCAPED_UNICODE);
        return;
    }
    if (!empty($_POST['csrf_token']) && function_exists('csrf_verify_or_die')) { csrf_verify_or_die(); }
    if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
        http_response_code(405);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
        return;
    }
    $email = '';
    if (isset($_POST['newsletter_email']) && is_scalar($_POST['newsletter_email']) && (string)$_POST['newsletter_email'] !== '') {
        $email = trim((string)($_POST['newsletter_email']));
    } else {
        $raw = (string)file_get_contents('php://input');
        if ($raw !== '') {
            $j = json_decode($raw, true);
            if (is_array($j) && !empty($j['newsletter_email'])) $email = trim((string)$j['newsletter_email']);
            elseif (is_array($j) && !empty($j['email'])) $email = trim((string)$j['email']);
        }
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(422);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => 'البريد الإلكتروني غير صحيح'], JSON_UNESCAPED_UNICODE);
        return;
    }
    try {
        if (function_exists('gdy_pdo_is_pgsql') && gdy_pdo_is_pgsql($pdo)) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS newsletter_subscribers (id BIGSERIAL PRIMARY KEY,email VARCHAR(190) NOT NULL UNIQUE,status VARCHAR(30) NOT NULL DEFAULT 'active',lang VARCHAR(10) NOT NULL DEFAULT 'ar',ip VARCHAR(45) NULL,ua VARCHAR(255) NULL,created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)");
        } else {
            $pdo->exec("CREATE TABLE IF NOT EXISTS newsletter_subscribers (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,email VARCHAR(190) NOT NULL UNIQUE,status VARCHAR(30) NOT NULL DEFAULT 'active',lang VARCHAR(10) NOT NULL DEFAULT 'ar',ip VARCHAR(45) NULL,ua VARCHAR(255) NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    } catch (\Throwable $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => 'خطأ في قاعدة البيانات'], JSON_UNESCAPED_UNICODE);
        return;
    }
    $lang = '';
    if (!empty($_COOKIE['lang']) && is_scalar($_COOKIE['lang'])) $lang = gdy_allowlist((string)$_COOKIE['lang'], ['ar','en','fr'], $lang);
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    try {
        $now = date('Y-m-d H:i:s');
        gdy_db_upsert($pdo, 'newsletter_subscribers', ['email'=>$email,'status'=>'active','lang'=>$lang,'ip'=>$ip,'ua'=>$ua,'updated_at'=>$now], ['email'], ['status','lang','ip','ua','updated_at']);
    } catch (\Throwable $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => 'تعذر حفظ الاشتراك'], JSON_UNESCAPED_UNICODE);
        return;
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true, 'message' => 'تم الاشتراك بنجاح ✅'], JSON_UNESCAPED_UNICODE);
});

if ($router->dispatch($requestPath)) {
    exit;
}

godyar_render_404();
