<?php

if (!function_exists('gdy_file_put_contents')) {
  
  function gdy_file_put_contents(string $file, string $data, int $flags = 0): int|false {
    $dir = dirname($file);
    if (!is_dir($dir)) {
      @mkdir($dir, 0755, true);
    }
    $tmp = $file . '.tmp.' . bin2hex(random_bytes(6));
    $bytes = @file_put_contents($tmp, $data, $flags | LOCK_EX);
    if ($bytes === false) {
      @unlink($tmp);
      return false;
    }
    
    if (!@rename($tmp, $file)) {
      
      $ok = @copy($tmp, $file);
      @unlink($tmp);
      return $ok ? $bytes : false;
    }
    return $bytes;
  }
}

if (!function_exists('gdy_boot_error_logging')) {
  function gdy_boot_error_logging(?string $forcedLogPath = null): void {
    
    @ini_set('display_errors', '0');
    @ini_set('log_errors', '1');
    @ini_set('error_reporting', (string)E_ALL);

    $root = defined('GODYAR_ROOT') ? (string)GODYAR_ROOT : realpath(__DIR__ . '/..');
    $candidates = [];

    if ($forcedLogPath) {
      $candidates[] = $forcedLogPath;
    }

    
    if ($root) {
      $candidates[] = $root . '/storage/logs/.php.error.log';
      $candidates[] = $root . '/storage/logs/php.error.log';
      $candidates[] = $root . '/error_log';
    }

    $logFile = null;
    foreach ($candidates as $p) {
      if (!$p) continue;
      $dir = dirname($p);
      if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
      }
      if (is_dir($dir) && (@is_writable($dir) || (is_file($p) && @is_writable($p)))) {
        $logFile = $p;
        break;
      }
    }

    if ($logFile) {
      @ini_set('error_log', $logFile);
    }

    
    set_error_handler(function ($severity, $message, $file, $line) {
      
      if (!(error_reporting() & $severity)) return false;
      $msg = "[PHP] {$message} in {$file}:{$line}";
      error_log($msg);
      return false; 
    });

    set_exception_handler(function ($ex) {
      $msg = '[EXCEPTION] ' . $ex->getMessage() . ' in ' . $ex->getFile() . ':' . $ex->getLine();
      error_log($msg . "\n" . $ex->getTraceAsString());
      
    });
  }
}

if (!defined('GDY_LOGGING_BOOTED')) {
  define('GDY_LOGGING_BOOTED', true);
  gdy_boot_error_logging();
}

if (!function_exists('gdy_detect_lang')) {
  function gdy_detect_lang(): array {
    $supported = ['ar', 'en', 'fr'];

    
    $lang = strtolower((string)($_GET['lang'] ?? ''));

    
    if (!$lang) {
      $uri = (string)($_SERVER['REQUEST_URI'] ?? '');
      if (preg_match('~^/([a-z]{2})(/|$)~i', $uri, $m)) {
        $lang = strtolower($m[1]);
      }
    }

    
    if (!$lang) {
      $lang = strtolower((string)($_COOKIE['gdy_lang'] ?? ''));
    }

    
    if (!in_array($lang, $supported, true)) {
      $lang = 'ar';
    }

    
    if (!headers_sent()) {
      setcookie('gdy_lang', $lang, time() + 3600*24*365, '/');
    }

    $dir = ($lang === 'ar') ? 'rtl' : 'ltr';
    return [$lang, $dir];
  }
}

if (!defined('GDY_LANG')) {
  [$__lang, $__dir] = gdy_detect_lang();
  define('GDY_LANG', $__lang);
  define('GDY_DIR', $__dir);
}
