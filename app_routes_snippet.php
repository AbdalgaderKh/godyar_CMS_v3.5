<?php

$route = $route ?? trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');

$routeParts = explode('/', $route);
if (!empty($routeParts[0]) && in_array($routeParts[0], ['ar', 'en', 'fr'], true)) {
    $_GET['lang'] = $_GET['lang'] ?? $routeParts[0];
    array_shift($routeParts);
    $route = implode('/', $routeParts);
}

if (preg_match('#^page/([a-zA-Z0-9_-]+)$#', $route, $m)) {
    require_once __DIR__ . '/src/Controllers/PageController.php';
    PageController::show($m[1]);
    exit;
}

if ($route === 'contact') {
    require_once __DIR__ . '/src/Controllers/PageController.php';
    PageController::show('contact');
    exit;
}
