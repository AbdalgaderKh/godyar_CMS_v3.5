<?php
declare(strict_types=1);
namespace GodyarV4\Bootstrap;

use GodyarV4\Controllers\ErrorController;
use GodyarV4\Services\LegacyRedirectService;

final class Kernel
{
    public function __construct(private readonly App $app) {}

    public function handle(Request $request): Response
    {
        $redirect = $this->app->make(LegacyRedirectService::class)->match($request->path());
        if ($redirect !== null) {
            return Response::redirect((string)($redirect['to'] ?? '/'), (int)($redirect['status'] ?? 301));
        }

        $matched = $this->app->router()->match($request);
        if ($matched === null) {
            return (new ErrorController($this->app))->notFound($request);
        }

        [$class, $method] = $matched['handler'];
        $controller = new $class($this->app);
        return $controller->$method($request, ...$matched['params']);
    }
}
