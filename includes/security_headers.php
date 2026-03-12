<?php

function gdy_apply_security_headers(): void
{
    if (headers_sent()) { return; }

    
    
    if (!defined('GDY_CSP_NONCE')) {
        try {
            $nonce = base64_encode(random_bytes(16));
        } catch (Throwable $e) {
            if (function_exists('openssl_random_pseudo_bytes')) {
                $fallback = openssl_random_pseudo_bytes(16);
                if ($fallback !== false) {
                    $nonce = base64_encode($fallback);
                } else {
                    $nonce = base64_encode(hash('sha256', microtime(true) . '|' . getmypid() . '|' . memory_get_usage(), true));
                }
            } else {
                $nonce = base64_encode(hash('sha256', microtime(true) . '|' . getmypid() . '|' . memory_get_usage(), true));
            }
        }
        define('GDY_CSP_NONCE', $nonce);
    }

    $enabled = getenv('GDY_SECURITY_HEADERS');
    if ($enabled !== false && trim((string)$enabled) === '0') {
        return;
    }

    
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Permitted-Cross-Domain-Policies: none');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

    
    header('X-XSS-Protection: 0');

    
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-origin');

    
    $hstsEnabled = getenv('GDY_HSTS');
    if (!($hstsEnabled !== false && trim((string)$hstsEnabled) === '0')) {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
 || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
 || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
        if ($https) {
            $maxAge = getenv('GDY_HSTS_MAXAGE');
            $maxAge = ($maxAge !== false && ctype_digit((string)$maxAge)) ? (int)$maxAge : 31536000;
            header('Strict-Transport-Security: max-age=' . $maxAge . '; includeSubDomains');
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

    
    
    $nonce = defined('GDY_CSP_NONCE') ? GDY_CSP_NONCE : '';
    $csp = "default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'self'; " .
           "img-src 'self' data: https:; font-src 'self' data: https:; " .
           "style-src 'self' 'unsafe-inline' https:; style-src-attr 'unsafe-inline'; " .
           "script-src 'self' 'nonce-{$nonce}' https:; connect-src 'self' https:; " .
           "form-action 'self';";

    
    if (is_string($reportUri) && trim($reportUri) !== '') {
        $csp .= ' report-uri ' . trim($reportUri) . ';';
    }

    if ($useReportOnly) {
        header('Content-Security-Policy-Report-Only: ' . $csp);
    } else {
        header('Content-Security-Policy: ' . $csp);
    }
}
