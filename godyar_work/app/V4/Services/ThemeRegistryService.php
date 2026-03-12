<?php
declare(strict_types=1);
namespace GodyarV4\Services;

use GodyarV4\Repositories\SystemSettingsRepository;

final class ThemeRegistryService
{
    public function __construct(private readonly SystemSettingsRepository $settings) {}

    public function themes(): array
    {
        $base = godyar_v4_project_root() . '/themes';
        $activeKey = (string)$this->settings->get('v4_active_theme', godyar_v4_config('theme.active', 'default'));
        $rows = [];
        foreach (glob($base . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $key = basename($dir);
            $manifest = is_file($dir . '/theme.json') ? json_decode((string) file_get_contents($dir . '/theme.json'), true) : [];
            $css = basename((string)($manifest['site_theme'] ?? ('theme-' . $key . '.css')));
            $rows[] = [
                'key' => $key,
                'name' => (string)($manifest['name'] ?? ucfirst($key)),
                'version' => (string)($manifest['version'] ?? '1.0.0'),
                'author' => (string)($manifest['author'] ?? 'Godyar'),
                'description' => (string)($manifest['description'] ?? ''),
                'site_theme' => $css,
                'active' => $key === $activeKey,
            ];
        }
        usort($rows, static fn(array $a, array $b): int => strcmp($a['name'], $b['name']));
        return $rows;
    }

    public function activate(string $key): bool
    {
        foreach ($this->themes() as $theme) {
            if (($theme['key'] ?? '') !== $key) {
                continue;
            }
            $this->settings->set('v4_active_theme', $theme['key']);
            $this->settings->set('v4_active_theme_css', $theme['site_theme']);
            @mkdir(godyar_v4_storage_path('v4'), 0775, true);
            file_put_contents(
                godyar_v4_storage_path('v4/theme_state.json'),
                json_encode(['theme' => $theme['key'], 'css' => $theme['site_theme']], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
            return true;
        }
        return false;
    }
}
