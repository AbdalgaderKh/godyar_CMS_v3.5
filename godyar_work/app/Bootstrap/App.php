<?php

declare(strict_types=1);

namespace GodyarV4\Bootstrap;

use GodyarV4\Services\CacheService;
use GodyarV4\Services\I18nService;
use GodyarV4\Services\LegacyRedirectService;
use GodyarV4\Services\SeoService;
use GodyarV4\Services\SettingsService;
use GodyarV4\Services\ThemeService;

final class App
{
    private Router $router;
    private array $instances = [];

    public function __construct(private readonly string $root)
    {
        date_default_timezone_set((string) godyar_v4_config('app.timezone', 'Asia/Riyadh'));
        (new ErrorHandler($this->root))->register();
        $this->router = new Router(require $this->root . '/app/Config/routes.php');
    }

    public function run(): void
    {
        $request = Request::capture();
        $response = (new Kernel($this))->handle($request);
        $response->send();
    }

    public function root(): string
    {
        return $this->root;
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function make(string $class): object
    {
        if (isset($this->instances[$class])) {
            return $this->instances[$class];
        }

        return $this->instances[$class] = match ($class) {
            ThemeService::class => new ThemeService($this),
            SettingsService::class => new SettingsService(),
            I18nService::class => new I18nService(),
            SeoService::class => new SeoService(),
            CacheService::class => new CacheService(),
            LegacyRedirectService::class => new LegacyRedirectService(),
            default => new $class($this),
        };
    }
}
