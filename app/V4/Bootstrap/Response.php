<?php
declare(strict_types=1);
namespace GodyarV4\Bootstrap;

final class Response
{
    public function __construct(
        private readonly string $content,
        private readonly int $status = 200,
        private readonly array $headers = []
    ) {}

    public static function html(string $content, int $status = 200, array $headers = []): self
    {
        $headers['Content-Type'] = $headers['Content-Type'] ?? 'text/html; charset=UTF-8';
        return new self($content, $status, $headers);
    }

    public static function xml(string $content, int $status = 200): self
    {
        return new self($content, $status, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public static function redirect(string $to, int $status = 302): self
    {
        return new self('', $status, ['Location' => $to]);
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value, true);
        }
        echo $this->content;
    }
}
