<?php

declare(strict_types=1);

function gdy_apply_security_headers(): void
{
    if (headers_sent() === true) {
        return;
    }

    if (defined('GDY_CSP_NONCE') === false) {
        try {
            $nonce = base64_encode(random_bytes(16));
        } catch (Throwable $e) {
            $nonce = base64_encode(hash('sha256', uniqid('gdy-csp-', true), true));
        }

        define('GDY_CSP_NONCE', $nonce);
    }

    $serverVar = static function (string $key, string $default = ''): string {
        if (isset($_SERVER[$key]) === true) {
            return (string) $_SERVER[$key];
        }
        return $default;
    };

    $env = static function (string $name): string {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? null;

        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    };

    $sendHeader = static function (string $value): void {
        if ($value !== '') {
            header($value);
        }
    };

    $headerList = static function (): array {
        return function_exists('headers_list') === true ? headers_list() : [];
    };

    if ($env('GDY_SECURITY_HEADERS') === '0') {
        return;
    }

    $securityHeaders = [
        'X-Content-Type-Options: nosniff',
        'Referrer-Policy: strict-origin-when-cross-origin',
        'X-Frame-Options: SAMEORIGIN',
        'X-Permitted-Cross-Domain-Policies: none',
        'Permissions-Policy: geolocation=(), microphone=(), camera=()',
        'X-XSS-Protection: 0',
        'Cross-Origin-Opener-Policy: same-origin',
        'Cross-Origin-Resource-Policy: same-origin',
    ];

    foreach ($securityHeaders as $headerValue) {
        $sendHeader($headerValue);
    }

    if ($env('GDY_HSTS') !== '0') {
        $httpsValue = strtolower($serverVar('HTTPS', ''));
        $forwardedProto = strtolower($serverVar('HTTP_X_FORWARDED_PROTO', ''));
        $serverPort = (int) $serverVar('SERVER_PORT', '0');

        $isHttps = false;

        if ($httpsValue !== '' && $httpsValue !== 'off') {
            $isHttps = true;
        }

        if ($serverPort === 443) {
            $isHttps = true;
        }

        if ($forwardedProto === 'https') {
            $isHttps = true;
        }

        if ($isHttps === true) {
            $maxAge = 31536000;
            $maxAgeEnv = $env('GDY_HSTS_MAXAGE');

            if ($maxAgeEnv !== '' && ctype_digit($maxAgeEnv) === true) {
                $maxAge = (int) $maxAgeEnv;
            }

            $sendHeader('Strict-Transport-Security: max-age=' . $maxAge . '; includeSubDomains');
        }
    }

    if ($env('GDY_CSP') === '0') {
        return;
    }

    foreach ($headerList() as $existingHeader) {
        if (stripos($existingHeader, 'Content-Security-Policy:') === 0) {
            return;
        }

        if (stripos($existingHeader, 'Content-Security-Policy-Report-Only:') === 0) {
            return;
        }
    }

    $useReportOnly = ($env('GDY_CSP_REPORT_ONLY') === '1');

    $reportUri = $env('GDY_CSP_REPORT_URI');
    if ($reportUri === '') {
        $reportUri = '/csp-report.php';
    }

    $nonce = defined('GDY_CSP_NONCE') === true ? (string) GDY_CSP_NONCE : '';

    $csp = "default-src 'self'; "
        . "base-uri 'self'; "
        . "object-src 'none'; "
        . "frame-ancestors 'self'; "
        . "img-src 'self' data: https:; "
        . "font-src 'self' data: https:; "
        . "style-src 'self' 'unsafe-inline' https:; "
        . "style-src-attr 'unsafe-inline'; "
        . "script-src 'self' 'nonce-{$nonce}' https:; "
        . "connect-src 'self' https:; "
        . "form-action 'self';";

    if ($reportUri !== '') {
        $csp .= ' report-uri ' . $reportUri . ';';
    }

    if ($useReportOnly === true) {
        $sendHeader('Content-Security-Policy-Report-Only: ' . $csp);
        return;
    }

    $sendHeader('Content-Security-Policy: ' . $csp);
}