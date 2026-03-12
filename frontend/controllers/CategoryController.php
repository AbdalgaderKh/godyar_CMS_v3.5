<?php

$bootstrap = dirname(__DIR__, 2) . '/includes/bootstrap.php';
if (!is_file($bootstrap)) {
    http_response_code(500);
    exit('Bootstrap missing: ' . $bootstrap);
}
require_once $bootstrap;

if (!function_exists('h')) {
    function h($v): string {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

function gdy_get_base_url(): string
{
    if (function_exists('gdy_base_url')) {
        return rtrim((string)gdy_base_url(), '/');
    }
    if (defined('GODYAR_BASE_URL')) {
        return rtrim((string)GODYAR_BASE_URL, '/');
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return rtrim($scheme . '://' . $host, '/');
}

function gdy_render_message_page(string $title, string $message, int $code = 200): void
{
    http_response_code($code);
    echo '<!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8">
';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' .h($title) . '</title></head><body style="font-family:system-ui;max-width:720px;margin:40px auto;padding:0 16px;">';
    echo '<h1>' .h($title) . '</h1><p>' .h($message) . '</p></body></html>';
    exit;
}

function gdy_render_not_found_page(string $title, string $message): void
{
    gdy_render_message_page($title, $message, 404);
}

function gdy_render_error_page(string $title, string $message): void
{
    gdy_render_message_page($title, $message, 500);
}

$pdo = $pdo ?? (function_exists('gdy_pdo_safe') ? gdy_pdo_safe() : null);
if (!($pdo instanceof PDO)) {
    gdy_render_error_page('خطأ', 'تعذر الاتصال بقاعدة البيانات.');
}

$slug = isset($_GET['slug']) ? trim((string)$_GET['slug']) : '';
if ($slug === '') {
    gdy_render_not_found_page('القسم غير موجود', 'لم يتم تحديد اسم القسم في الرابط.');
}
$slug = preg_replace('~[^a-zA-Z0-9\-]~', '', $slug) ?? '';
if ($slug === '') {
    gdy_render_not_found_page('القسم غير موجود', 'صيغة الرابط غير صحيحة.');
}

$perPage = 12;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page-1) * $perPage;

$__didOutputCache = false;
$__pageCacheKey = '';
$__ttl = function_exists('gdy_output_cache_ttl') ? gdy_output_cache_ttl() : 0;

$__ttl = 0;
$__pageCacheKey = '';
$__didOutputCache = false;

try {
    $st = $pdo->prepare("SELECT * FROM categories WHERE slug = :slug AND (is_active = 1 OR is_active IS NULL) LIMIT 1");
    $st->execute([':slug' => $slug]);
    $category = $st->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (\Throwable $e) {
    error_log('[CategoryController] category fetch failed: ' . $e->getMessage());
    $category = null;
}
if (!$category) {
    gdy_render_not_found_page('القسم غير موجود', 'لم يتم العثور على القسم المطلوب.');
}

$totalItems = 0;
$items = [];

$ttl = 0;
$cacheKey = function_exists('gdy_cache_key')
    ? gdy_cache_key('list:cat', [$slug, (int)$category['id'], $page, $perPage, $_SERVER['HTTP_HOST'] ?? ''])
    : ('list:cat:' . $slug . ':' . $page);

try {
    $CAT_SQL_ERR = '';
    $loader = function () use ($pdo, $category, $perPage, $offset, &$CAT_SQL_ERR) {
            $out = ['total' => 0, 'items' => []];

            
            
            
            
            $hasDeletedAt = false;
            $hasIsPublished = false;
            $hasPublishAt = false;
            try {
                $hasDeletedAt   = (bool)$pdo->query("SHOW COLUMNS FROM news LIKE 'deleted_at'")->rowCount();
                $hasIsPublished = (bool)$pdo->query("SHOW COLUMNS FROM news LIKE 'is_published'")->rowCount();
                $hasPublishAt   = (bool)$pdo->query("SHOW COLUMNS FROM news LIKE 'publish_at'")->rowCount();
            } catch (\Throwable $e) {
                
            }

            $where = "category_id = :cid";
            if ($hasDeletedAt) {
                $where .= " AND (deleted_at IS NULL OR deleted_at='' OR deleted_at='0000-00-00 00:00:00')";
            }
            if ($hasIsPublished) {
                $where .= " AND (LOWER(TRIM(status))='published' OR is_published=1)";
            } else {
                $where .= " AND LOWER(TRIM(status))='published'";
            }

            $st = $pdo->prepare("SELECT COUNT(*) FROM news WHERE {$where}");
            $st->execute([':cid' => (int)$category['id']]);
            $out['total'] = (int)$st->fetchColumn();

            $orderBy = $hasPublishAt ? "publish_at DESC" : "id DESC";
            $sql = "SELECT *
                    FROM news
                    WHERE {$where}
                    ORDER BY {$orderBy}
                    LIMIT :lim OFFSET :off";

            $prevEmulate = (bool)$pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

            $st = $pdo->prepare($sql);
            $st->bindValue(':cid', (int)$category['id'], PDO::PARAM_INT);
            $st->bindValue(':lim', (int)$perPage, PDO::PARAM_INT);
            $st->bindValue(':off', (int)$offset, PDO::PARAM_INT);
            try {
                $st->execute();
            } catch (\Throwable $e) {
                $CAT_SQL_ERR = $e->getMessage();
                throw $e;
            }
            $out['items'] = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, $prevEmulate);

            return $out;
        };

    
    if ($ttl > 0 && function_exists('gdy_cache_remember')) {
        $payload = gdy_cache_remember($cacheKey, (int)$ttl, $loader);
    } else {
        $payload = $loader();
    }

    if (is_array($payload)) {
        $totalItems = (int)($payload['total'] ?? 0);
        $items = (array)($payload['items'] ?? []);
    }

        
    
    if (!$items && $totalItems > 0) {
        try {
            $sql2 = "SELECT * FROM news WHERE category_id = :cid";
            $hasDeletedAt = false;
            try { $hasDeletedAt = (bool)$pdo->query("SHOW COLUMNS FROM news LIKE 'deleted_at'")->rowCount(); } catch (\Throwable $e) {}
            if ($hasDeletedAt) {
                $sql2 .= " AND (deleted_at IS NULL OR deleted_at='' OR deleted_at='0000-00-00 00:00:00')";
            }
            $sql2 .= " ORDER BY id DESC LIMIT :lim OFFSET :off";
            $prevEmulate = (bool)$pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
            $st2 = $pdo->prepare($sql2);
            $st2->bindValue(':cid', (int)$category['id'], PDO::PARAM_INT);
            $st2->bindValue(':lim', (int)$perPage, PDO::PARAM_INT);
            $st2->bindValue(':off', (int)$offset, PDO::PARAM_INT);
            $st2->execute();
            $items = $st2->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, $prevEmulate);
        } catch (\Throwable $e) {
            error_log('[CategoryController] fallback fetch failed: ' . $e->getMessage());
        }
    }

    
    if (!$items) {
        try {
            $dbName = (string)$pdo->query("SELECT DATABASE()")->fetchColumn();
            $cntAll = (int)$pdo->query("SELECT COUNT(*) FROM news WHERE category_id=".(int)$category['id'])->fetchColumn();
            $cntPub = 0;
            try {
                $cntPub = (int)$pdo->query("SELECT COUNT(*) FROM news WHERE category_id=".(int)$category['id']." AND LOWER(TRIM(status))='published'")->fetchColumn();
            } catch (\Throwable $e) {}
            $GLOBALS['__gdy_category_debug'] = "db={$dbName}; cat_id=".(int)$category['id']."; cnt_all={$cntAll}; cnt_published={$cntPub}; total_calc={$totalItems}; items=".count($items)."; langcol=" . ((int)$pdo->query("SHOW COLUMNS FROM news LIKE 'lang'")->rowCount()) . "; sqlerr=" . rawurlencode((string)($CAT_SQL_ERR ?? ''));
        } catch (\Throwable $e) {}
    }

    if (function_exists('gdy_attach_comment_counts_to_news_rows') && $pdo instanceof PDO) {
        try { $items = gdy_attach_comment_counts_to_news_rows($pdo, $items); } catch (\Throwable $e) {  }
    }

} catch (\Throwable $e) {
    error_log('[CategoryController] news fetch failed: ' . $e->getMessage());
}
$pages = max(1, (int)ceil($totalItems / $perPage));
$pagination = [
    'total_items' => $totalItems,
    'per_page' => $perPage,
    'current' => $page,
    'pages' => $pages,
];

$baseUrl = gdy_get_base_url();
$newsItems = $items;

$news = $items;
$currentPage = $page;

$view = dirname(__DIR__) . '/views/category.php';
if (!is_file($view)) {
    gdy_render_error_page('خطأ', 'ملف العرض غير موجود: ' . $view);
}

try {
    $dbName = '';
    try { $dbName = (string)$pdo->query("SELECT DATABASE()")->fetchColumn(); } catch (\Throwable $e) {}

    $cntAll = 0;
    $cntPub = 0;
    try { $cntAll = (int)$pdo->query("SELECT COUNT(*) FROM news WHERE category_id=".(int)$category['id'])->fetchColumn(); } catch (\Throwable $e) {}
    try { $cntPub = (int)$pdo->query("SELECT COUNT(*) FROM news WHERE category_id=".(int)$category['id']." AND LOWER(TRIM(status))='published'")->fetchColumn(); } catch (\Throwable $e) {}

    $langCol = 0;
    try { $langCol = (int)$pdo->query("SHOW COLUMNS FROM news LIKE 'lang'")->rowCount(); } catch (\Throwable $e) {}

    
    $GLOBALS['__gdy_category_debug'] =
        'build=v12' .
        '; slug=' . $slug .
        '; db=' . $dbName .
        '; cat_id=' . (int)$category['id'] .
        '; cnt_all=' . $cntAll .
        '; cnt_published=' . $cntPub .
        '; total_calc=' . (int)$totalItems .
        '; items=' . (int)count($items) .
        '; langcol=' . $langCol .
        '; sqlerr=' . rawurlencode((string)($CAT_SQL_ERR ?? ''));
} catch (\Throwable $e) {
    
}

if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

if (!empty($GLOBALS['__gdy_category_debug'])) {
    echo "\n<!-- __gdy_category_debug {$GLOBALS['__gdy_category_debug']} -->\n";
}

require $view;

if ($__didOutputCache && $__pageCacheKey !== '') {
    PageCache::store($__pageCacheKey, $__ttl);
    @ob_end_flush();
}
