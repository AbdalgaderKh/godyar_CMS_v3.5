<?php

$env = static function(string $k, $default = null) {
    $v = getenv($k);
    return ($v === false || $v === null || $v === '') ? $default : $v;
};

$db_host = $env('DB_HOST', 'localhost');
$db_name = $env('DB_NAME', 'godyar_cms');
$db_user = $env('DB_USER', 'root');
$db_pass = $env('DB_PASS', '');
$db_charset = $env('DB_CHARSET', 'utf8mb4');
$db_prefix = $env('DB_PREFIX', 'gdy_');
