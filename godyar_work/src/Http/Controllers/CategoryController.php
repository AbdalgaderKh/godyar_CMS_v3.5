<?php
namespace App\Http\Controllers;

use Godyar\Services\CategoryService;

final class CategoryController
{
    private CategoryService $categories;
    private string $basePrefix;

    public function __construct(CategoryService $categories, string $basePrefix = '')
    {
        $this->categories = $categories;
        $this->basePrefix = rtrim($basePrefix, '/');
    }

    
    public function show(string $slug, int $page = 1, string $sort = 'latest', string $period = 'all'): void
    {
        $slug = trim((string)$slug);
        $slug = trim($slug, "/ " . "\t\n\r\0\x0B");
        
        if ($slug === '' || strpos($slug, '/') !== false) {
            $this->renderMessage(404, 'القسم غير موجود', 'لم يتم تحديد اسم القسم في الرابط.');
        }

        
        if (function_exists('gdy_session_start')) {
            @gdy_session_start();
        } elseif (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            @session_start();
        }

        
        $usePageCache = false;
        $pageCacheKey = 'cat:' .hash('sha256', $slug . '|' . $page . '|' . $sort . '|' . $period);
        if ($usePageCache) {
            try {
                $cached = \Cache::get($pageCacheKey);
                if (is_string($cached) && $cached !== '') {
                    header('X-Godyar-Cache: HIT');
                    echo $cached;
                    exit;
                }
            } catch (\Throwable $e) {
                
            }
        }

        $category = $this->categories->findBySlug($slug);
        if (!$category) {
            $this->renderMessage(404, 'القسم غير موجود', 'لم يتم العثور على القسم المطلوب.');
        }

        $categoryId = (int)($category['id'] ?? 0);
        if ($categoryId <= 0) {
            $this->renderMessage(404, 'القسم غير موجود', 'القسم غير صالح.');
        }

        $page = max(1, (int)$page);
        $perPage = 12;

        $sort = strtolower(trim((string)$sort));
        if (!in_array($sort, ['latest','popular','views'], true)) {
            $sort = 'latest';
        }

        $period = strtolower(trim((string)$period));
        if (!in_array($period, ['all','day','week','month'], true)) {
            $period = 'all';
        }

		
		if (method_exists($this->categories, 'listPublishedNews')) {
		    $result = $this->categories->listPublishedNews($categoryId, $page, $perPage, $sort, $period);
		} else {
		    $result = $this->categories->listNews($categoryId, $page, $perPage, $sort, $period);
		}
        $rows = (array)($result['items'] ?? []);

        $baseUrl = $this->baseUrl();
        $lang = function_exists('gdy_request_lang') ? gdy_request_lang('ar') : 'ar';
        $langPrefix = '/' . $lang;
        $navBaseUrl = function_exists('gdy_front_url') ? rtrim(gdy_front_url('', $lang), '/') : (rtrim($baseUrl, '/') . $langPrefix);
        $items = [];

        foreach ($rows as $row) {
            if (!is_array($row)) continue;

            $id = (int)($row['id'] ?? 0);
            $newsSlug = (string)($row['slug'] ?? '');

            
            $url = function_exists('gdy_route_news_url')
                ? gdy_route_news_url(['id' => $id, 'slug' => $newsSlug], ltrim($langPrefix, '/') ?: 'ar')
                : ($id > 0 ? ($navBaseUrl . '/news/id/' . $id) : ($navBaseUrl . '/news/' . rawurlencode($newsSlug)));

            $catMembersOnly = (int)($category['is_members_only'] ?? 0) === 1;
            $rowMembersOnly = (int)($row['is_members_only'] ?? 0) === 1;
            $isLocked = $catMembersOnly || $rowMembersOnly;

            $items[] = array_merge($row, [
                'url' => $url,
                'is_locked' => $isLocked ? 1 : 0,
            ]);
        }

        $subcategories = $this->categories->subcategories($categoryId, 10);
        $parentId = isset($category['parent_id']) ? (int)$category['parent_id'] : null;
        if ($parentId === 0) {
            $parentId = null;
        }
        $siblingCategories = $this->categories->siblingCategories($parentId, $categoryId, 8);

        $categoryName = (string)($category['name'] ?? '');
        $categoryDescription = (string)($category['description'] ?? '');

        $currentCategoryUrl = function_exists('gdy_route_category_url') ? gdy_route_category_url(['id'=>$categoryId,'slug'=>$slug], ltrim($langPrefix, '/') ?: 'ar') : (rtrim($navBaseUrl, '/') . '/category/' . rawurlencode($slug));
        $canonicalUrl = $currentCategoryUrl . $this->canonicalQuery($page, $sort, $period);

        $categories = [];
        if (method_exists($this->categories, 'headerCategories')) {
            $categories = (array)$this->categories->headerCategories(20);
        } elseif (method_exists($this->categories, 'all')) {
            $categories = (array)$this->categories->all(20);
        }

        $viewData = [
            'category' => $category,
            'items' => $items,
            'news' => $items,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'siblingCategories' => $siblingCategories,
            'totalItems' => (int)($result['total'] ?? 0),
            'itemsPerPage' => $perPage,
            'currentPage' => $page,
            'pages' => (int)($result['total_pages'] ?? 1),
            'baseUrl' => $baseUrl,
            'rootUrl' => $baseUrl,
            'navBaseUrl' => $navBaseUrl,
            'pageLang' => $lang,
            'homeUrl' => $navBaseUrl . '/',
            'currentCategoryUrl' => $currentCategoryUrl,
            'breadcrumbs' => [
                ['label' => function_exists('__') ? __('nav.home', 'الرئيسية') : 'الرئيسية', 'url' => $navBaseUrl . '/'],
                ['label' => $categoryName ?: $slug, 'url' => $currentCategoryUrl],
            ],
            'pageTitle' => $category['meta_title'] ?? (($categoryName ?: $slug) . ' - أخبار'),
            'meta_title' => $category['meta_title'] ?? (($categoryName ?: $slug) . ' - أخبار'),
            'metaDescription' => $category['meta_description']
 ?? ($categoryDescription !== '' ? $categoryDescription : 'أحدث الأخبار في قسم ' . ($categoryName ?: $slug)),
            'meta_description' => $category['meta_description']
 ?? ($categoryDescription !== '' ? $categoryDescription : 'أحدث الأخبار في قسم ' . ($categoryName ?: $slug)),
            'canonicalUrl' => $canonicalUrl,
            'canonical_url' => $canonicalUrl,
            'rss' => $baseUrl . '/rss/category/' . rawurlencode((string)($category['slug'] ?? $slug)) . '.xml',
        ];

        $root = dirname(__DIR__, 3);
        $modernView = $root . '/frontend/views/category_modern.php';
        $legacyView = $root . '/frontend/views/category.php';
        if (is_file($modernView) || is_file($legacyView)) {
            $renderer = new \App\Core\FrontendRenderer($root, '');
            $renderer->render(is_file($modernView) ? 'frontend/views/category_modern.php' : 'frontend/views/category.php', $viewData);
        }

        $this->renderMessage(500, 'خطأ', 'ملف العرض غير موجود.');
    }

