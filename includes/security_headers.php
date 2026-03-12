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

    $sendHeader = static function (string $value): void {
        header($value);
    };

    $enabled = getenv('GDY_SECURITY_HEADERS');
    if ($enabled !== false && trim((string) $enabled) === '0') {
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

    $hstsEnabled = getenv('GDY_HSTS');
    if (!($hstsEnabled !== false && trim((string) $hstsEnabled) === '0')) {
        $https = false;
        $https = ($serverVar('HTTPS', '') !== '' && strtolower($serverVar('HTTPS', '')) !== 'off')
            || ((int) $serverVar('SERVER_PORT', '0') === 443)
            || (strtolower($serverVar('HTTP_X_FORWARDED_PROTO', '')) === 'https');

        if ($https === true) {
            $maxAge = getenv('GDY_HSTS_MAXAGE');
            $maxAge = ($maxAge !== false && ctype_digit((string) $maxAge)) ? (int) $maxAge : 31536000;
            $sendHeader('Strict-Transport-Security: max-age=' . $maxAge . '; includeSubDomains');
        }
    }

    $cspEnabled = getenv('GDY_CSP');
    if ($cspEnabled !== false && trim((string) $cspEnabled) === '0') {
        return;
    }

    foreach (headers_list() as $h) {
        if (stripos($h, 'Content-Security-Policy:') === 0) {
            return;
        }
        if (stripos($h, 'Content-Security-Policy-Report-Only:') === 0) {
            return;
        }
    }

    $reportOnly = getenv('GDY_CSP_REPORT_ONLY');
    $useReportOnly = ($reportOnly !== false && trim((string) $reportOnly) === '1');

    $reportUri = getenv('GDY_CSP_REPORT_URI');
    if ($reportUri === false || trim((string) $reportUri) === '') {
        $reportUri = '/csp-report.php';
    }

    $nonce = defined('GDY_CSP_NONCE') === true ? (string) GDY_CSP_NONCE : '';
    $csp = "default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'self'; "
        . "img-src 'self' data: https:; font-src 'self' data: https:; "
        . "style-src 'self' 'unsafe-inline' https:; style-src-attr 'unsafe-inline'; "
        . "script-src 'self' 'nonce-{$nonce}' https:; connect-src 'self' https:; "
        . "form-action 'self';";

    if (is_string($reportUri) === true && trim($reportUri) !== '') {
        $csp .= ' report-uri ' . trim($reportUri) . ';';
    }

    $headerValue = ($useReportOnly === true)
        ? 'Content-Security-Policy-Report-Only: ' . $csp
        : 'Content-Security-Policy: ' . $csp;

    $sendHeader($headerValue);
}