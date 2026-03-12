<?php

declare(strict_types=1);

namespace GodyarV4\Controllers;

use GodyarV4\Bootstrap\Request;
use GodyarV4\Bootstrap\Response;
use GodyarV4\Repositories\NewsRepository;
use GodyarV4\Services\SeoService;
use GodyarV4\Services\SettingsService;
use GodyarV4\Services\ThemeService;

final class NewsController extends Controller
{
    public function show(Request $request, string $lang, string $slug): Response
    {
        $repository = new NewsRepository();
        $news = $repository->findBySlug($slug, $lang);
        if (!$news) {
            return $this->app->make(ErrorController::class)->notFound($request);
        }
        $identity = $this->app->make(SettingsService::class)->siteIdentity();
        $related = [];
        if (!empty($news['category_id']) && !empty($news['id'])) {
            $related = $repository->related((int)$news['category_id'], (int)$news['id'], 4);
        }
        $seo = $this->app->make(SeoService::class)->defaults([
            'title' => (($news['seo_title'] ?? '') ?: $news['title']) . ' | ' . ($identity['site_name'] ?? 'Godyar'),
            'description' => (($news['seo_description'] ?? '') ?: ($news['excerpt'] ?? '')),
            'canonical' => godyar_v4_base_url($request->path),
        ]);
        return $this->app->make(ThemeService::class)->render('pages/news', compact('news', 'related', 'seo', 'lang', 'identity'));
    }
}
