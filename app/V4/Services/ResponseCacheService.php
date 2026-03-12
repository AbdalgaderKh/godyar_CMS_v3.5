<?php
declare(strict_types=1);
namespace GodyarV4\Services;

use GodyarV4\Bootstrap\Response;

final class ResponseCacheService
{
    public function remember(string $key, int $ttl, callable $callback): Response
    {
        $file = $this->fileFor($key);
        if (is_file($file) && (filemtime($file) + $ttl) >= time()) {
            return Response::html((string) file_get_contents($file), 200, ['X-Godyar-Cache' => 'HIT']);
        }
        $response = $callback();
        if ($response instanceof Response) {
            @mkdir(dirname($file), 0775, true);
            file_put_contents($file, $this->contentOf($response));
            return Response::html($this->contentOf($response), 200, ['X-Godyar-Cache' => 'MISS']);
        }
        return $response;
    }

    public function clear(string $prefix = ''): int
    {
        $base = godyar_v4_storage_path('cache/v4');
        if (!is_dir($base)) {
            return 0;
        }
        $count = 0;
        foreach (glob($base . '/*.html') ?: [] as $file) {
            if ($prefix !== '' && !str_starts_with(basename($file), $prefix)) {
                continue;
            }
            @unlink($file);
            $count++;
        }
        return $count;
    }

    private function fileFor(string $key): string
    {
        return godyar_v4_storage_path('cache/v4/' . hash('sha256', $key) . '.html');
    }

    private function contentOf(Response $response): string
    {
        $ref = new \ReflectionClass($response);
        $prop = $ref->getProperty('content');
        $prop->setAccessible(true);
        return (string) $prop->getValue($response);
    }
}
