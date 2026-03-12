<?php
declare(strict_types=1);
namespace GodyarV4\Services;

use GodyarV4\Repositories\MediaRepository;

final class MediaBridgeService
{
    public function __construct(private readonly MediaRepository $mediaRepository) {}

    public function forNews(array $news): array
    {
        return $this->mediaRepository->forNews((int)($news['id'] ?? 0), $news);
    }

    public function primaryForNews(array $news): ?array
    {
        $items = $this->forNews($news);
        return $items[0] ?? null;
    }
}
