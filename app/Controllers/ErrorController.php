<?php

declare(strict_types=1);

namespace GodyarV4\Controllers;

use GodyarV4\Bootstrap\Request;
use GodyarV4\Bootstrap\Response;
use GodyarV4\Services\SeoService;
use GodyarV4\Services\ThemeService;

final class ErrorController extends Controller
{
    public function notFound(Request $request): Response
    {
        $seo = $this->app->make(SeoService::class)->defaults([
            'title' => '404 | Godyar',
            'robots' => 'noindex,nofollow',
        ]);
        return $this->app->make(ThemeService::class)->render('pages/404', compact('seo'), 404);
    }
}
