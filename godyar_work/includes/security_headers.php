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

    $enabled = getenv('GDY_SECURITY_HEADERS');
    if ($enabled !== false && trim((string)$enabled) === '0') {
        return;
    }
    gdy_header('X-Content-Type-Options: nosniff');
    gdy_header('Referrer-Policy: strict-origin-when-cross-origin');
    gdy_header('X-Frame-Options: SAMEORIGIN');
    gdy_header('X-Permitted-Cross-Domain-Policies: none');
    gdy_header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    gdy_header('X-XSS-Protection: 0');
    gdy_header('Cross-Origin-Opener-Policy: same-origin');
    gdy_header('Cross-Origin-Resource-Policy: same-origin');

    $hstsEnabled = getenv('GDY_HSTS');
    if (!($hstsEnabled !== false && trim((string)$hstsEnabled) === '0')) {
        $https = false;
        $https = (gdy_server_var('HTTPS', '') !== '' && strtolower(gdy_server_var('HTTPS', '')) !== 'off')
            || ((int)gdy_server_var('SERVER_PORT', '0') === 443)
            || (strtolower(gdy_server_var('HTTP_X_FORWARDED_PROTO', '')) === 'https');
        if ($https === true) {
            $maxAge = getenv('GDY_HSTS_MAXAGE');
            $maxAge = ($maxAge !== false && ctype_digit((string)$maxAge)) ? (int)$maxAge : 31536000;
            gdy_header('Strict-Transport-Security: max-age=' . $maxAge . '; includeSubDomains');
        }
    }

    $cspEnabled = getenv('GDY_CSP');
    if ($cspEnabled !== false && trim((string)$cspEnabled) === '0') {
        return;
    }

    foreach (headers_list() as $h) {
        if (stripos($h, 'Content-Security-Policy:') === 0) {
            return;
        }
    }

    $reportOnly = getenv('GDY_CSP_REPORT_ONLY');
    $useReportOnly = ($reportOnly !== false && trim((string)$reportOnly) === '1');
    $reportUri = getenv('GDY_CSP_REPORT_URI');
    if ($reportUri === false || trim((string)$reportUri) === '') {
        $reportUri = '/csp-report.php';
    }

    $nonce = defined('GDY_CSP_NONCE') === true ? (string)GDY_CSP_NONCE : '';
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

    gdy_header($headerValue);
}
