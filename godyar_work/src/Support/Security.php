<?php
namespace App\Support;

final class Security
{
    public static function headers(): void
    {
        if (headers_sent()) {
            return;
        }

        
        header_remove('X-Powered-By');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

        
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        if (empty($_SESSION['csp_nonce']) || !is_string($_SESSION['csp_nonce'])) {
            $_SESSION['csp_nonce'] = base64_encode(random_bytes(16));
        }

        $nonce = (string)$_SESSION['csp_nonce'];
        $GLOBALS['cspNonce'] = $nonce;
        $GLOBALS['gdy_csp_nonce'] = $nonce;

        $strict = getenv('CSP_STRICT') === '1' || strtolower((string)getenv('CSP_STRICT')) === 'true';
        if (!$strict) {
            return;
        }

        $origin = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https:' : 'http:';

        $csp = [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
            "img-src 'self' data: https:",
            "font-src 'self' data: https:",
            "style-src 'self' 'unsafe-inline' https:",
            "script-src 'self' 'nonce-{$nonce}' https:",
            "connect-src 'self' https:",
            "object-src 'none'",
        ];

        header('Content-Security-Policy: ' . implode('; ', $csp));
    }

    public static function nonce(): string
    {
        if (!empty($GLOBALS['cspNonce'])) {
            return (string)$GLOBALS['cspNonce'];
        }
        if (!empty($_SESSION['csp_nonce'])) {
            return (string)$_SESSION['csp_nonce'];
        }
        return '';
    }
}
