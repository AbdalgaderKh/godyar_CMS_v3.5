<?php
declare(strict_types=1);
namespace GodyarV4\Bootstrap;

final class Router
{
    public function __construct(private readonly array $routes) {}

    public function match(Request $request): ?array
    {
        foreach ($this->routes as [$method, $pattern, $handler]) {
            if (strtoupper($method) !== $request->method()) {
                continue;
            }
            if (preg_match($pattern, $request->path(), $matches)) {
                array_shift($matches);
                return ['handler' => $handler, 'params' => $matches];
            }
        }
        return null;
    }
}
