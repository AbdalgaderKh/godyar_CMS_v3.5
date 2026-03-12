<?php
declare(strict_types=1);
namespace GodyarV4\Bootstrap;

final class ErrorHandler
{
    public function __construct(private readonly string $root) {}

    public function register(): void
    {
        set_exception_handler(function (\Throwable $e): void {
            error_log('[GodyarV4] ' . $e->getMessage());
            http_response_code(500);
            echo 'Godyar CMS v4 internal error';
        });
    }
}
