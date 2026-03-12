<?php
namespace App\Http\Controllers;

use App\Core\FrontendRenderer;
use Godyar\Services\AdService;
use Godyar\Services\CategoryService;
use Godyar\Services\NewsService;
use Godyar\Services\TagService;
use PDO;
use Throwable;

final class NewsController
{
    private PDO $pdo;
    private NewsService $news;
    private CategoryService $categories;
    private TagService $tags;
    private AdService $ads;
    private string $basePrefix;

    public function __construct(PDO $pdo, NewsService $news, CategoryService $categories, TagService $tags, AdService $ads, string $basePrefix = '')
    {
        $this->pdo = $pdo;
        $this->news = $news;
        $this->categories = $categories;
        $this->tags = $tags;
        $this->ads = $ads;
        $this->basePrefix = rtrim($basePrefix, '/');
    }

    public function preview(int $id): void
    {
        $this->show((string)$id, true);
    }

    public function print(int $id): void
    {
        $id = (int)$id;
        if ($id <= 0) {
            $this->renderMessage(404, 'غير موجود', 'لم يتم تحديد الخبر.');
        }

        $post = $this->news->findById($id, false);
        if (!$post) {
            $this->renderMessage(404, 'غير موجود', 'الخبر غير موجود.');
        }

        $baseUrl = $this->absoluteBaseUrl();
        $articleUrlFull = rtrim($baseUrl, '/') . $this->basePrefix . '/news/id/' . $id;

        $root = dirname(__DIR__, 3);
        $renderer = new FrontendRenderer($root, $this->basePrefix);
        $renderer->render('frontend/views/news_print.php', [
            'post' => $post,
            'baseUrl' => $baseUrl,
            'articleUrlFull' => $articleUrlFull,
        ]);
    }

    
    public function show(string $slugOrId, bool $forcePreview = false): void
    {
        $slugOrId = trim($slugOrId);
        if ($slugOrId === '') {
            $this->renderMessage(404, 'غير موجود', 'لم يتم تحديد الخبر.');
        }

        
        $isPreview = $forcePreview || ((string)($_GET['preview'] ?? '') === '1');

        
        if ($isPreview && !$this->isAdmin()) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }

        $post = $this->news->findBySlugOrId($slugOrId, $isPreview);
        if (!$post) {
            $this->renderMessage(404, 'غير موجود', 'الخبر غير موجود.');
        }

        $id = (int)($post['id'] ?? 0);
        if ($id > 0 && !$isPreview) {
            $this->news->incrementViews($id);
        }

        $categoryId = (int)($post['category_id'] ?? 0);
        $related = ($categoryId > 0 && $id > 0) ? $this->news->relatedByCategory($categoryId, $id, 6) : [];
        $tags = ($id > 0) ? $this->tags->forNews($id) : [];
        $latest = $this->news->latest(10, false);
        $mostRead = $this->news->mostRead(10);

        $baseUrl = $this->absoluteBaseUrl();
        $lang = function_exists('gdy_request_lang') ? gdy_request_lang('ar') : 'ar';
        $navBaseUrl = function_exists('gdy_front_url') ? rtrim(gdy_front_url('', $lang), '/') : (rtrim($baseUrl, '/') . '/' . $lang);
        $canonicalUrl = function_exists('gdy_route_news_url') ? gdy_route_news_url(['id'=>$id,'slug'=>(string)($post['slug'] ?? $slugOrId)], $lang) : ($id > 0 ? ($navBaseUrl . '/news/id/' . $id) : ($navBaseUrl . '/news/' . rawurlencode((string)($post['slug'] ?? $slugOrId))));
        $metaTitle = (string)($post['meta_title'] ?? $post['title'] ?? 'خبر');
        $metaDescription = trim((string)($post['meta_description'] ?? $post['excerpt'] ?? $post['summary'] ?? ''));
        if ($metaDescription === '') {
            $plain = trim(strip_tags((string)($post['content'] ?? $post['body'] ?? '')));
            $metaDescription = function_exists('mb_substr') ? mb_substr($plain, 0, 180) : substr($plain, 0, 180);
        }

        $root = dirname(__DIR__, 3);
        $renderer = new FrontendRenderer($root, '');
        $renderer->render('frontend/views/news_single_legacy.php', [
            'news' => $post,
            'article' => $post,
            'related' => $related,
            'tags' => $tags,
            'latest' => $latest,
            'mostRead' => $mostRead,
            'isPreview' => $isPreview,
            'pdo' => $this->pdo,
            'baseUrl' => $baseUrl,
            'rootUrl' => $baseUrl,
            'navBaseUrl' => $navBaseUrl,
            'pageLang' => $lang,
            'homeUrl' => $navBaseUrl . '/',
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'canonical_url' => $canonicalUrl,
        ]);
    }

    private function isAdmin(): bool
    {
        try {
            if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
                session_start();
            }
            $role = (string)($_SESSION['user']['role'] ?? ($_SESSION['user_role'] ?? ''));
            return $role !== '' && in_array($role, ['admin', 'superadmin', 'manager'], true);
        } catch (Throwable) {
            return false;
        }
    }

    private function absoluteBaseUrl(): string
    {
        
        if (function_exists('base_url')) {
            $u = (string)base_url();
            return rtrim($u, '/');
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host;
    }

    private function renderMessage(int $status, string $title, string $message): void
    {
        http_response_code($status);
        echo '<!doctype html><html lang="ar"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>'
            . '<style>body{font-family:system-ui,Segoe UI,Arial,sans-serif;display:flex;min-height:100vh;align-items:center;justify-content:center;margin:0;background:#f8fafc;color:#0f172a;padding:24px}.box{max-width:720px;padding:28px 22px;background:#fff;border:1px solid #dbe4ee;border-radius:18px;box-shadow:0 18px 42px rgba(15,23,42,.08)}h1{margin:0 0 12px;font-size:1.6rem}p{margin:0;line-height:1.9;color:#475569}</style>'
            . '</head><body><div class="box"><h1>'
            . htmlspecialchars($title, ENT_QUOTES, 'UTF-8')
            . '</h1><p>'
            . htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
            . '</p></div></body></html>';
        exit;
    }
}
