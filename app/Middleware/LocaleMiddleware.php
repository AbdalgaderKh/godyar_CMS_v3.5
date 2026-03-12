<?php

declare(strict_types=1);

namespace GodyarV4\Middleware;

use GodyarV4\Bootstrap\App;
use GodyarV4\Bootstrap\Request;

final class LocaleMiddleware
{
    public function __construct(private App $app) {}

    public function handle(Request $request): Request
    {
        $service = $this->app->make(\GodyarV4\Services\I18nService::class);
        $service->bootFromRequest($request);
        return $request;
    }
}
