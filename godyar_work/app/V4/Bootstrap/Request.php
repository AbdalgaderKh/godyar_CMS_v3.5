<?php
declare(strict_types=1);
namespace GodyarV4\Bootstrap;

final class Request
{
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query = [],
        private readonly array $post = [],
        private readonly array $server = []
    ) {}

    public static function capture(): self
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        return new self(
            strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            $path,
            $_GET,
            $_POST,
            $_SERVER
        );
    }

    public function method(): string { return $this->method; }
    public function path(): string { return $this->path; }
    public function query(string $key, mixed $default = null): mixed { return $this->query[$key] ?? $default; }
    public function input(string $key, mixed $default = null): mixed { return $this->post[$key] ?? $default; }
    public function all(): array { return $this->post; }
    public function server(string $key, mixed $default = null): mixed { return $this->server[$key] ?? $default; }
}
