<?php
declare(strict_types=1);
namespace GodyarV4\Plugins;

final class Registry
{
    public function all(): array
    {
        $dir = (string) godyar_v4_config('plugins.directory', godyar_v4_project_root() . '/plugins');
        $state = $this->state();
        $enabledDefault = (array) godyar_v4_config('plugins.enabled_by_default', []);
        $rows = [];
        if (!is_dir($dir)) {
            return $rows;
        }
        foreach (glob($dir . '/*/plugin.php') ?: [] as $manifest) {
            $pluginDir = basename(dirname($manifest));
            $data = require $manifest;
            if (!is_array($data)) {
                continue;
            }
            $key = (string)($data['key'] ?? $pluginDir);
            $rows[] = [
                'key' => $key,
                'name' => (string)($data['name'] ?? $key),
                'version' => (string)($data['version'] ?? '1.0.0'),
                'description' => (string)($data['description'] ?? ''),
                'provider' => (string)($data['provider'] ?? ''),
                'path' => dirname($manifest),
                'enabled' => array_key_exists($key, $state) ? (bool)$state[$key] : in_array($key, $enabledDefault, true),
            ];
        }
        usort($rows, static fn(array $a, array $b): int => strcmp($a['name'], $b['name']));
        return $rows;
    }

    public function state(): array
    {
        $file = (string) godyar_v4_config('plugins.state_file', godyar_v4_storage_path('v4/plugins.json'));
        if (!is_file($file)) {
            return [];
        }
        $json = json_decode((string) file_get_contents($file), true);
        return is_array($json) ? $json : [];
    }

    public function saveState(array $state): void
    {
        $file = (string) godyar_v4_config('plugins.state_file', godyar_v4_storage_path('v4/plugins.json'));
        @mkdir(dirname($file), 0775, true);
        file_put_contents($file, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
