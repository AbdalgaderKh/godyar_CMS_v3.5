<?php
namespace Godyar\Services;

$root = dirname(__DIR__, 2);
$target = $root . '/includes/classes/Services/CategoryService.php';
if (is_file($target)) {
    require_once $target;
}
