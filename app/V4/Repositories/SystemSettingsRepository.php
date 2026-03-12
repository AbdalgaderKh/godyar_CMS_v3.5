<?php
declare(strict_types=1);
namespace GodyarV4\Repositories;

use PDO;
use Throwable;

final class SystemSettingsRepository
{
    private string $file;

    public function __construct()
    {
        $this->file = godyar_v4_storage_path('v4/runtime_settings.json');
    }

    public function all(): array
    {
        $rows = $this->readFile();
        foreach ($this->readDatabase() as $key => $value) {
            $rows[$key] = $value;
        }
        ksort($rows);
        return $rows;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->readFile();
        if (array_key_exists($key, $file)) {
            return $file[$key];
        }
        $db = $this->readDatabase();
        return $db[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $rows = $this->readFile();
        $rows[$key] = $value;
        @mkdir(dirname($this->file), 0775, true);
        file_put_contents($this->file, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->saveDatabase($key, $value);
    }

    private function readFile(): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $json = json_decode((string) file_get_contents($this->file), true);
        return is_array($json) ? $json : [];
    }

    private function readDatabase(): array
    {
        $pdo = godyar_v4_db();
        if (!$pdo instanceof PDO) {
            return [];
        }
        $tables = [
            ['settings', 'setting_key', 'setting_value'],
            ['settings', 'key', 'value'],
            ['site_settings', 'setting_key', 'setting_value'],
            ['site_settings', 'key', 'value'],
            ['site_settings', 'name', 'value'],
        ];
        foreach ($tables as [$table, $keyCol, $valueCol]) {
            try {
                $stmt = $pdo->query("SELECT {$keyCol} AS skey, {$valueCol} AS svalue FROM {$table}");
                if (!$stmt) {
                    continue;
                }
                $rows = [];
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                    $rows[(string)$row['skey']] = $row['svalue'];
                }
                if ($rows !== []) {
                    return $rows;
                }
            } catch (Throwable) {
            }
        }
        return [];
    }

    private function saveDatabase(string $key, mixed $value): void
    {
        $pdo = godyar_v4_db();
        if (!$pdo instanceof PDO) {
            return;
        }
        $tables = [
            ['settings', 'setting_key', 'setting_value'],
            ['settings', 'key', 'value'],
            ['site_settings', 'setting_key', 'setting_value'],
            ['site_settings', 'key', 'value'],
            ['site_settings', 'name', 'value'],
        ];
        foreach ($tables as [$table, $keyCol, $valueCol]) {
            try {
                $pdo->prepare("DELETE FROM {$table} WHERE {$keyCol} = ?")->execute([$key]);
                $pdo->prepare("INSERT INTO {$table} ({$keyCol}, {$valueCol}) VALUES (?, ?)")->execute([$key, is_scalar($value) ? (string)$value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
                return;
            } catch (Throwable) {
            }
        }
    }
}
