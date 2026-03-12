<?php

if (!function_exists('gdy_norm_theme_path')) {
  
  function gdy_norm_theme_path($value) {
    $v = trim((string)$value);
    if ($v === '') return null;

    
    if (preg_match('~^(theme-)?([a-z0-9_-]+)$~i', $v, $m) && stripos($v, '.css') === false && stripos($v, 'assets/') === false) {
      $slug = strtolower($m[2]);
      return "assets/css/themes/theme-{$slug}.css";
    }

    
    $v = str_replace('\\', '/', $v);

    
    if (preg_match('~^https?://[^/]+/(.*)$~i', $v, $mm)) {
      $v = $mm[1];
    }

    $v = ltrim($v, '/');

    
    if (!preg_match('~^assets/css/themes/theme-[a-z0-9_-]+\.css$~i', $v)) {
      return null;
    }

    return $v;
  }
}

if (!function_exists('gdy_get_setting_any')) {
  
  function gdy_get_setting_any(array $keys) {
    if (!function_exists('site_settings_get')) return null;
    foreach ($keys as $k) {
      try {
        
        global $pdo;
        if (isset($pdo) && $pdo) {
          $val = site_settings_get($pdo, $k, null);
        } else {
          
          $val = site_settings_get($k, null);
        }
      } catch (Throwable $e) {
        $val = null;
      }
      if ($val !== null && $val !== '') return $val;
    }
    return null;
  }
}

if (!function_exists('gdy_site_theme_path')) {
  
  function gdy_site_theme_path() {
    
    $dbVal = gdy_get_setting_any(['frontend_theme', 'front_theme', 'site_theme']);
    $norm = gdy_norm_theme_path($dbVal);
    if ($norm) return $norm;

    
    $preset = gdy_get_setting_any(['front_preset']);
    $norm = gdy_norm_theme_path($preset);
    if ($norm) return $norm;

    
    $cfg = __DIR__ . '/../storage/config/site_theme.php';
    if (is_file($cfg)) {
      $cfgVal = @include $cfg;
      $norm = gdy_norm_theme_path($cfgVal);
      if ($norm) return $norm;
    }

    
    return 'assets/css/themes/theme-blue.css';
  }
}

if (!function_exists('gdy_theme_meta')) {
  
  function gdy_theme_meta() {
    $path = gdy_site_theme_path();

    
    $slug = 'blue';
    if (preg_match('~theme-([a-z0-9_-]+)\.css$~i', $path, $m)) {
      $slug = strtolower($m[1]);
    }

    return ['path' => $path, 'key' => 'theme-' . $slug, 'slug' => $slug];
  }
}
