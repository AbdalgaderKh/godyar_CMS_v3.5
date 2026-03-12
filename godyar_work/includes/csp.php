<?php

if (!function_exists('gdy_csp_nonce')) {
  function gdy_csp_nonce(): string {
    if (!empty($GLOBALS['gdy_csp_nonce']) && is_string($GLOBALS['gdy_csp_nonce'])) {
      return $GLOBALS['gdy_csp_nonce'];
    }
    try {
      $nonce = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
    } catch (Throwable $e) {
      $nonce = bin2hex(random_bytes(16));
    }
    $GLOBALS['gdy_csp_nonce'] = $nonce;
    return $nonce;
  }
}

if (!function_exists('gdy_nonce_attr')) {
  function gdy_nonce_attr(?string $nonce): string {
    $nonce = (string)($nonce ?? '');
    if ($nonce === '') return '';
    return ' nonce="' . htmlspecialchars($nonce, ENT_QUOTES, 'UTF-8') . '"';
  }
}

if (!function_exists('gdy_send_csp_headers')) {
  function gdy_send_csp_headers(?string $nonce = null): void {
    if (headers_sent()) return;
    if (defined('GDY_SKIP_CSP') && GDY_SKIP_CSP) return;

    $nonce = (string)($nonce ?? '');
    $scriptSrc = "'self'";
    if ($nonce !== '') $scriptSrc .= " 'nonce-{$nonce}'";
    else $scriptSrc .= " 'unsafe-inline'";

    $csp = [
      "default-src 'self'",
      "base-uri 'self'",
      "frame-ancestors 'self'",
      "object-src 'none'",
      "img-src 'self' data: https:",
      "font-src 'self' data: https:",
      "style-src 'self' 'unsafe-inline' https:",
      "script-src {$scriptSrc} https:",
      "connect-src 'self' https:",
    ];

    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Content-Security-Policy: ' . implode('; ', $csp));
  }
}
