<?php
declare(strict_types=1);
namespace GodyarV4\Bootstrap;

use GodyarV4\Repositories\CategoryRepository;
use GodyarV4\Repositories\MenuRepository;
use GodyarV4\Repositories\MediaCleanupQueueRepository;
use GodyarV4\Repositories\MediaRepository;
use GodyarV4\Repositories\NewsRepository;
use GodyarV4\Repositories\RedirectRepository;
use GodyarV4\Repositories\SeoAuditRepository;
use GodyarV4\Repositories\RevisionRepository;
use GodyarV4\Repositories\SeoOverrideRepository;
use GodyarV4\Repositories\PageRepository;
use GodyarV4\Repositories\SystemSettingsRepository;
use GodyarV4\Services\I18nService;
use GodyarV4\Services\LegacyRedirectService;
use GodyarV4\Services\MenuService;
use GodyarV4\Services\MediaOptimizerService;
use GodyarV4\Services\PluginService;
use GodyarV4\Services\ResponseCacheService;
use GodyarV4\Services\ThemeRegistryService;
use GodyarV4\Services\SeoAuditService;
use GodyarV4\Services\SeoService;
use GodyarV4\Services\SettingsService;
use GodyarV4\Services\SitemapService;
use GodyarV4\Services\SystemHealthService;
use GodyarV4\Services\ThemeService;
use GodyarV4\Services\HookBus;

final class App
{
    private Router $router;
    private array $instances = [];

    public function __construct(private readonly string $root)
    {
        date_default_timezone_set((string) godyar_v4_config('app.timezone', 'Asia/Riyadh'));
        (new ErrorHandler($root))->register();
        $this->router = new Router(require $root . '/app/V4/Config/routes.php');
        $this->make(PluginService::class)->boot();
    }

    public function run(): void
    {
        $request = Request::capture();
        (new Kernel($this))->handle($request)->send();
    }

    public function root(): string { return $this->root; }
    public function router(): Router { return $this->router; }

    public function make(string $class): object
    {
        if (isset($this->instances[$class])) {
            return $this->instances[$class];
        }

        return $this->instances[$class] = match ($class) {
            ThemeService::class => new ThemeService($this),
            SeoService::class => new SeoService($this),
            SeoAuditService::class => new SeoAuditService($this),
            SettingsService::class => new SettingsService(),
            HookBus::class => ($GLOBALS['godyar_v4_hook_bus'] = new HookBus()),
            SystemSettingsRepository::class => new SystemSettingsRepository(),
            SystemHealthService::class => new SystemHealthService(),
            I18nService::class => new I18nService(),
            LegacyRedirectService::class => new LegacyRedirectService(),
            MenuRepository::class => new MenuRepository(),
            RevisionRepository::class => new RevisionRepository(),
            SeoOverrideRepository::class => new SeoOverrideRepository(),
            SeoAuditRepository::class => new SeoAuditRepository(),
            RedirectRepository::class => new RedirectRepository(),
            MediaRepository::class => new MediaRepository(),
            MediaCleanupQueueRepository::class => new MediaCleanupQueueRepository(),
            MenuService::class => new MenuService($this->make(MenuRepository::class), $this->make(SettingsService::class)),
            PluginService::class => new PluginService($this->make(HookBus::class)),
            ThemeRegistryService::class => new ThemeRegistryService($this->make(SystemSettingsRepository::class)),
            MediaOptimizerService::class => new MediaOptimizerService(),
            ResponseCacheService::class => new ResponseCacheService(),
            PageRepository::class => new PageRepository(),
            NewsRepository::class => new NewsRepository(),
            CategoryRepository::class => new CategoryRepository(),
            SitemapService::class => new SitemapService(
                $this->make(PageRepository::class),
                $this->make(NewsRepository::class),
                $this->make(CategoryRepository::class)
            ),
            default => new $class($this),
        };
    }
}
