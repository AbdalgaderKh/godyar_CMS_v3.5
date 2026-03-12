<?php
declare(strict_types=1);
namespace GodyarV4\Services;

use GodyarV4\Repositories\MenuRepository;

final class MenuService
{
    public function __construct(
        private readonly MenuRepository $repository,
        private readonly SettingsService $settings
    ) {}

    public function header(string $locale = 'ar'): array
    {
        return $this->repository->get('header_main', $locale);
    }

    public function footer(string $locale = 'ar'): array
    {
        return $this->repository->get('footer_primary', $locale);
    }
}
