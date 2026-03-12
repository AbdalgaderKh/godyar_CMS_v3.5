<?php

if (!function_exists('gdy_settings_render_embed')) {
  function gdy_settings_render_embed(callable $fn): string {
    ob_start();
    try {
      $fn();
    } catch (Throwable $e) {
      
      $msg = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
      echo '<div class="alert alert-danger" style="border-radius:14px;">';
      echo '<strong>خطأ في تحميل تبويب الإعدادات:</strong><div class="mt-2" dir="ltr">' . $msg . '</div>';
      echo '</div>';
    }
    $html = ob_get_clean();

    
    if (stripos($html, '<html') !== false) {
      if (preg_match('~<body[^>]*>(.*)</body>~is', $html, $m)) {
        $html = $m[1];
      }
    }

    return $html;
  }
}
