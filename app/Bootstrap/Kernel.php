<?php

declare(strict_types=1);

namespace GodyarV4\Bootstrap;

use GodyarV4\Services\LegacyRedirectService;

final class Kernel
{
    public function __construct(private readonly App $app)
    {
    }

    public function handle(Request $request): Response
    {
        $legacyRedirect = $this->app->make(LegacyRedirectService::class)->match($request);
        if ($legacyRedirect instanceof Response) {
            return $legacyRedirect;
        }

        $match = $this->app->router()->match($request->method, $request->path);

        if ($match === null) {
            return $this->app->make(\GodyarV4\Controllers\ErrorController::class)->notFound($request);
        }

        [$controllerClass, $method] = $match['handler'];
        $controller = $this->app->make($controllerClass);
        return $controller->$method($request, ...$match['params']);
    }
}
