<?php

if (!empty($jsonLd) && is_array($jsonLd)) {
  $nonceAttr = '';
  if (isset($cspNonce) && is_string($cspNonce) && $cspNonce !== '') {
    $nonceAttr = ' nonce="' . htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8') . '"';
  } elseif (defined('GDY_CSP_NONCE')) {
    $nonceAttr = ' nonce="' . htmlspecialchars((string)GDY_CSP_NONCE, ENT_QUOTES, 'UTF-8') . '"';
  }
  $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
  $out = json_encode($jsonLd, $flags);
  if (is_string($out) && $out !== '') {
    echo '<script' . $nonceAttr . ' type="application/ld+json">' . $out . '</script>';
  }
}
