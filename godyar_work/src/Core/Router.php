<?php
namespace App\Core;

final class Router
{
    
    private array $routes = [];

    public function get(string $pattern, callable $handler): self
    {
        $this->routes[] = [$pattern, $handler];
        return $this;
    }

    
    public function post(string $pattern, callable $handler): self
    {
        return $this->get($pattern, $handler);
    }

    public function dispatch(string $path): bool
    {
        foreach ($this->routes as $route) {
            [$pattern, $handler] = $route;
            if (preg_match($pattern, $path, $matches)) {
                $handler($matches);
                return true;
            }
        }
        return false;
    }
}
