<?php
declare(strict_types=1);
namespace GodyarV4\Controllers;

use GodyarV4\Bootstrap\Request;
use GodyarV4\Bootstrap\Response;
use GodyarV4\Repositories\PageRepository;
use GodyarV4\Services\ResponseCacheService;

final class PageController extends Controller
{
    public function show(Request $request, string $locale, string $slug): Response
    {
        $page = $this->app->make(PageRepository::class)->findBySlug($slug, $locale);
        if ($page === null) {
            return (new ErrorController($this->app))->notFound($request);
        }
        return $this->app->make(ResponseCacheService::class)->remember('page:' . $locale . ':' . $slug, 300, function () use ($page, $locale): Response {
            return $this->theme()->render('pages/page', [
                'locale' => $locale,
                'page' => $page,
                'seo' => $this->seo()->forPage($page, $locale),
            ]);
        });
    }
}
