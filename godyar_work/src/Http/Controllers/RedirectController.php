<?php
namespace App\Http\Controllers;

use Godyar\Services\NewsService;
use Godyar\Services\CategoryService;

final class RedirectController
{
    
    private NewsService $news;

    
    private CategoryService $categories;

    
    private string $basePrefix;

    public function __construct(NewsService $news, CategoryService $categories, string $basePrefix = '')
    {
        $this->news = $news;
        $this->categories = $categories;
        $this->basePrefix = rtrim($basePrefix, '/');
    }

    public function newsIdToSlug(int $id): void
    {
        $slug = $this->news->slugById($id) ?? '';

        if ($slug === '') {
            http_response_code(404);
            echo 'Not Found';
            exit;
        }

        header('Location: ' . $this->basePrefix . '/news/id/' . (int)$id, true, 301);
        exit;
    }

    public function categoryIdToSlug(int $id): void
    {
        $slug = $this->categories->slugById($id) ?? '';

        if ($slug === '') {
            http_response_code(404);
            echo 'Not Found';
            exit;
        }

        header('Location: ' . $this->basePrefix . '/category/' .rawurlencode($slug), true, 301);
        exit;
    }
}
