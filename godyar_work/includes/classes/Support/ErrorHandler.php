<?php

namespace App\Support;

class ErrorHandler
{
    private static function ensureLogPath(): string
    {
        $root = defined('ROOT_PATH') ? (string)ROOT_PATH : dirname(__DIR__, 4);
        $dir = rtrim($root, '/\\') . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir . '/php.log';
    }

    public static function register(bool $debug = false): void
    {
        $logFile = self::ensureLogPath();
        if ($logFile) {
            ini_set('log_errors', '1');
            ini_set('error_log', $logFile);
        }

        if ($debug === true) {
            
            error_reporting(E_ALL & ~E_DEPRECATED);
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
        } else {
            error_reporting(E_ALL & ~E_DEPRECATED);
            ini_set('display_errors', '0');
            ini_set('display_startup_errors', '0');
        }

        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
    }

    public static function handleException($e): void
    {
        error_log('Uncaught: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        http_response_code(500);
        echo $GLOBALS['APP_DEBUG'] ?? false
            ? 'Exception: ' .htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
            : 'حدث خطأ غير متوقع. الرجاء المحاولة لاحقًا.';
    }

    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        
        if ((error_reporting() & $errno) === false) {
            return false;
        }
        error_log("PHP Error [$errno]: $errstr in $errfile:$errline");
        if ($errno === E_USER_ERROR) {
            http_response_code(500);
            echo 'حدث خطأ غير متوقع. الرجاء المحاولة لاحقًا.';
            return true;
        }
        return false; 
    }
}
