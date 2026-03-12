<?php

$root = defined('GODYAR_ROOT') ? (string)GODYAR_ROOT : realpath(__DIR__ . '/../../');
$runtime = $root ? ($root . '/includes/gdy_runtime.php') : null;
if ($runtime && is_file($runtime)) {
  require_once $runtime;
} else {
  
  if (!function_exists('gdy_file_put_contents')) {
    function gdy_file_put_contents(string $file, string $data, int $flags = 0): int|false {
      $dir = dirname($file);
      if (!is_dir($dir)) @mkdir($dir, 0755, true);
      return @file_put_contents($file, $data, $flags | LOCK_EX);
    }
  }
  @ini_set('display_errors', '0');
  @ini_set('log_errors', '1');
}
