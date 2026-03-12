<?php

declare(strict_types=1);

namespace GodyarV4\Services;

final class CacheService
{
    public function get(string $key): ?string
    {
        $file = $this->path($key);
        if (!is_file($file)) {
            return null;
        }
        $payload = @json_decode((string) file_get_contents($file), true);
        if (!is_array($payload) || (($payload['expires_at'] ?? 0) < time())) {
            return null;
        }
        return (string) ($payload['content'] ?? '');
    }

    public function put(string $key, string $content, ?int $ttl = null): void
    {
        $ttl ??= (int) godyar_v4_config('cache.ttl', 300);
        $file = $this->path($key);
        @mkdir(dirname($file), 0775, true);
        file_put_contents($file, json_encode([
            'expires_at' => time() + $ttl,
            'content' => $content,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    private function path(string $key): string
    {
        return rtrim((string) godyar_v4_config('cache.path', godyar_v4_storage_path('cache')), '/') . '/' . ltrim($key, '/') . '.json';
    }
}
