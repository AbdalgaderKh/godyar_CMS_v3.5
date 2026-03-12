<?php

declare(strict_types=1);

namespace GodyarV4\Bootstrap;

use Throwable;

final class ErrorHandler
{
    public function __construct(private string $root) {}

    public function register(): void
    {
        set_exception_handler(function (Throwable $e): void {
            $logFile = $this->root . '/storage/logs/app_v4.log';
            @file_put_contents($logFile, '[' . date('c') . '] ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL . PHP_EOL, FILE_APPEND);
            http_response_code(500);
            echo 'Godyar CMS v4 skeleton error.';
        });
    }
}
