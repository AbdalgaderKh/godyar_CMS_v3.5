<?php

$GLOBALS['_gdy_assets_included'] = $GLOBALS['_gdy_assets_included'] ?? ['css'=>[], 'js'=>[]];

function gdy_asset_url(string $path): string {
    $path = '/' . ltrim($path, '/');
    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    $abs = $docRoot ? rtrim($docRoot, '/') . $path : '';
    $v = '';
    if ($abs && is_file($abs)) {
        $v = (string)@filemtime($abs);
    }
    $q = $v !== '' ? ('?v=' . rawurlencode($v)) : '';
    
    $base = defined('GODYAR_BASE_URL') ? rtrim((string)GODYAR_BASE_URL, '/') : '';
    return $base . $path . $q;
}

function gdy_css(string $path, array $attrs = []): void {
    $key = ltrim($path, '/');
    if (!empty($GLOBALS['_gdy_assets_included']['css'][$key])) return;
    $GLOBALS['_gdy_assets_included']['css'][$key] = true;

    $href = htmlspecialchars(gdy_asset_url($path), ENT_QUOTES, 'UTF-8');
    $extra = '';
    foreach ($attrs as $k=>$v) {
        if ($v === null || $v === false) continue;
        $extra .= ' ' . htmlspecialchars((string)$k, ENT_QUOTES, 'UTF-8')
               . '="' . htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8') . '"';
    }
    echo '<link rel="stylesheet" href="' . $href . '"' . $extra . ">\n";
}

function gdy_js(string $path, ?string $nonce = null, array $attrs = []): void {
    $key = ltrim($path, '/');
    if (!empty($GLOBALS['_gdy_assets_included']['js'][$key])) return;
    $GLOBALS['_gdy_assets_included']['js'][$key] = true;

    $src = htmlspecialchars(gdy_asset_url($path), ENT_QUOTES, 'UTF-8');
    $nonceAttr = '';
    if (is_string($nonce) && trim($nonce) !== '') {
        $nonceAttr = ' nonce="' . htmlspecialchars(trim($nonce), ENT_QUOTES, 'UTF-8') . '"';
    }
    $extra = '';
    foreach ($attrs as $k=>$v) {
        if ($v === null || $v === false) continue;
        
        if ($v === true) { $extra .= ' ' . htmlspecialchars((string)$k, ENT_QUOTES, 'UTF-8'); continue; }
        $extra .= ' ' . htmlspecialchars((string)$k, ENT_QUOTES, 'UTF-8')
               . '="' . htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8') . '"';
    }
    echo '<script' . $nonceAttr . ' src="' . $src . '"' . $extra . '></script>' . "\n";
}
