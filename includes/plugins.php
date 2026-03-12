<?php

if (!function_exists('gdy_plugins_dir')) {
function gdy_plugins_dir(): string {
    $root = defined('ROOT_PATH') ? (string)ROOT_PATH : (realpath(__DIR__ . '/..') ?: dirname(__DIR__));
    $p1 = $root . '/plugins';
    if (is_dir($p1)) return $p1;
    $p2 = $root . '/admin/plugins';
    if (is_dir($p2)) return $p2;
    return $p1;
}
}

interface GodyarPluginInterface
{
    
    public function register(PluginManager $pm): void;
}

final class PluginManager
{
    private static ?PluginManager $instance = null;

    
    private array $plugins = [];

    
    private array $meta = [];

    
    private array $hooks = [];

    private function __construct() {}

    public static function instance(): PluginManager
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    
    public function loadAll(?string $baseDir = null): void
    {
        $base = $baseDir ?: gdy_plugins_dir();
        $baseReal = realpath($base) ?: $base;
        if (!is_dir($baseReal)) {
            return;
        }

        $dirs = scandir($baseReal);
        if (!is_array($dirs)) {
            return;
        }

        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            
            if (!preg_match('~^[A-Za-z0-9_\-]{1,64}$~', (string)$dir)) {
                continue;
            }

            $pluginPath = rtrim($baseReal, '/\\') . '/' . $dir;

            
            if (is_link($pluginPath)) {
                continue;
            }

            $pluginPathReal = realpath($pluginPath);
            if ($pluginPathReal === false || strpos($pluginPathReal, rtrim($baseReal, '/\\') .DIRECTORY_SEPARATOR) !== 0) {
                continue;
            }

            if (!is_dir($pluginPathReal)) {
                continue;
            }

            $slug = $dir;

            
            $meta = [
                'slug' => $slug,
                'enabled' => true,
            ];
            $metaFile = $pluginPathReal . '/plugin.json';
            if (!is_file($metaFile)) {
                
                continue;
            }

            $json = gdy_file_get_contents($metaFile);
            if (is_string($json) && $json !== '') {
                $decoded = json_decode($json, true);
                if (is_array($decoded)) {
                    $meta = array_merge($meta, $decoded);
                }
            }

            
            $enabled = $meta['enabled'] ?? true;
            if (is_string($enabled)) {
                $enabled = in_array(strtolower($enabled), ['1','true','yes','on'], true);
            } else {
                $enabled = (bool)$enabled;
            }
            if (!$enabled) {
                continue; 
            }

            $main = $pluginPathReal . '/Plugin.php';
            if (!is_file($main)) {
                continue;
            }

            try {
                
                $instance = include $main;

                if ($instance instanceof GodyarPluginInterface) {
                    
                    $this->maybeMigratePlugin($slug, $pluginPathReal, $meta, $instance);

                    $this->meta[$slug] = $meta;
                    $this->plugins[$slug] = $instance;
                    $instance->register($this);
                }
            } catch (\Throwable $e) {
                error_log('[Godyar Plugin] Failed to load plugin ' . $slug . ': ' . $e->getMessage());
            }
        }
    }

    
    public function runMigrationsFor(string $slug, ?string $baseDir = null, bool $force = false): array
    {
        $slug = preg_replace('~[^A-Za-z0-9_\-]~', '', $slug) ?: '';
        if ($slug === '') {
            return ['ok' => false, 'message' => 'Invalid slug'];
        }

        $base = $baseDir ?: gdy_plugins_dir();
        $baseReal = realpath($base) ?: $base;
        $pluginPath = rtrim($baseReal, '/\\') .DIRECTORY_SEPARATOR . $slug;
        if (is_link($pluginPath)) {
            return ['ok' => false, 'message' => 'Symlinked plugin folders are not allowed'];
        }
        $pluginPathReal = realpath($pluginPath);
        if ($pluginPathReal === false || strpos($pluginPathReal, rtrim($baseReal, '/\\') .DIRECTORY_SEPARATOR) !== 0) {
            return ['ok' => false, 'message' => 'Invalid plugin path'];
        }
        if (!is_dir($pluginPathReal)) {
            return ['ok' => false, 'message' => 'Plugin folder not found'];
        }

        $meta = ['slug' => $slug, 'enabled' => true];
        $metaFile = $pluginPathReal . '/plugin.json';
        if (!is_file($metaFile)) {
            return ['ok' => false, 'message' => 'plugin.json not found'];
        }
        $json = gdy_file_get_contents($metaFile);
        if (is_string($json) && $json !== '') {
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                $meta = array_merge($meta, $decoded);
            }
        }

        $target = (int)($meta['schema_version'] ?? 0);
        if ($target <= 0) {
            return ['ok' => false, 'message' => 'No schema_version configured'];
        }

        $main = $pluginPathReal . '/Plugin.php';
        if (!is_file($main)) {
            return ['ok' => false, 'message' => 'Plugin.php not found'];
        }

        $pdo = function_exists('gdy_pdo_safe') ? gdy_pdo_safe() : null;
        if (($pdo instanceof \PDO) === false) {
            return ['ok' => false, 'message' => 'DB connection not available'];
        }

        try {
            $instance = include $main;
            if (($instance instanceof GodyarPluginInterface) === false) {
                return ['ok' => false, 'message' => 'Plugin does not implement interface'];
            }

            $this->ensureSchemaTable($pdo);
            $installed = $this->getInstalledSchemaVersion($pdo, $slug);
            $from = $force ? 0 : $installed;

            
            if (method_exists($instance, 'migrate')) {
                $ref = new \ReflectionMethod($instance, 'migrate');
                $argc = $ref->getNumberOfParameters();
                $args = [$pdo, $from, $target, $pluginPath, $meta];
                $ref->invokeArgs($instance, array_slice($args, 0, $argc));
                $this->setInstalledSchemaVersion($pdo, $slug, $target);
                return ['ok' => true, 'message' => 'Migrations executed', 'from' => $from, 'to' => $target];
            }

            
            if ($from === 0 && method_exists($instance, 'install')) {
                $ref = new \ReflectionMethod($instance, 'install');
                $argc = $ref->getNumberOfParameters();
                $args = [$pdo, $pluginPathReal, $meta];
                $ref->invokeArgs($instance, array_slice($args, 0, $argc));
                $this->setInstalledSchemaVersion($pdo, $slug, $target);
                return ['ok' => true, 'message' => 'Install executed', 'from' => 0, 'to' => $target];
            }

            return ['ok' => false, 'message' => 'No migrate/install method found'];
        } catch (\Throwable $e) {
            error_log('[Godyar Plugin] manual migrate failed for ' . $slug . ': ' . $e->getMessage());
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    
    public function runMigrationsForAll(?string $baseDir = null, bool $force = false): array
    {
        $base = $baseDir ?: gdy_plugins_dir();
        if (!is_dir($base)) return [];

        $out = [];
        $dirs = scandir($base);
        if (!is_array($dirs)) return [];

        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') continue;
            if (!is_dir($base . '/' . $dir)) continue;
            $res = $this->runMigrationsFor($dir, $base, $force);
            $out[] = array_merge(['slug' => $dir], $res);
        }
        return $out;
    }

    
    

