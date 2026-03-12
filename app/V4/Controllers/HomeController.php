<?php
declare(strict_types=1);
namespace GodyarV4\Controllers;

use GodyarV4\Bootstrap\Request;
use GodyarV4\Bootstrap\Response;
use GodyarV4\Repositories\NewsRepository;
use GodyarV4\Services\I18nService;
use GodyarV4\Services\ResponseCacheService;

final class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        $locale = $this->app->make(I18nService::class)->detectFromPath($request->path());
        return $this->app->make(ResponseCacheService::class)->remember('home:' . $locale, 180, function () use ($locale): Response {
            $news = $this->app->make(NewsRepository::class)->latest($locale, 8);
            return $this->theme()->render('pages/home', [
                'seo' => $this->seo()->defaults([
                    'title' => 'Godyar - Home',
                    'canonical' => godyar_v4_url($locale),
                ]),
                'locale' => $locale,
                'news' => $news,
            ]);
        });
    }
}
