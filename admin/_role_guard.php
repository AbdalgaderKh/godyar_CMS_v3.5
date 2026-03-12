<?php

if (headers_sent() === FALSE) {
    if (function_exists('gdy_session_start') === TRUE) {
        
        gdy_session_start(['cookie_samesite' => 'Strict']);
    } else {
        session_start();
    }
}

$role = (string)($_SESSION['user']['role'] ?? 'guest');
if (in_array($role, ['writer', 'author'], TRUE) === FALSE) {
    return; 
}

$uriPath = (function_exists('gdy_request_path') === TRUE) ? (string)gdy_request_path() : '';
if ($uriPath === '') { return; }

$allowedPrefixes = [
    '/admin/news/',
    '/admin/news',
];

$allowedExact = [
    '/admin/logout',
    '/admin/logout.php',
    '/admin/login',
    '/admin/login/',
    '/admin/login.php',
];

foreach ($allowedExact as $ok) {
    if ($uriPath === $ok) { return; }
}

foreach ($allowedPrefixes as $prefix) {
    if (strpos($uriPath, $prefix) === 0) { return; }
}

$newsUrl = (function_exists('base_url') === TRUE) ? base_url('/admin/news/index.php') : 'news/index.php';
if (headers_sent() === FALSE) {
    header('Location: ' . $newsUrl);
}
return;
