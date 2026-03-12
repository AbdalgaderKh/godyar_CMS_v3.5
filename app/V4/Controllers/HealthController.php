<?php
declare(strict_types=1);
namespace GodyarV4\Controllers;

use GodyarV4\Bootstrap\Request;
use GodyarV4\Bootstrap\Response;
use GodyarV4\Services\HealthCheckService;
use GodyarV4\Services\LegacyRedirectService;

final class HealthController extends Controller
{
    public function health(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) { return $guard; }
        $summary = $this->app->make(HealthCheckService::class)->summary();
        return $this->theme()->render('pages/health', [
            'locale' => 'ar',
            'summary' => $summary,
            'seo' => $this->seo()->defaults([
                'title' => 'V4 Health Check',
                'description' => 'فحص جاهزية Godyar CMS v4.',
                'canonical' => godyar_v4_base_url('/v4/health'),
                'robots' => 'noindex,nofollow',
            ]),
        ]);
    }

    public function redirects(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) { return $guard; }
        $items = $this->app->make(LegacyRedirectService::class)->all();
        return $this->theme()->render('pages/redirects', [
            'locale' => 'ar',
            'items' => $items,
            'seo' => $this->seo()->defaults([
                'title' => 'V4 Redirect Center',
                'description' => 'مراجعة التحويلات القديمة في Godyar CMS v4.',
                'canonical' => godyar_v4_base_url('/v4/redirects'),
                'robots' => 'noindex,nofollow',
            ]),
        ]);
    }
}
