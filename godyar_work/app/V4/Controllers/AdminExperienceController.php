<?php
declare(strict_types=1);
namespace GodyarV4\Controllers;

use GodyarV4\Bootstrap\Request;
use GodyarV4\Bootstrap\Response;
use GodyarV4\Repositories\SystemSettingsRepository;
use GodyarV4\Services\HookBus;
use GodyarV4\Services\PluginService;
use GodyarV4\Services\ThemeRegistryService;
use GodyarV4\Services\ThemeSettingsService;

final class AdminExperienceController extends Controller
{
    public function themes(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) return $guard;
        return $this->theme()->render('pages/admin-themes', [
            'locale' => 'ar',
            'rows' => $this->app->make(ThemeRegistryService::class)->themes(),
            'flash' => godyar_v4_flash_get('themes'),
            'seo' => $this->seo()->defaults(['title' => 'Theme Switcher', 'robots' => 'noindex,nofollow'])
        ]);
    }

    public function activateTheme(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) return $guard;
        if (godyar_v4_csrf_valid((string)$request->input('_token', ''))) {
            $key = (string)$request->input('key', 'default');
            $ok = $this->app->make(ThemeRegistryService::class)->activate($key);
            godyar_v4_flash_set('themes', $ok ? 'تم تفعيل الثيم بنجاح.' : 'تعذر تفعيل الثيم المطلوب.', $ok ? 'success' : 'danger');
        }
        return Response::redirect(godyar_v4_base_url('/v4/admin/themes'));
    }

    public function themeSettings(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) return $guard;
        return $this->theme()->render('pages/admin-theme-settings', [
            'locale' => 'ar',
            'rows' => $this->app->make(ThemeSettingsService::class)->all(),
            'flash' => godyar_v4_flash_get('theme_settings'),
            'seo' => $this->seo()->defaults(['title' => 'Theme Settings', 'robots' => 'noindex,nofollow'])
        ]);
    }

    public function saveThemeSettings(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) return $guard;
        if (godyar_v4_csrf_valid((string)$request->input('_token', ''))) {
            $this->app->make(ThemeSettingsService::class)->save($request->all());
            godyar_v4_flash_set('theme_settings', 'تم حفظ إعدادات الثيم.', 'success');
        } else {
            godyar_v4_flash_set('theme_settings', 'فشل التحقق الأمني.', 'danger');
        }
        return Response::redirect(godyar_v4_base_url('/v4/admin/theme-settings'));
    }

    public function themeCustomizer(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) return $guard;
        return $this->theme()->render('pages/admin-theme-customizer', [
            'locale' => 'ar',
            'rows' => $this->app->make(ThemeSettingsService::class)->all(),
            'flash' => godyar_v4_flash_get('theme_customizer'),
            'seo' => $this->seo()->defaults(['title' => 'Theme Customizer', 'robots' => 'noindex,nofollow'])
        ]);
    }

    public function saveThemeCustomizer(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) return $guard;
        if (godyar_v4_csrf_valid((string)$request->input('_token', ''))) {
            $this->app->make(ThemeSettingsService::class)->save($request->all());
            godyar_v4_flash_set('theme_customizer', 'تم حفظ تخصيص الثيم.', 'success');
        } else {
            godyar_v4_flash_set('theme_customizer', 'فشل التحقق الأمني.', 'danger');
        }
        return Response::redirect(godyar_v4_base_url('/v4/admin/theme-customizer'));
    }

    public function hooks(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) return $guard;
        return $this->theme()->render('pages/admin-hooks', [
            'locale' => 'ar',
            'rows' => $this->app->make(HookBus::class)->summary(),
            'plugins_rows' => $this->app->make(PluginService::class)->all(),
            'settings_rows' => $this->app->make(SystemSettingsRepository::class)->all(),
            'seo' => $this->seo()->defaults(['title' => 'Hook Inspector', 'robots' => 'noindex,nofollow'])
        ]);
    }
}
