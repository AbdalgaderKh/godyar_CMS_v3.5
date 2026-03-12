<?php
namespace App\Core;

final class FrontendRenderer
{
    private string $rootDir;
    private string $basePrefix;

    public function __construct(string $rootDir, string $basePrefix = '')
    {
        $this->rootDir = rtrim($rootDir, '/');
        $this->basePrefix = rtrim($basePrefix, '/');
    }

    
    public function render(string $viewRelative, array $data = [], array $meta = []): void
    {
        $header = is_file($this->rootDir . '/frontend/templates/header.php') ? $this->rootDir . '/frontend/templates/header.php' : $this->rootDir . '/frontend/views/partials/header.php';
        $footer = is_file($this->rootDir . '/frontend/templates/footer.php') ? $this->rootDir . '/frontend/templates/footer.php' : $this->rootDir . '/frontend/views/partials/footer.php';
        $view = $this->rootDir . '/' .ltrim($viewRelative, '/');

        if (is_file($view) === false) {
            http_response_code(500);
            echo 'View not found.';
            exit;
        }

        if (function_exists('gdy_boot_legacy_front_plugins')) {
            gdy_boot_legacy_front_plugins();
        }

        $data['basePrefix'] = $data['basePrefix'] ?? $this->basePrefix;
        if (function_exists('gdy_shared_view_data')) {
            $data = gdy_shared_view_data($data);
        } else {
            $resolvedBase = $data['baseUrl'] ?? (function_exists('base_url') ? base_url() : $this->basePrefix);
            $resolvedBase = rtrim((string)$resolvedBase, '/');
            $lang = $data['pageLang'] ?? (function_exists('gdy_current_lang') ? gdy_current_lang() : 'ar');
            $lang = in_array((string)$lang, ['ar','en','fr'], true) ? (string)$lang : 'ar';
            $data['baseUrl'] = $resolvedBase;
            $data['rootUrl'] = $data['rootUrl'] ?? $resolvedBase;
            $data['pageLang'] = $lang;
            $data['navBaseUrl'] = $data['navBaseUrl'] ?? (function_exists('gdy_lang_prefix') ? ($resolvedBase . gdy_lang_prefix($lang)) : $resolvedBase);
            $data['homeUrl'] = $data['homeUrl'] ?? (function_exists('gdy_route_home_url') ? gdy_route_home_url($lang) : ($resolvedBase . '/'));
        }

        if (!isset($data['meta_title']) && isset($data['pageSeo']['title'])) {
            $data['meta_title'] = (string)$data['pageSeo']['title'];
        }
        if (!isset($data['meta_description']) && isset($data['pageSeo']['description'])) {
            $data['meta_description'] = (string)$data['pageSeo']['description'];
        }
        if (!isset($data['canonical_url']) && isset($data['pageSeo']['canonical'])) {
            $data['canonical_url'] = (string)$data['pageSeo']['canonical'];
        }

        if (function_exists('g_apply_filters')) {
            try { $data = g_apply_filters('frontend.renderer.data', $data, $viewRelative, $meta); } catch (\Throwable $e) {}
        }

        $vars = array_merge($meta, $data);

        (static function (string $header, string $view, string $footer, array $vars): void {
            foreach ($vars as $k => $v) {
                if (is_string($k) && preg_match('~^[a-zA-Z_][a-zA-Z0-9_]*$~', $k)) {
                    ${$k} = $v;
                }
            }
            if (is_file($header) === true) {
                require $header;
            }
            require $view;
            if (is_file($footer) === true) {
                require $footer;
            }
        })($header, $view, $footer, $vars);

        exit;
    }
}
