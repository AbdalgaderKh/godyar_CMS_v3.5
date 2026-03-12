<?php
declare(strict_types=1);
namespace GodyarV4\Plugins;

use GodyarV4\Services\HookBus;

final class Manager
{
    private array $booted = [];

    public function __construct(private readonly Registry $registry, private readonly HookBus $hooks) {}

    public function plugins(): array
    {
        return $this->registry->all();
    }

    public function enabled(): array
    {
        return array_values(array_filter($this->plugins(), static fn(array $row): bool => !empty($row['enabled'])));
    }

    public function setEnabled(string $key, bool $enabled): void
    {
        $state = $this->registry->state();
        $state[$key] = $enabled;
        $this->registry->saveState($state);
    }

    public function bootEnabled(): void
    {
        foreach ($this->enabled() as $plugin) {
            $provider = (string)($plugin['provider'] ?? '');
            $key = (string)($plugin['key'] ?? '');
            $path = (string)($plugin['path'] ?? '');
            if ($key !== '' && isset($this->booted[$key])) {
                continue;
            }
            if ($provider !== '' && class_exists($provider)) {
                $instance = new $provider();
                if (method_exists($instance, 'boot')) {
                    $instance->boot();
                }
            }
            $bootFile = $path !== '' ? $path . '/boot.php' : '';
            if ($bootFile !== '' && is_file($bootFile)) {
                $hooks = $this->hooks;
                require $bootFile;
            }
            if ($key !== '') {
                $this->booted[$key] = true;
            }
        }
    }
}
