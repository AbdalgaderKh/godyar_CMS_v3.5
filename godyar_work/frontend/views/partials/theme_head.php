<?php

if (!function_exists('h')) {
    function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

$normalizeTheme = static function($v): string {
    if (is_array($v)) {
        foreach (['slug','theme','value','name','key'] as $k) {
            if (!empty($v[$k])) { $v = (string)$v[$k]; break; }
        }
    } elseif (is_object($v)) {
        foreach (['slug','theme','value','name','key'] as $k) {
            if (!empty($v->$k)) { $v = (string)$v->$k; break; }
        }
    }
    $v = is_string($v) ? trim($v) : '';
    if ($v === '') return '';
    
    if (preg_match('~theme-([a-z0-9_-]+)\.css~i', $v, $m)) {
        $v = $m[1];
    }
    $v = strtolower($v);
    $v = preg_replace('/^theme-/', '', $v);
    $v = preg_replace('/[^a-z0-9_-]/', '', $v);
    return $v;
};

$baseUrl = isset($baseUrl) ? rtrim((string)$baseUrl, '/') : '';
if ($baseUrl === '' && function_exists('base_url')) {
    $baseUrl = rtrim((string)base_url(), '/');
}

$siteSettings = (isset($siteSettings) && is_array($siteSettings)) ? $siteSettings : ($GLOBALS['site_settings'] ?? []);
if (!is_array($siteSettings)) { $siteSettings = []; }

if (!function_exists('gdy_get_active_theme')) {
    
    $candidates = [
        __DIR__ . '/../../../includes/theme_utils.php',
        
        __DIR__ . '/../../includes/theme_utils.php',
        __DIR__ . '/../../../../includes/theme_utils.php',
    ];

    foreach ($candidates as $p) {
        if (is_file($p)) { require_once $p; break; }
    }

    
    if (!function_exists('gdy_get_active_theme')) {
        function gdy_get_active_theme($settings = null): string {
            $normalize = static function ($v): string {
                $v = is_string($v) ? trim($v) : '';
                if ($v === '') return '';
                if (preg_match('~theme-([a-z0-9_-]+)\.css~i', $v, $m)) { $v = $m[1]; }
                $v = strtolower($v);
                $v = preg_replace('/^theme-/', '', $v);
                $v = preg_replace('/[^a-z0-9_-]/', '', $v);
                return $v;
            };

            $theme = '';
            $settings = is_array($settings) ? $settings : [];
            foreach (['theme_name','theme_front','frontend_theme','theme'] as $k) {
                if (!empty($settings[$k])) { $theme = (string)$settings[$k]; break; }
            }
            $theme = $normalize($theme);
            return $theme !== '' ? $theme : 'default';
        }
    }
}

$activeTheme = $normalizeTheme($GLOBALS['gdy_active_theme'] ?? '');

if ($activeTheme === '') {
    $activeTheme = $normalizeTheme(gdy_get_active_theme($siteSettings));
}

if ($activeTheme === '') { $activeTheme = 'default'; }

$gdyAssetVer = preg_replace('~[^0-9A-Za-z._-]~','',(string)($siteSettings['assets_version'] ?? (defined('GODYAR_VERSION') ? GODYAR_VERSION : (defined('GODYAR_CMS_VERSION') ? GODYAR_CMS_VERSION : '20260226'))));
if ($gdyAssetVer === '') $gdyAssetVer = '20260226';

$assetFallbackVer = isset($assetVer) ? (string)$assetVer : '1';
$asset = static function (string $path) use ($baseUrl, $assetFallbackVer, $gdyAssetVer): string {
    $path = '/' . ltrim($path, '/');
    $disk = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/') . $path;
    $v = is_file($disk) ? (string)@filemtime($disk) : $assetFallbackVer;
    return ($baseUrl ?: '') . $path . '?v=' . rawurlencode((string)$v) . '&av=' . rawurlencode((string)$gdyAssetVer);
};

$docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$helperRoot = '';
if (function_exists('gdy_public_root')) {
    $helperRoot = rtrim((string)gdy_public_root(__DIR__), '/');
}
$publicRoot = $docRoot ?: $helperRoot;
if ($publicRoot === '') { $publicRoot = $helperRoot; }
$themeFileRel = 'assets/css/themes/theme-' . $activeTheme . '.css';
$themeDisk    = rtrim(str_replace('\\', '/', $publicRoot), '/') . '/' . $themeFileRel;

if (!is_file($themeDisk) && $activeTheme !== 'default') {
    $activeTheme = 'default';
    $themeFileRel = 'assets/css/themes/theme-default.css';
    $themeDisk    = rtrim(str_replace('\\', '/', $publicRoot), '/') . '/' . $themeFileRel;
}

$GLOBALS['gdy_active_theme'] = $activeTheme;

$nonce = '';
if (defined('GDY_CSP_NONCE') && GDY_CSP_NONCE) {
    $nonce = (string)GDY_CSP_NONCE;
} elseif (!empty($_SESSION['csp_nonce'])) {
    $nonce = (string)$_SESSION['csp_nonce'];
} elseif (!empty($GLOBALS['GDY_CSP_NONCE'])) {
    $nonce = (string)$GLOBALS['GDY_CSP_NONCE'];
}
$nonceAttr = $nonce ? ' nonce="' . h($nonce) . '"' : '';

?>
<link rel="stylesheet" href="<?= h($asset('assets/css/themes/theme-core.css')) ?>">
<?php if (is_file($themeDisk)): ?>
<link rel="stylesheet" href="<?= h($asset($themeFileRel)) ?>">
<?php endif; ?>
<link rel="stylesheet" href="<?= h($asset('assets/css/theme-bindings-premium.css')) ?>">

<script<?= $nonceAttr ?>>

(function(){
  try{
    var slug = <?= json_encode($activeTheme) ?>;
    var cls  = 'theme-' + slug;
    var root = document.documentElement;
    var body = document.body;
    function strip(el){
      if(!el) return;
      
      el.className = (el.className || '').replace(/\btheme-[a-z0-9_-]+\b/gi, '').replace(/\s+/g,' ').trim();
    }
    function apply(el){
      if(!el) return;
      strip(el);
      el.classList.add(cls);
    }
    
    apply(root);
    
    function syncBody(){
      if(document.body){ apply(document.body); return true; }
      return false;
    }
    if(!syncBody()){
      document.addEventListener('DOMContentLoaded', syncBody, { once:true });
      var tries=0, t=setInterval(function(){
        tries++; if(syncBody() || tries>20){ clearInterval(t); }
      }, 50);
    }
  }catch(e){}
})();
</script>

<style<?= $nonceAttr ?>>
  html{ color-scheme: light dark; }
  :root[data-theme="dark"]{ color-scheme: dark; }
  :root[data-theme="light"]{ color-scheme: light; }
</style>