    private function canonicalQuery(int $page, string $sort, string $period): string
    {
        $q = [];
        if ($page > 1) {
            $q['page'] = $page;
        }
        if ($sort !== 'latest') {
            $q['sort'] = $sort;
        }
        if ($period !== 'all') {
            $q['period'] = $period;
        }
        return $q ? ('?' .http_build_query($q)) : '';
    }

    private function baseUrl(): string
    {
        if (function_exists('base_url')) {
            $b = rtrim((string)base_url(), '/');
            if ($b !== '') {
                return $b;
            }
        }

        if (defined('BASE_URL')) {
            $b = rtrim((string)BASE_URL, '/');
            if ($b !== '') {
                return $b;
            }
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return rtrim($scheme . '://' . $host . $this->basePrefix, '/');
    }

    private function renderMessage(int $code, string $title, string $message): void
    {
        http_response_code($code);
        $cssHref = function_exists('asset_url') ? asset_url('assets/vendor/bootstrap/css/bootstrap.rtl.min.css') : ($this->baseUrl() . '/assets/vendor/bootstrap/css/bootstrap.rtl.min.css');
        $cssHref = htmlspecialchars((string)$cssHref, ENT_QUOTES, 'UTF-8');
        echo '<!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8">
'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>' .htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>'
            . '<link href="' . $cssHref . '" rel="stylesheet">'
            . '</head><body class="bg-dark text-light"><main class="container py-5">'
            . '<div class="alert alert-info rounded-3 shadow-sm bg-opacity-75">'
            . '<h1 class="h4 mb-2">' .htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>'
            . '<p class="mb-0">' .nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</p>'
            . '</div></main></body></html>';
        exit;
    }
}
