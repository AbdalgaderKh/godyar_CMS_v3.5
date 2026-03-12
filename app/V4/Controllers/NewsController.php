<?php
declare(strict_types=1);
namespace GodyarV4\Controllers;

use GodyarV4\Bootstrap\Request;
use GodyarV4\Bootstrap\Response;
use GodyarV4\Repositories\NewsRepository;
use GodyarV4\Services\ResponseCacheService;

final class NewsController extends Controller
{
    public function show(Request $request, string $locale, string $slug): Response
    {
        $repo = $this->app->make(NewsRepository::class);
        $news = $repo->findBySlug($slug, $locale);
        if ($news === null) {
            return (new ErrorController($this->app))->notFound($request);
        }
        return $this->app->make(ResponseCacheService::class)->remember('news:' . $locale . ':' . $slug, 300, function () use ($repo, $news, $locale): Response {
            $related = $repo->related((int) ($news['id'] ?? 0), (string) ($news['category_slug'] ?? ''), $locale, 4);
            return $this->theme()->render('pages/news', [
                'locale' => $locale,
                'news' => $news,
                'related' => $related,
                'seo' => $this->seo()->forNews($news, $locale),
            ]);
        });
    }
}
