<?php
declare(strict_types=1);
namespace GodyarV4\Services;

use GodyarV4\Bootstrap\App;
use GodyarV4\Bootstrap\Response;

final class ThemeService
{
    public function __construct(private readonly App $app) {}

    public function render(string $view, array $data = [], int $status = 200): Response
    {
        $layout = godyar_v4_theme_path((string) godyar_v4_config('theme.layout', 'layout/master.php'));
        $viewFile = godyar_v4_theme_path($view . '.php');
        if (!is_file($viewFile) || !is_file($layout)) {
            return Response::html('Missing v4 view/layout', 500);
        }

        $data['content_view'] = $viewFile;
        $data['theme'] = $this;
        $data['settings'] = $this->app->make(SettingsService::class);
        $data['menu'] = $this->app->make(MenuService::class);
        $data['plugins'] = $this->app->make(PluginService::class);
        $data['locale'] = $data['locale'] ?? $this->app->make(I18nService::class)->locale();
        $data['seo'] = $data['seo'] ?? $this->app->make(SeoService::class)->defaults();

        godyar_v4_hooks()->dispatch('theme.rendering', ['view' => $view, 'data' => $data]);
        ob_start();
        extract($data, EXTR_SKIP);
        require $layout;
        $html = (string) ob_get_clean();
        godyar_v4_hooks()->dispatch('theme.rendered', ['view' => $view, 'html_length' => strlen($html)]);
        return Response::html($html, $status);
    }

    public function partial(string $name, array $data = []): void
    {
        $file = godyar_v4_theme_path('partials/' . $name . '.php');
        if (!is_file($file)) {
            return;
        }
        extract($data, EXTR_SKIP);
        require $file;
    }
}
