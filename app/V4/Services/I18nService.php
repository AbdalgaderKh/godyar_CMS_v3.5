<?php
declare(strict_types=1);
namespace GodyarV4\Services;

final class I18nService
{
    public function locale(): string
    {
        return $_SESSION['lang'] ?? $_COOKIE['gdy_lang'] ?? godyar_v4_config('app.default_locale', 'ar');
    }

    public function detectFromPath(string $path): string
    {
        if (preg_match('#^/(ar|en|fr)(?:/|$)#', $path, $m)) {
            $_SESSION['lang'] = $m[1];
            return $m[1];
        }
        return (string) $this->locale();
    }
}
