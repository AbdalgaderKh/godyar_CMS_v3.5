<?php

declare(strict_types=1);

namespace GodyarV4\Bootstrap;

final class Router
{
    public function __construct(private array $routes) {}

    public function dispatch(Request $request, App $app): Response
    {
        foreach ($this->routes as $route) {
            [$method, $pattern, $handler] = $route;
            if ($method !== $request->method) {
                continue;
            }
            if (!preg_match($pattern, $request->path, $matches)) {
                continue;
            }
            array_shift($matches);
            [$class, $action] = $handler;
            $controller = $app->make($class);
            return $controller->{$action}($request, ...$matches);
        }
        return $app->make(\GodyarV4\Controllers\ErrorController::class)->notFound($request);
    }
}
