<?php
declare(strict_types=1);
namespace GodyarV4\Controllers;

use GodyarV4\Bootstrap\Request;
use GodyarV4\Bootstrap\Response;
use GodyarV4\Services\SearchService;

final class SearchApiController extends Controller
{
    public function suggest(Request $request, string $locale): Response
    {
        $term = trim((string)$request->query('q', ''));
        $service = $this->app->make(SearchService::class);
        $items = $term === '' ? $service->popularFallback($locale) : $service->suggest($term, $locale, 8);
        return Response::json([
            'ok' => true,
            'query' => $term,
            'count' => count($items),
            'items' => array_values(array_map(static function(array $item): array {
                return [
                    'label' => (string)($item['label'] ?? $item['title'] ?? ''),
                    'title' => (string)($item['title'] ?? $item['label'] ?? ''),
                    'url' => (string)($item['url'] ?? '#'),
                    'type' => (string)($item['type'] ?? 'link'),
                ];
            }, $items)),
        ]);
    }
}
