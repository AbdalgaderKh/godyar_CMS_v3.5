<?php

declare(strict_types=1);

namespace GodyarV4\Controllers;

use GodyarV4\Bootstrap\Request;
use GodyarV4\Bootstrap\Response;
use GodyarV4\Services\SeoService;
use GodyarV4\Services\SettingsService;
use GodyarV4\Services\ThemeService;

final class ContactController extends Controller
{
    public function index(Request $request, string $lang): Response
    {
        $identity = $this->app->make(SettingsService::class)->siteIdentity();
        $seo = $this->app->make(SeoService::class)->defaults([
            'title' => 'اتصل بنا | ' . ($identity['site_name'] ?? 'Godyar CMS'),
            'description' => 'تواصل مع فريق الموقع عبر النموذج الموحد في v4.',
            'canonical' => godyar_v4_base_url($request->path),
        ]);
        return $this->app->make(ThemeService::class)->render('pages/contact', compact('seo', 'lang', 'identity'));
    }

    public function send(Request $request, string $lang): Response
    {
        return new Response(json_encode([
            'ok' => true,
            'message' => 'تم تجهيز endpoint الإرسال في v4 وجاهز للربط بجدول contact_messages.',
            'lang' => $lang,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 200, ['Content-Type' => 'application/json; charset=utf-8']);
    }
}
