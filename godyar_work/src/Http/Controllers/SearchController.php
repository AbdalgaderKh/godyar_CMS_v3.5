<?php
namespace App\Http\Controllers;

use App\Http\Presenters\SeoPresenter;
use Godyar\Services\CategoryService;
use Godyar\Services\NewsService;

final class SearchController
{
    
    private NewsService $news;
    
    private CategoryService $categories;
    
    private SeoPresenter $seo;
    
    private string $rootDir;
    
    private string $basePrefix;

    public function __construct(NewsService $news, CategoryService $categories, SeoPresenter $seo, string $rootDir, string $basePrefix = '')
    {
        $this->news = $news;
        $this->categories = $categories;
        $this->seo = $seo;
        $this->rootDir = rtrim($rootDir, '/');
        $this->basePrefix = rtrim($basePrefix, '/');
    }

    public function index(): void
    {
        $q = trim((string)($_GET['q'] ?? ''));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 12;

        $engine = (string)($_GET['engine'] ?? 'local');
        if ($engine === 'google' && $q !== '') {
            $host = (string)($_SERVER['HTTP_HOST'] ?? '');
            $site = $host !== '' ? ('site:' . $host) : '';
            $googleQ = trim($site . ' ' . $q);
            header('Location: https://www.google.com/search?q=' .rawurlencode($googleQ), true, 302);
            exit;
        }

        $type = (string)($_GET['type'] ?? 'all'); 
$catSlug = (string)($_GET['cat'] ?? 'all'); 
$dateKey = (string)($_GET['date'] ?? 'any'); 
$match = (string)($_GET['match'] ?? 'all'); 

$categoryId = 0;
if ($catSlug !== '' && $catSlug !== 'all') {
    $catRow = $this->categories->findBySlug($catSlug);
    if (!empty($catRow['id'])) {
        $categoryId = (int)$catRow['id'];
    }
}

$dateFrom = '';
$dateTo = '';
if ($dateKey !== 'any') {
    $now = new \DateTimeImmutable('now');
    if ($dateKey === '24h') {
        $dateFrom = $now->modify('-1 day')->format('Y-m-d');
    } elseif ($dateKey === '7d') {
        $dateFrom = $now->modify('-7 days')->format('Y-m-d');
    } elseif ($dateKey === '30d') {
        $dateFrom = $now->modify('-30 days')->format('Y-m-d');
    } elseif ($dateKey === 'year') {
        $dateFrom = $now->modify('-365 days')->format('Y-m-d');
    }
    $dateTo = $now->format('Y-m-d');
}

$filters = [
    'type' => $type,
    'category_id' => $categoryId,
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
    'match' => $match,
];

        $list = $this->news->search($q, $page, $perPage, $filters);

        $cats = $this->categories->headerCategories(50);

        $view = $this->rootDir . '/frontend/views/search.php';
        if (!is_file($view)) {
            http_response_code(500);
            echo 'View not found.';
            exit;
        }

        $pageSeo = $this->seo->search($q);

        
        (static function (string $view, array $vars): void {
            foreach ($vars as $k => $v) {
                if (is_string($k) && preg_match('~^[a-zA-Z_][a-zA-Z0-9_]*$~', $k)) {
                    ${$k} = $v;
                }
            }
            require $view;
        })($view, [
            'results' => $list['items'],
            'pages' => $list['total_pages'],
            'total' => $list['total'],
            'counts' => $list['counts'],
            'categories' => $cats,
            'pageSeo' => $pageSeo,
        ]);

        exit;
    }
}
