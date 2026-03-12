<?php
declare(strict_types=1);
namespace GodyarV4\Services;

use GodyarV4\Repositories\RedirectRepository;

final class LegacyRedirectService
{
    public function match(string $path): ?array
    {
        $path = rtrim($path, '/') ?: '/';
        $dynamic = (new RedirectRepository())->findByPath($path);
        if ($dynamic) {
            (new RedirectRepository())->incrementHits($path);
            return [
                'to' => $this->toAbsolute((string)($dynamic['new_path'] ?? '/')),
                'status' => (int)($dynamic['status_code'] ?? 301),
            ];
        }
        $map = godyar_v4_config('legacy_redirects', []);
        if (isset($map[$path])) {
            return ['to' => $this->legacyToAbsolute((string)$map[$path]), 'status' => 301];
        }
        return null;
    }

    private function legacyToAbsolute(string $target): string
    {
        if ($target === '/') return godyar_v4_base_url('/');
        return godyar_v4_base_url('/ar/' . ltrim($target, '/'));
    }
    private function toAbsolute(string $target): string
    {
        if (preg_match('#^https?://#i', $target)) return $target;
        return godyar_v4_base_url($target);
    }
}
