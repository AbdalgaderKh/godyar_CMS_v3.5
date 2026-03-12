<?php
declare(strict_types=1);
namespace GodyarV4\Controllers;

use GodyarV4\Bootstrap\Request;
use GodyarV4\Bootstrap\Response;
use GodyarV4\Services\ContactService;
use GodyarV4\Services\LegacyRedirectService;

final class AdminToolsController extends Controller
{
    public function redirects(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) { return $guard; }
        $items = $this->app->make(LegacyRedirectService::class)->all();
        return $this->theme()->render('pages/admin-redirects', [
            'locale' => 'ar',
            'items' => $items,
            'seo' => $this->seo()->defaults([
                'title' => 'V4 Redirect Manager',
                'description' => 'لوحة مراجعة التحويلات.',
                'canonical' => godyar_v4_base_url('/v4/admin/redirects'),
                'robots' => 'noindex,nofollow',
            ]),
        ]);
    }

    public function contactMessages(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) { return $guard; }
        $items = $this->app->make(ContactService::class)->latest(100);
        return $this->theme()->render('pages/admin-contact-messages', [
            'locale' => 'ar',
            'items' => $items,
            'seo' => $this->seo()->defaults([
                'title' => 'V4 Contact Inbox',
                'description' => 'رسائل التواصل المستلمة من طبقة v4.',
                'canonical' => godyar_v4_base_url('/v4/admin/contact-messages'),
                'robots' => 'noindex,nofollow',
            ]),
        ]);
    }
}
