<?php

declare(strict_types=1);

namespace GodyarV4\Services;

use GodyarV4\Bootstrap\App;
use GodyarV4\Bootstrap\Response;

final class ThemeService
{
    public function __construct(private App $app) {}

    public function render(string $view, array $data = [], int $status = 200): Response
    {
        $layout = godyar_v4_theme_path((string) godyar_v4_config('theme.layout', 'layout/master.php'));
        $viewFile = godyar_v4_theme_path($view . '.php');
        if (!is_file($viewFile)) {
            return Response::html('Missing view: ' . e($view), 500);
        }

        $data['content_view'] = $viewFile;
        $data['theme'] = $this;
        $data['app'] = $this->app;
        $data['settings'] = $this->app->make(SettingsService::class);
        $data['seo'] = $data['seo'] ?? $this->app->make(SeoService::class)->defaults();
        $data['locale'] = $this->app->make(I18nService::class)->locale();

        ob_start();
        extract($data, EXTR_SKIP);
        require $layout;
        return Response::html((string) ob_get_clean(), $status);
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
