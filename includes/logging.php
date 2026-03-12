<?php

ini_set('display_errors', 0);
ini_set('log_errors', 1);

$log1 = __DIR__ . '/../storage/logs/.php.error.log';
$log2 = __DIR__ . '/../error_log';

if (is_dir(dirname($log1))) {
    ini_set('error_log', $log1);
} else {
    ini_set('error_log', $log2);
}

@error_log("Runtime logging initialized: " . date('Y-m-d H:i:s'));
?>
