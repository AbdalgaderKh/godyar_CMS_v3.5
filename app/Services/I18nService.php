<?php

declare(strict_types=1);

namespace GodyarV4\Services;

use GodyarV4\Bootstrap\Request;

final class I18nService
{
    private string $locale = 'ar';

    public function bootFromRequest(Request $request): void
    {
        if (preg_match('#^/(ar|en|fr)(?:/|$)#i', $request->path, $m)) {
            $this->locale = strtolower($m[1]);
        } else {
            $this->locale = (string) godyar_v4_config('app.default_locale', 'ar');
        }
    }

    public function locale(): string
    {
        return $this->locale;
    }

    public function localizedUrl(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        return '/' . $this->locale . ($path === '/' ? '' : $path);
    }
}
