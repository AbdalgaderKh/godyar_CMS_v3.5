<?php

declare(strict_types=1);

namespace GodyarV4\Controllers;

use GodyarV4\Bootstrap\Request;
use GodyarV4\Bootstrap\Response;
use GodyarV4\Repositories\NewsRepository;
use GodyarV4\Services\SeoService;
use GodyarV4\Services\SettingsService;
use GodyarV4\Services\ThemeService;

final class HomeController extends Controller
{
    public function index(Request $request, ?string $lang = null): Response
    {
        $lang = $lang ?: godyar_v4_config('app.default_locale', 'ar');
        $news = (new NewsRepository())->latest(8, $lang);
        $identity = $this->app->make(SettingsService::class)->siteIdentity();
        $seo = $this->app->make(SeoService::class)->defaults([
            'title' => ($identity['site_name'] ?? 'Godyar CMS') . ' | الرئيسية',
            'description' => $identity['site_description'] ?? 'الواجهة الرئيسية الموحدة لـ Godyar CMS v4.',
            'canonical' => godyar_v4_base_url($request->path),
        ]);
        return $this->app->make(ThemeService::class)->render('pages/home', compact('news', 'seo', 'lang', 'identity'));
    }
}
