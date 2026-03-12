<?php

declare(strict_types=1);

namespace GodyarV4\Controllers;

use GodyarV4\Bootstrap\Request;
use GodyarV4\Bootstrap\Response;
use GodyarV4\Repositories\PageRepository;
use GodyarV4\Services\SeoService;
use GodyarV4\Services\SettingsService;
use GodyarV4\Services\ThemeService;

final class PageController extends Controller
{
    public function show(Request $request, string $lang, string $slug): Response
    {
        $page = (new PageRepository())->findBySlug($slug, $lang);
        if (!$page) {
            return $this->app->make(ErrorController::class)->notFound($request);
        }
        $identity = $this->app->make(SettingsService::class)->siteIdentity();
        $seo = $this->app->make(SeoService::class)->defaults([
            'title' => ($page['title'] ?? ucfirst($slug)) . ' | ' . ($identity['site_name'] ?? 'Godyar CMS'),
            'description' => mb_substr(strip_tags((string)($page['content'] ?? '')), 0, 160),
            'canonical' => godyar_v4_base_url($request->path),
        ]);
        return $this->app->make(ThemeService::class)->render('pages/page', compact('page', 'seo', 'lang', 'identity'));
    }
}
