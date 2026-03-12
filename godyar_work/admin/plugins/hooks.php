<?php

try {
    $isDirect = (PHP_SAPI !== 'cli') && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__);
} catch (\Throwable $e) {
    $isDirect = false;
}
if ((empty($isDirect) === false)) {
    require_once __DIR__ . '/../_admin_guard.php';
    if (class_exists('Godyar\\Auth') && method_exists('Godyar\\Auth','requireRole')) {
        \Godyar\Auth::requireRole('admin');
    } else {
        if (($_SESSION['user']['role'] ?? '') !== 'admin') { http_response_code(403); exit('403 Forbidden'); }
    }
    header('Location: /admin/plugins/index.php');
    exit;
}

$GLOBALS['GDY_ACTIONS'] = $GLOBALS['GDY_ACTIONS'] ?? [];
$GLOBALS['GDY_FILTERS'] = $GLOBALS['GDY_FILTERS'] ?? [];

function add_action(string $hook, callable $cb, int $priority = 10): void {
    $GLOBALS['GDY_ACTIONS'][$hook][$priority][] = $cb;
}
function do_action(string $hook, ... $args): void {
    if (empty($GLOBALS['GDY_ACTIONS'][$hook])) return;
    ksort($GLOBALS['GDY_ACTIONS'][$hook]);
    foreach ($GLOBALS['GDY_ACTIONS'][$hook] as $cbs) {
        foreach ($cbs as $cb) {
            try { $cb( ... $args); } catch (\Throwable $e) {  }
        }
    }
}
function add_filter(string $hook, callable $cb, int $priority = 10): void {
    $GLOBALS['GDY_FILTERS'][$hook][$priority][] = $cb;
}
function apply_filters(string $hook, $value, ... $args) {
    if (empty($GLOBALS['GDY_FILTERS'][$hook])) return $value;
    ksort($GLOBALS['GDY_FILTERS'][$hook]);
    foreach ($GLOBALS['GDY_FILTERS'][$hook] as $cbs) {
        foreach ($cbs as $cb) {
            try { $value = $cb($value, ... $args); } catch (\Throwable $e) {  }
        }
    }
    return $value;
}
