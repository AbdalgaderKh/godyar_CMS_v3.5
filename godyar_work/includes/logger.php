<?php

if (!function_exists('gdy_log_path')) {
    function gdy_log_path(string $name = 'app.log'): string
    {
        $base = defined('ABSPATH') ? ABSPATH : (__DIR__ . '/../');
        $dir  = rtrim($base, '/\\') . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir . '/' . $name;
    }
}

if (!function_exists('gdy_log_enabled')) {
    function gdy_log_enabled(): bool
    {
        $v = getenv('GDY_LOG_ENABLED');
        if ($v === false) return true;
        return !in_array(strtolower((string)$v), ['0','false','off','no'], true);
    }
}

if (!function_exists('gdy_log_level_rank')) {
    function gdy_log_level_rank(string $level): int
    {
        return match (strtolower($level)) {
            'debug' => 10,
            'info' => 20,
            'warning', 'warn' => 30,
            'error' => 40,
            default => 20,
        };
    }
}

if (!function_exists('gdy_log')) {
    function gdy_log(string $level, string $message, array $context = [], string $file = 'app.log'): void
    {
        if (!gdy_log_enabled()) return;

        $min = getenv('GDY_LOG_LEVEL');
        $min = $min === false ? 'info' : (string)$min;
        if (gdy_log_level_rank($level) < gdy_log_level_rank($min)) return;

        $row = [
            'ts' => gmdate('c'),
            'level' => strtolower($level),
            'msg' => $message,
            'ctx' => $context,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'uri' => $_SERVER['REQUEST_URI'] ?? null,
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ];

        $line = json_encode($row, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . "\n";

        
        if (function_exists('gdy_file_put_contents')) {
            @gdy_file_put_contents(gdy_log_path($file), $line, FILE_APPEND);
        } else {
            $p = gdy_log_path($file);
            @file_put_contents($p, $line, FILE_APPEND);
        }
    }
}

if (!function_exists('gdy_register_error_handlers')) {
    function gdy_register_error_handlers(): void
    {
        
        $phpLog = gdy_log_path('php-errors.log');
        @ini_set('log_errors', '1');
        @ini_set('error_log', $phpLog);

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            
            if (!(error_reporting() & $severity)) {
                return false;
            }
            gdy_log('error', 'PHP error: ' . $message, [
                'severity' => $severity,
                'file' => $file,
                'line' => $line,
            ], 'php-errors.log');
            
            return false;
        });

        set_exception_handler(static function (Throwable $e): void {
            gdy_log('error', 'Uncaught exception: ' . $e->getMessage(), [
                'type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => substr($e->getTraceAsString(), 0, 4000),
            ], 'php-errors.log');

            
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: text/plain; charset=utf-8');
            }
            echo "حدث خطأ غير متوقع.\n";
        });
    }
}
