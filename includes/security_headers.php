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

    $sendHeader = static function (string $headerValue): void {
        header($headerValue);
    };

    $isDisabledByEnv = static function (string $envName): bool {
        $value = getenv($envName);

        if ($value === false) {
            return false;
        }

        return trim((string) $value) === '0';
    };

    $isEnabledByEnv = static function (string $envName): bool {
        $value = getenv($envName);

        if ($value === false) {
            return false;
        }

        return trim((string) $value) === '1';
    };

    if ($isDisabledByEnv('GDY_SECURITY_HEADERS') === true) {
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

    if ($isDisabledByEnv('GDY_HSTS') === false) {
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
            $maxAgeValue = getenv('GDY_HSTS_MAXAGE');
            $maxAge = 31536000;

            if ($maxAgeValue !== false && ctype_digit((string) $maxAgeValue) === true) {
                $maxAge = (int) $maxAgeValue;
            }

            $sendHeader('Strict-Transport-Security: max-age=' . $maxAge . '; includeSubDomains');
        }
    }

    if ($isDisabledByEnv('GDY_CSP') === true) {
        return;
    }

    foreach (headers_list() as $existingHeader) {
        if (stripos($existingHeader, 'Content-Security-Policy:') === 0) {
            return;
        }

        if (stripos($existingHeader, 'Content-Security-Policy-Report-Only:') === 0) {
            return;
        }
    }

    $useReportOnly = $isEnabledByEnv('GDY_CSP_REPORT_ONLY');

    $reportUriValue = getenv('GDY_CSP_REPORT_URI');
    $reportUri = '/csp-report.php';

    if ($reportUriValue !== false && trim((string) $reportUriValue) !== '') {
        $reportUri = trim((string) $reportUriValue);
    }

    $nonce = '';
    if (defined('GDY_CSP_NONCE') === true) {
        $nonce = (string) GDY_CSP_NONCE;
    }

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