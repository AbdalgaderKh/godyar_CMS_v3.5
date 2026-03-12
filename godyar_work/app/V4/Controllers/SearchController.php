<?php
declare(strict_types=1);
namespace GodyarV4\Controllers;

use GodyarV4\Bootstrap\Request;
use GodyarV4\Bootstrap\Response;
use GodyarV4\Repositories\NewsRepository;
use GodyarV4\Repositories\SearchAnalyticsRepository;
use GodyarV4\Services\I18nService;

final class SearchController extends Controller
{
    public function index(Request $request): Response
    {
        $locale = $this->app->make(I18nService::class)->detectFromPath($request->path());
        $q = trim((string)($request->query('q') ?? ''));
        $results = $q !== '' ? $this->app->make(NewsRepository::class)->search($q, $locale, 24) : [];
        if ($q !== '') {
            $this->app->make(SearchAnalyticsRepository::class)->log($q, count($results), $locale);
        }

        return $this->theme()->render('pages/search', [
            'locale' => $locale,
            'query' => $q,
            'results' => $results,
            'seo' => $this->seo()->defaults([
                'title' => $q !== '' ? ('نتائج البحث: ' . $q) : 'البحث',
                'description' => $q !== '' ? ('نتائج البحث عن ' . $q) : 'بحث في الأخبار والمحتوى',
                'canonical' => godyar_v4_url($locale, 'search') . ($q !== '' ? ('?q=' . urlencode($q)) : ''),
            ]),
        ]);
    }

    public function suggest(Request $request): Response
    {
        $locale = $this->app->make(I18nService::class)->detectFromPath($request->path());
        $q = trim((string)($request->query('q') ?? ''));
        $items = [];
        if ($q !== '') {
            foreach ($this->app->make(NewsRepository::class)->search($q, $locale, 6) as $row) {
                $items[] = [
                    'title' => (string)($row['title'] ?? ''),
                    'url' => godyar_v4_url($locale, 'news/' . (string)($row['slug'] ?? '')),
                    'meta' => (string)($row['published_at'] ?? ''),
                ];
            }
        }
        return Response::json(['query' => $q, 'items' => $items]);
    }
}
