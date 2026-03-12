<?php
declare(strict_types=1);
namespace GodyarV4\Controllers;

use GodyarV4\Bootstrap\App;
use GodyarV4\Bootstrap\Request;
use GodyarV4\Bootstrap\Response;
use GodyarV4\Services\SeoService;
use GodyarV4\Services\ThemeService;

abstract class Controller
{
    public function __construct(protected readonly App $app) {}

    protected function theme(): ThemeService
    {
        return $this->app->make(ThemeService::class);
    }

    protected function seo(): SeoService
    {
        return $this->app->make(SeoService::class);
    }

    protected function ensureAdmin(Request $request): ?Response
    {
        godyar_v4_session_start();
        if (godyar_v4_is_admin()) {
            return null;
        }
        godyar_v4_flash_set('admin', 'يجب تسجيل الدخول بصلاحية إدارة للوصول إلى أدوات v4.', 'warning');
        return Response::redirect(godyar_v4_admin_login_url(), 302);
    }
}
