<?php
declare(strict_types=1);
namespace GodyarV4\Services;

use GodyarV4\Repositories\SystemSettingsRepository;

final class ThemeSettingsService
{
    private string $file;

    public function __construct(private readonly SystemSettingsRepository $settings)
    {
        $this->file = godyar_v4_storage_path('v4/theme_customizer.json');
    }

    public function defaults(): array
    {
        return [
            'primary_color' => '#0f766e',
            'background_color' => '#f7f9fc',
            'text_color' => '#152033',
            'card_color' => '#ffffff',
            'muted_color' => '#61708a',
            'border_color' => '#dce5f1',
            'shell_width' => '1120px',
            'radius' => '20px',
            'font_family' => 'Cairo, Arial, sans-serif',
            'dark_mode_default' => 'light',
            'header_layout' => 'default',
            'footer_layout' => 'default',
            'show_breaking_bar' => '1',
            'show_reading_progress' => '1',
            'show_sticky_header' => '1',
            'compact_cards' => '0',
            'logo_text' => '',
            'footer_note' => '',
            'breaking_text' => 'واجهة الثيمات المستقرة مفعلة الآن مع دعم الوضع الليلي وشريط القراءة وتحسينات الأداء.',
        ];
    }

    public function all(): array
    {
        return array_merge($this->defaults(), $this->read());
    }

    public function save(array $input): void
    {
        $data = $this->defaults();
        foreach ($data as $key => $default) {
            if (in_array($key, ['show_breaking_bar', 'show_reading_progress', 'show_sticky_header', 'compact_cards'], true)) {
                $data[$key] = !empty($input[$key]) ? '1' : '0';
                continue;
            }
            if (isset($input[$key])) {
                $value = trim((string) $input[$key]);
                $data[$key] = $this->sanitize($key, $value, (string) $default);
            }
        }
        @mkdir(dirname($this->file), 0775, true);
        file_put_contents($this->file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        foreach ($data as $key => $value) {
            $this->settings->set('theme_' . $key, $value);
        }
    }

    private function read(): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $json = json_decode((string) file_get_contents($this->file), true);
        return is_array($json) ? $json : [];
    }

    private function sanitize(string $key, string $value, string $fallback): string
    {
        if (in_array($key, ['primary_color', 'background_color', 'text_color', 'card_color', 'muted_color', 'border_color'], true)) {
            return preg_match('/^#[0-9a-fA-F]{3,8}$/', $value) ? $value : $fallback;
        }
        if ($key === 'shell_width') {
            return preg_match('/^[0-9]{3,4}px$/', $value) ? $value : $fallback;
        }
        if ($key === 'radius') {
            return preg_match('/^[0-9]{1,3}px$/', $value) ? $value : $fallback;
        }
        if ($key === 'dark_mode_default') {
            return in_array($value, ['light', 'dark'], true) ? $value : $fallback;
        }
        if (in_array($key, ['header_layout', 'footer_layout'], true)) {
            return in_array($value, ['default', 'centered', 'split'], true) ? $value : $fallback;
        }
        return mb_substr($value, 0, 500);
    }
}
