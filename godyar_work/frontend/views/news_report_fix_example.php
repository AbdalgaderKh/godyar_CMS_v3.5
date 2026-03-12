<?php


require_once __DIR__ . '/../../includes/frontend_compat_fixes.php';

$updatedAt = gdy_safe_updated_at(isset($post) ? $post : array());
$updatedAtIso = gdy_safe_updated_at_iso(isset($post) ? $post : array());

if (!isset($currentUrl)) {
    $currentUrl = '';
}

$canonical = gdy_safe_canonical(isset($canonical) ? $canonical : '', $currentUrl);