private function maybeMigratePlugin(string $slug, string $pluginPath, array $meta, GodyarPluginInterface $instance): void
{
    $target = (int)($meta['schema_version'] ?? 0);
    if ($target <= 0) {
        return;
    }

    $pdo = function_exists('gdy_pdo_safe') ? gdy_pdo_safe() : null;
    if (($pdo instanceof \PDO) === false) {
        return;
    }

    try {
        $this->ensureSchemaTable($pdo);
        $from = $this->getInstalledSchemaVersion($pdo, $slug);

        if ($target <= $from) {
            return; 
        }

        
        if (method_exists($instance, 'migrate')) {
            $ref = new \ReflectionMethod($instance, 'migrate');
            $argc = $ref->getNumberOfParameters();
            if ($argc >= 2) {
                
                $args = [$pdo, $from, $target, $pluginPath, $meta];
                $ref->invokeArgs($instance, array_slice($args, 0, $argc));
            }
            $this->setInstalledSchemaVersion($pdo, $slug, $target);
            return;
        }

        
        if ($from === 0 && method_exists($instance, 'install')) {
            $ref = new \ReflectionMethod($instance, 'install');
            $argc = $ref->getNumberOfParameters();
            $args = [$pdo, $pluginPath, $meta];
            $ref->invokeArgs($instance, array_slice($args, 0, $argc));
            $this->setInstalledSchemaVersion($pdo, $slug, $target);
        }
    } catch (\Throwable $e) {
        
        error_log('[Godyar Plugin] migrate failed for ' . $slug . ': ' . $e->getMessage());
    }
}

