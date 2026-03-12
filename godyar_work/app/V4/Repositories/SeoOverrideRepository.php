<?php
declare(strict_types=1);
namespace GodyarV4\Repositories;

final class SeoOverrideRepository
{
    private function file(): string { return godyar_v4_storage_path('v4/seo_overrides.json'); }
    private function read(): array { $f = $this->file(); return is_file($f) ? (json_decode((string)file_get_contents($f), true) ?: []) : []; }
    private function write(array $rows): void { $f = $this->file(); if (!is_dir(dirname($f))) @mkdir(dirname($f), 0775, true); file_put_contents($f, json_encode(array_values($rows), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); }
    public function upsert(string $type, string $locale, string $identifier, array $payload, string $actor='system'): void
    {
        $rows = $this->read(); $found = false;
        foreach ($rows as &$row) {
            if (($row['type'] ?? '') === $type && ($row['locale'] ?? '') === $locale && ($row['identifier'] ?? '') === $identifier) {
                $row = array_merge($row, $payload, ['updated_at'=>date('c'), 'updated_by'=>$actor]); $found = true; break;
            }
        }
        unset($row);
        if (!$found) $rows[] = array_merge($payload, ['type'=>$type,'locale'=>$locale,'identifier'=>$identifier,'updated_at'=>date('c'),'updated_by'=>$actor]);
        $this->write($rows);
    }
    public function findFor(string $type, string $locale, array $record): ?array
    {
        $ids = array_filter([(string)($record['id'] ?? ''), (string)($record['slug'] ?? '')]);
        foreach ($this->read() as $row) {
            if (($row['type'] ?? '') === $type && ($row['locale'] ?? '') === $locale && in_array((string)($row['identifier'] ?? ''), $ids, true)) return $row;
        }
        return null;
    }
}
