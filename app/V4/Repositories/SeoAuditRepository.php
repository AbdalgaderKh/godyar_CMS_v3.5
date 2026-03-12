<?php
declare(strict_types=1);
namespace GodyarV4\Repositories;

final class SeoAuditRepository
{
    private function file(): string { return godyar_v4_storage_path('v4/seo_audit_logs.json'); }
    private function readRows(): array { $f = $this->file(); return is_file($f) ? (json_decode((string)file_get_contents($f), true) ?: []) : []; }
    private function writeRows(array $rows): void { $f = $this->file(); if (!is_dir(dirname($f))) @mkdir(dirname($f), 0775, true); file_put_contents($f, json_encode(array_values($rows), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); }
    public function latest(): array { return $this->readRows(); }
    public function log(array $entry): void
    {
        $rows = $this->readRows();
        array_unshift($rows, array_merge(['logged_at'=>date('c')], $entry));
        $this->writeRows(array_slice($rows, 0, 100));
    }
}