private function ensureSchemaTable(\PDO $pdo): void
{
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS godyar_plugin_schema (
            slug VARCHAR(120) NOT NULL PRIMARY KEY,
            schema_version INT NOT NULL DEFAULT 0,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci
    ");
}

private function getInstalledSchemaVersion(\PDO $pdo, string $slug): int
{
    try {
        $st = $pdo->prepare("SELECT schema_version FROM godyar_plugin_schema WHERE slug=? LIMIT 1");
        $st->execute([$slug]);
        $v = $st->fetchColumn();
        return is_numeric($v) ? (int)$v : 0;
    } catch (\Throwable $e) {
        return 0;
    }
}

private function setInstalledSchemaVersion(\PDO $pdo, string $slug, int $version): void
{
    $version = (int)$version;
    try {
        $st = $pdo->prepare("
            INSERT INTO godyar_plugin_schema (slug, schema_version)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE schema_version = VALUES(schema_version)
        ");
        $st->execute([$slug, $version]);
    } catch (\Throwable $e) {
        
    }
}

public function addHook(string $hook, callable $callback, int $priority = 10): void
    {
        $this->hooks[$hook][] = [$priority, $callback];
        usort($this->hooks[$hook], static function ($a, $b) {
            return $a[0] <=> $b[0];
        });
    }

    
    public function doHook(string $hook, &...$args): void
    {
        if (empty($this->hooks[$hook])) {
            return;
        }

        foreach ($this->hooks[$hook] as [$priority, $cb]) {
            try {
                $cb(...$args);
            } catch (\Throwable $e) {
                error_log('[Godyar Plugin] Error in hook ' . $hook . ': ' . $e->getMessage());
            }
        }
    }

    
    public function filter(string $hook, $value, ... $args)
    {
        if (empty($this->hooks[$hook])) {
            return $value;
        }

        $result = $value;

        foreach ($this->hooks[$hook] as [$priority, $cb]) {
            try {
                $result = $cb($result, ... $args);
            } catch (\Throwable $e) {
                error_log('[Godyar Plugin] Error in filter ' . $hook . ': ' . $e->getMessage());
            }
        }

        return $result;
    }

    
    public function all(): array
    {
        return $this->plugins;
    }

    
    public function meta(?string $slug = null): array
    {
        if ($slug === null) {
            return $this->meta;
        }
        return $this->meta[$slug] ?? [];
    }
}

if (!function_exists('g_plugins')) {
    function g_plugins(): PluginManager
    {
        return PluginManager::instance();
    }
}

if (!function_exists('g_do_hook')) {
    
    function g_do_hook(string $hook, &...$args): void
    {
        PluginManager::instance()->doHook($hook, ... $args);
    }
}

if (!function_exists('g_apply_filters')) {
    
    function g_apply_filters(string $hook, $value, ... $args)
    {
        return PluginManager::instance()->filter($hook, $value, ... $args);
    }
}
