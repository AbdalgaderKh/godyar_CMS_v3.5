<?php
declare(strict_types=1);
namespace GodyarV4\Plugins\Contracts;

interface PluginInterface
{
    public function key(): string;
    public function name(): string;
    public function version(): string;
    public function boot(): void;
}
