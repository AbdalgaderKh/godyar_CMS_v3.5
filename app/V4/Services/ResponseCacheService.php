<?php
declare(strict_types=1);
namespace GodyarV4\Services;

use GodyarV4\Bootstrap\Response;

final class ResponseCacheService
{
    public function remember(string $key, int $ttl, callable $callback): Response
    {
        $file = $this->fileFor($key);
        $mtime = function_exists('gdy_filemtime') === true ? gdy_filemtime($file) : false;
        if (file_exists($file) === true && is_int($mtime) === true && ($mtime + $ttl) >= time()) {
            $cached = function_exists('gdy_file_get_contents') === true ? gdy_file_get_contents($file) : false;
            return Response::html((string) $cached, 200, ['X-Godyar-Cache' => 'HIT']);
        }
        $response = $callback();
        if ($response instanceof Response) {
            $content = $this->contentOf($response);
            $dir = godyar_v4_storage_path('cache/v4');
            if (function_exists('gdy_mkdir') === true) {
                gdy_mkdir($dir, 0755, true);
            }
            if (function_exists('gdy_file_put_contents') === true) {
                gdy_file_put_contents($file, $content);
            }
            return Response::html($content, 200, ['X-Godyar-Cache' => 'MISS']);
        }
        return $response;
    }

    public function clear(string $prefix = ''): int
    {
        $base = godyar_v4_storage_path('cache/v4');
        if (is_dir($base) !== true) {
            return 0;
        }
        $count = 0;
        foreach (glob($base . '/*.html') ?: [] as $file) {
            if ($prefix !== '' && str_starts_with(basename($file), $prefix) !== true) {
                continue;
            }
            if (function_exists('gdy_unlink') === true && gdy_unlink($file) === true) {
                $count++;
            }
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
