<?php
declare(strict_types=1);
namespace GodyarV4\Services;

final class HookBus
{
    private array $listeners = [];

    public function on(string $event, callable $listener): void
    {
        $this->listeners[$event][] = $listener;
    }

    public function dispatch(string $event, array $payload = []): array
    {
        $responses = [];
        foreach ($this->listeners[$event] ?? [] as $listener) {
            $responses[] = $listener($payload);
        }
        return $responses;
    }

    public function render(string $event, array $payload = []): string
    {
        $chunks = [];
        foreach ($this->dispatch($event, $payload) as $response) {
            if (is_string($response) && $response !== '') {
                $chunks[] = $response;
            }
        }
        return implode("\n", $chunks);
    }

    public function summary(): array
    {
        $rows = [];
        foreach ($this->listeners as $event => $items) {
            $rows[] = ['event' => $event, 'count' => count($items)];
        }
        usort($rows, static fn(array $a, array $b): int => strcmp($a['event'], $b['event']));
        return $rows;
    }
}
