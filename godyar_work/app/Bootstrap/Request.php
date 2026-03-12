<?php

declare(strict_types=1);

namespace GodyarV4\Bootstrap;

final class Request
{
    public function __construct(
        public readonly string $method,
        public readonly string $uri,
        public readonly string $path,
        public readonly array $get,
        public readonly array $post,
        public readonly array $server,
        public readonly array $cookies,
    ) {}

    public static function capture(): self
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = (string) (parse_url($uri, PHP_URL_PATH) ?: '/');
        return new self(
            strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            $uri,
            $path,
            $_GET,
            $_POST,
            $_SERVER,
            $_COOKIE,
        );
    }
}
