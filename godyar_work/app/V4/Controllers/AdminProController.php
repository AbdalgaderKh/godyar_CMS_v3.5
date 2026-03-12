<?php
declare(strict_types=1);
namespace GodyarV4\Controllers;

use GodyarV4\Bootstrap\Request;
use GodyarV4\Bootstrap\Response;
use GodyarV4\Services\MediaOptimizerService;
use GodyarV4\Services\PluginService;
use GodyarV4\Services\ResponseCacheService;
use GodyarV4\Services\ThemeRegistryService;

final class AdminProController extends Controller
{
    public function plugins(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) return $guard;
        return $this->theme()->render('pages/admin-plugins', [
            'locale' => 'ar',
            'rows' => $this->app->make(PluginService::class)->all(),
            'flash' => godyar_v4_flash_get('plugins'),
            'seo' => $this->seo()->defaults(['title' => 'V4 Plugins', 'robots' => 'noindex,nofollow'])
        ]);
    }

    public function togglePlugin(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) return $guard;
        if (godyar_v4_csrf_valid((string)$request->input('_token', ''))) {
            $this->app->make(PluginService::class)->toggle((string)$request->input('key', ''), (bool)$request->input('enabled', ''));
            godyar_v4_flash_set('plugins', 'تم تحديث حالة الإضافة.', 'success');
        }
        return Response::redirect(godyar_v4_base_url('/v4/admin/plugins'));
    }

    public function themes(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) return $guard;
        return $this->theme()->render('pages/admin-themes', [
            'locale' => 'ar',
            'rows' => $this->app->make(ThemeRegistryService::class)->themes(),
            'flash' => godyar_v4_flash_get('themes'),
            'seo' => $this->seo()->defaults(['title' => 'V4 Themes', 'robots' => 'noindex,nofollow'])
        ]);
    }

    public function mediaOptimizer(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) return $guard;
        $service = $this->app->make(MediaOptimizerService::class);
        return $this->theme()->render('pages/admin-media-optimizer', [
            'locale' => 'ar',
            'caps' => $service->capabilities(),
            'rows' => $service->scan(160),
            'flash' => godyar_v4_flash_get('media_optimizer'),
            'seo' => $this->seo()->defaults(['title' => 'V4 Media Optimizer', 'robots' => 'noindex,nofollow'])
        ]);
    }

    public function optimizeMedia(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) return $guard;
        if (godyar_v4_csrf_valid((string)$request->input('_token', ''))) {
            $res = $this->app->make(MediaOptimizerService::class)->optimize((string)$request->input('relative', ''));
            godyar_v4_flash_set('media_optimizer', (string)$res['message'], ($res['ok'] ?? false) ? 'success' : 'danger');
        }
        return Response::redirect(godyar_v4_base_url('/v4/admin/media-optimizer'));
    }

    public function cacheCenter(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) return $guard;
        $base = godyar_v4_storage_path('cache/v4');
        $rows = [];
        foreach (glob($base . '/*.html') ?: [] as $file) {
            $rows[] = ['file' => basename($file), 'size' => (int)filesize($file), 'updated_at' => date('Y-m-d H:i:s', (int)filemtime($file))];
        }
        return $this->theme()->render('pages/admin-cache-center', [
            'locale' => 'ar',
            'rows' => $rows,
            'flash' => godyar_v4_flash_get('cache_center'),
            'seo' => $this->seo()->defaults(['title' => 'V4 Cache Center', 'robots' => 'noindex,nofollow'])
        ]);
    }

    public function clearCache(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) return $guard;
        if (godyar_v4_csrf_valid((string)$request->input('_token', ''))) {
            $count = $this->app->make(ResponseCacheService::class)->clear();
            godyar_v4_flash_set('cache_center', 'تم حذف ' . $count . ' ملف كاش.', 'success');
        }
        return Response::redirect(godyar_v4_base_url('/v4/admin/cache-center'));
    }
}
