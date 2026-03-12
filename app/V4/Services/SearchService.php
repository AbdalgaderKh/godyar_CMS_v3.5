<?php
declare(strict_types=1);
namespace GodyarV4\Services;

use GodyarV4\Repositories\SearchRepository;

final class SearchService
{
    public function __construct(private readonly SearchRepository $repository) {}

    public function search(string $term, string $locale = 'ar', int $limit = 20): array
    {
        return $this->repository->search($term, $locale, $limit);
    }

    public function suggest(string $term, string $locale = 'ar', int $limit = 6): array
    {
        return $this->repository->suggest($term, $locale, $limit);
    }

    public function popularFallback(string $locale = 'ar'): array
    {
        return [
            ['label' => 'الأخبار العاجلة', 'url' => godyar_v4_url($locale)],
            ['label' => 'من نحن', 'url' => godyar_v4_url($locale, 'page/about')],
            ['label' => 'تواصل', 'url' => godyar_v4_url($locale, 'contact')],
        ];
    }
}
