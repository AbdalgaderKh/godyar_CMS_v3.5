<?php
declare(strict_types=1);
namespace GodyarV4\Services;

use GodyarV4\Plugins\Manager;
use GodyarV4\Plugins\Registry;

final class PluginService
{
    private ?Manager $manager = null;

    public function __construct(private readonly HookBus $hooks) {}

    public function manager(): Manager
    {
        if ($this->manager === null) {
            $this->manager = new Manager(new Registry(), $this->hooks);
        }
        return $this->manager;
    }

    public function all(): array
    {
        return $this->manager()->plugins();
    }

    public function enabled(): array
    {
        return $this->manager()->enabled();
    }

    public function toggle(string $key, bool $enabled): void
    {
        $this->manager()->setEnabled($key, $enabled);
    }

    public function boot(): void
    {
        $this->manager()->bootEnabled();
    }
}
