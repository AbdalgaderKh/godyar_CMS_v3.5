<?php

spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'Godyar\\' => [__DIR__ . '/classes/', __DIR__ . '/'],
        'App\\'   => [__DIR__ . '/../src/'],
    ];

    foreach ($prefixes as $prefix => $baseDirs) {
        $len = strlen($prefix);
        if (strncmp($class, $prefix, $len) !== 0) {
            continue;
        }

        $relativeClass = substr($class, $len);
        $relativePath  = str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

        foreach ((array)$baseDirs as $baseDir) {
            $file = rtrim((string)$baseDir, '/\\') . DIRECTORY_SEPARATOR . $relativePath;
            if (is_file($file)) {
                require_once $file;
                return;
            }
        }

        return;
    }
});
