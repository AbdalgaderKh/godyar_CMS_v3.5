<?php
declare(strict_types=1);
namespace GodyarV4\Controllers;

use GodyarV4\Bootstrap\Request;
use GodyarV4\Bootstrap\Response;
use GodyarV4\Repositories\CategoryRepository;
use GodyarV4\Repositories\NewsRepository;
use GodyarV4\Services\ResponseCacheService;

final class CategoryController extends Controller
{
    public function show(Request $request, string $locale, string $slug): Response
    {
        $category = $this->app->make(CategoryRepository::class)->findBySlug($slug, $locale);
        if ($category === null) {
            return (new ErrorController($this->app))->notFound($request);
        }
        return $this->app->make(ResponseCacheService::class)->remember('category:' . $locale . ':' . $slug, 240, function () use ($slug, $locale, $category): Response {
            $items = $this->app->make(NewsRepository::class)->byCategory($slug, $locale, 12);
            return $this->theme()->render('pages/category', [
                'locale' => $locale,
                'category' => $category,
                'items' => $items,
                'seo' => $this->seo()->forCategory($category, $locale),
            ]);
        });
    }
}
