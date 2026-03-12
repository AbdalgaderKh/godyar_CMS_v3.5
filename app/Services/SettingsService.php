<?php

declare(strict_types=1);

namespace GodyarV4\Services;

use GodyarV4\Support\LegacyDbBridge;

final class SettingsService
{
    /** @var array<string,mixed> */
    private array $cache = [];

    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        if (LegacyDbBridge::tableExists('settings')) {
            $row = LegacyDbBridge::fetchOne(
                'SELECT setting_value FROM settings WHERE setting_key = :key LIMIT 1',
                ['key' => $key]
            );
            if ($row && array_key_exists('setting_value', $row)) {
                return $this->cache[$key] = $row['setting_value'];
            }
        }

        if (LegacyDbBridge::tableExists('site_settings')) {
            $row = LegacyDbBridge::fetchOne(
                'SELECT value FROM site_settings WHERE key_name = :key LIMIT 1',
                ['key' => $key]
            );
            if ($row && array_key_exists('value', $row)) {
                return $this->cache[$key] = $row['value'];
            }
        }

        return $this->cache[$key] = $default;
    }

    /** @return array<string,mixed> */
    public function siteIdentity(): array
    {
        return [
            'site_name' => $this->get('site_name', $this->get('site.name', 'Godyar CMS')),
            'site_description' => $this->get('site_description', $this->get('site.desc', 'Godyar CMS v4')),
            'site_logo' => $this->get('site_logo', $this->get('site.logo', '')),
            'site_email' => $this->get('site_email', $this->get('site.email', '')),
            'site_phone' => $this->get('site.phone', ''),
            'site_url' => $this->get('site.url', $this->get('site_url', godyar_v4_base_url('/'))),
            'front_theme' => $this->get('front_theme', $this->get('site_theme', 'assets/css/themes/theme-blue.css')),
        ];
    }
}
