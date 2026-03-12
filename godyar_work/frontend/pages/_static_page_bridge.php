<?php
/**
 * Legacy static-page bridge.
 * Keeps old direct file paths working while rendering through the canonical controller.
 */
declare(strict_types=1);

$slug = isset($slug) ? (string)$slug : '';
$slug = strtolower(trim($slug));

if (!in_array($slug, ['about', 'privacy', 'terms', 'contact'], true)) {
    http_response_code(404);
    echo 'Static page not found.';
    return;
}

// Preserve the current request but ensure the canonical slug is available.
$_GET['slug'] = $slug;
$_REQUEST['slug'] = $slug;

require __DIR__ . '/../controllers/PageController.php';
