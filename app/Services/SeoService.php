<?php

declare(strict_types=1);

namespace GodyarV4\Services;

final class SeoService
{
    public function defaults(array $overrides = []): array
    {
        return array_merge([
            'title' => godyar_v4_config('seo.default_title', 'Godyar CMS'),
            'description' => godyar_v4_config('seo.default_description', ''),
            'robots' => godyar_v4_config('seo.robots', 'index,follow'),
            'canonical' => '',
            'og_image' => godyar_v4_config('seo.default_og_image', ''),
        ], $overrides);
    }
}
