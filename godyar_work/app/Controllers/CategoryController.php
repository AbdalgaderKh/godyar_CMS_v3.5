<?php

declare(strict_types=1);

namespace GodyarV4\Controllers;

use GodyarV4\Bootstrap\Request;
use GodyarV4\Bootstrap\Response;
use GodyarV4\Repositories\CategoryRepository;
use GodyarV4\Services\SeoService;
use GodyarV4\Services\SettingsService;
use GodyarV4\Services\ThemeService;

final class CategoryController extends Controller
{
    public function show(Request $request, string $lang, string $slug): Response
    {
        $repository = new CategoryRepository();
        $category = $repository->findBySlug($slug, $lang);
        if (!$category) {
            return $this->app->make(ErrorController::class)->notFound($request);
        }
        $items = $repository->latestNewsByCategorySlug($slug, $lang, 12);
        $identity = $this->app->make(SettingsService::class)->siteIdentity();
        $seo = $this->app->make(SeoService::class)->defaults([
            'title' => ($category['title'] ?? $slug) . ' | ' . ($identity['site_name'] ?? 'Godyar CMS'),
            'description' => $category['description'] ?? 'أرشيف التصنيف.',
            'canonical' => godyar_v4_base_url($request->path),
        ]);
        return $this->app->make(ThemeService::class)->render('pages/category', compact('category', 'items', 'seo', 'lang', 'identity'));
    }
}
