<?php
declare(strict_types=1);
namespace GodyarV4\Services;

final class SettingsService
{
    private ?array $cache = null;

    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $settings = [
            'site_name' => 'Godyar',
            'site_tagline' => 'News Platform',
            'contact_email' => 'info@example.com',
            'logo_header' => '',
            'logo_footer' => '',
        ];

        $pdo = godyar_v4_db();
        if ($pdo) {
            foreach (['settings', 'site_settings'] as $table) {
                try {
                    $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
                    if (!$stmt || !$stmt->fetchColumn()) { continue; }
                    $rows = $pdo->query("SELECT `key`, `value` FROM {$table}")->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                    foreach ($rows as $row) {
                        $settings[(string) $row['key']] = $row['value'];
                    }
                } catch (\Throwable) {}
            }
        }

        return $this->cache = $settings;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();
        return $all[$key] ?? $default;
    }
}
