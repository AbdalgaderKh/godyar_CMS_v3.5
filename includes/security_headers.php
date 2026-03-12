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

    $envVar = static function (string $name, string $default = ''): string {
        $value = getenv($name);

        if ($value === false) {
            return $default;
        }

        return trim((string) $value);
    };

    $sendHeader = static function (string $value): void {
        if ($value === '') {
            return;
        }

        header($value);
    };

    if ($envVar('GDY_SECURITY_HEADERS') === '0') {
        return;
    }

    $sendHeader('X-Content-Type-Options: nosniff');
    $sendHeader('Referrer-Policy: strict-origin-when-cross-origin');
    $sendHeader('X-Frame-Options: SAMEORIGIN');
    $sendHeader('X-Permitted-Cross-Domain-Policies: none');
    $sendHeader('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    $sendHeader('X-XSS-Protection: 0');
    $sendHeader('Cross-Origin-Opener-Policy: same-origin');
    $sendHeader('Cross-Origin-Resource-Policy: same-origin');

    $hstsDisabled = ($envVar('GDY_HSTS') === '0');

    if ($hstsDisabled === false) {
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
            $maxAgeEnv = $envVar('GDY_HSTS_MAXAGE');

            if ($maxAgeEnv !== '' && ctype_digit($maxAgeEnv) === true) {
                $maxAge = (int) $maxAgeEnv;
            }

            $sendHeader('Strict-Transport-Security: max-age=' . $maxAge . '; includeSubDomains');
        }
    }

    if ($envVar('GDY_CSP') === '0') {
        return;
    }

    foreach (headers_list() as $existingHeader) {
        if (stripos((string) $existingHeader, 'Content-Security-Policy:') === 0) {
            return;
        }

        if (stripos((string) $existingHeader, 'Content-Security-Policy-Report-Only:') === 0) {
            return;
        }
    }

    $useReportOnly = ($envVar('GDY_CSP_REPORT_ONLY') === '1');

    $reportUri = $envVar('GDY_CSP_REPORT_URI', '/csp-report.php');

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