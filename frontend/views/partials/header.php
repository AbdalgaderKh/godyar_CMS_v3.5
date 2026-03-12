<?php

if (!function_exists('h')) {
  function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('gdy_clean_text')) {
  function gdy_clean_text($s): string {
    $s = (string)$s;
    
    return str_replace(['?>','<?'], '', $s);
  }
}

$siteSettings = (isset($siteSettings) && is_array($siteSettings)) ? $siteSettings : [];
$rawSettings  = (isset($rawSettings)  && is_array($rawSettings))  ? $rawSettings  : $siteSettings;

$GLOBALS['site_settings'] = $siteSettings;

$baseUrl = isset($baseUrl) ? (string)$baseUrl : ((string)($siteSettings['base_url'] ?? ''));
$baseUrl = rtrim($baseUrl, '/');

$rootUrl = isset($rootUrl) ? (string)$rootUrl : ($baseUrl ?: '');
$rootUrl = rtrim($rootUrl, '/');

$navBaseUrl = isset($navBaseUrl) ? (string)$navBaseUrl : ($baseUrl ?: '');
$navBaseUrl = rtrim($navBaseUrl, '/');

$pageLang = isset($pageLang) ? (string)$pageLang : ((string)($siteSettings['site_lang'] ?? 'ar'));
$pageLang = $pageLang ?: 'ar';
$pageDir  = in_array($pageLang, ['ar', 'fa', 'ur'], true) ? 'rtl' : 'ltr';

$lang = $pageLang;
$dir  = $pageDir;

$_gdy_baseUrl = $baseUrl;
$GLOBALS['_gdy_baseUrl'] = $_gdy_baseUrl;

$__uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
$__langPrefix = '';
if (preg_match('~^/(ar|en|fr)(?=/|$)~i', $__uri, $__m)) {
  $__langPrefix = '/' . strtolower($__m[1]);
}

$__navHasPrefix = (bool)preg_match('~/(ar|en|fr)$~i', $navBaseUrl) || (bool)preg_match('~/(ar|en|fr)/~i', $navBaseUrl);
$__navRoot = ($__navHasPrefix ? $navBaseUrl : rtrim(($rootUrl ?: $baseUrl), '/') . $__langPrefix);
$__navRoot = rtrim((string)$__navRoot, '/');
$__rootAbs = rtrim(($rootUrl ?: $baseUrl), '/');

if (!isset($buildLangUrl) || !is_callable($buildLangUrl)) {
  $buildLangUrl = function (string $target) use ($__uri, $__rootAbs): string {
    if (function_exists('gdy_lang_url')) { return (string)gdy_lang_url($target); }
    $target = strtolower(trim($target));
    if (!in_array($target, ['ar','en','fr'], true)) { $target = 'ar'; }
    $u = (string)$__uri;
    
    $qpos = strpos($u, '?');
    $path = ($qpos === false) ? $u : substr($u, 0, $qpos);
    $path = $path !== '' ? $path : '/';
    
    $path = preg_replace('~^/(ar|en|fr)(?=/|$)~i', '', $path);
    $path = '/' . ltrim((string)$path, '/');
    
    $base = rtrim((string)$__rootAbs, '/');
    return $base . '/' . $target . $path;
  };
}

if (!isset($cspNonce) || trim((string)$cspNonce) === '') {
  if (!empty($GLOBALS['cspNonce'])) {
    $cspNonce = (string)$GLOBALS['cspNonce'];
  } elseif (defined('GDY_CSP_NONCE') && GDY_CSP_NONCE) {
    $cspNonce = (string)GDY_CSP_NONCE;
  } elseif (!empty($_SESSION['csp_nonce'])) {
    $cspNonce = (string)$_SESSION['csp_nonce'];
  } elseif (!empty($GLOBALS['gdy_csp_nonce'])) {
    $cspNonce = (string)$GLOBALS['gdy_csp_nonce'];
  } elseif (function_exists('gdy_csp_nonce')) {
    $cspNonce = (string)gdy_csp_nonce();
  } else {
    $cspNonce = '';
  }
}

$__gdy_strict = getenv('CSP_STRICT') === '1' || strtolower((string)getenv('CSP_STRICT')) === 'true';
if ($__gdy_strict && $cspNonce === '' && class_exists(\App\Support\Security::class) && !headers_sent()) {
  \App\Support\Security::headers();
  if (!empty($_SESSION['csp_nonce'])) {
    $cspNonce = (string)$_SESSION['csp_nonce'];
  }
}

$nonceAttr = $cspNonce ? ' nonce="' . htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8') . '"' : '';

$meta_title       = isset($meta_title) ? (string)$meta_title : ((string)($siteSettings['site_name'] ?? 'Godyar News'));
$meta_description = isset($meta_description) ? (string)$meta_description : ((string)($siteSettings['site_description'] ?? ''));
$canonical_url    = isset($canonical_url) ? (string)$canonical_url : (($__rootAbs ?: '') . ($_SERVER['REQUEST_URI'] ?? '/'));

$meta_description = trim((string)$meta_description);
if ($meta_description === '') {
  $meta_description = trim($meta_title) . ' منصة إخبارية متكاملة تقدم آخر الأخبار المحلية والعالمية، تحليلات وتقارير مميزة.';
}

if ($canonical_url !== '') {
  $isAbsolute = (bool)preg_match('~^https?://~i', $canonical_url);
  if (!$isAbsolute) {
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    if ($host !== '') {
      $path = $canonical_url;
      if ($path === '') $path = '/';
      if ($path[0] !== '/') $path = '/' . $path;
      $path = preg_replace('~^/{2,}~', '/', $path);
      $canonical_url = $scheme . '://' . $host . $path;
    }
  }
}

$isLoggedIn = !empty($_SESSION['user']) || !empty($_SESSION['user_id']) || !empty($_SESSION['is_member_logged']);

$userId = 0;
$username = '';
$userEmail = '';
$isAdmin = false;

if ($isLoggedIn) {
  if (!empty($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];
  } elseif (!empty($_SESSION['user']['id'])) {
    $userId = (int)$_SESSION['user']['id'];
  }

  if (!empty($_SESSION['username'])) {
    $username = (string)$_SESSION['username'];
  } elseif (!empty($_SESSION['user']['username'])) {
    $username = (string)$_SESSION['user']['username'];
  }

  if (!empty($_SESSION['user_email'])) {
    $userEmail = (string)$_SESSION['user_email'];
  } elseif (!empty($_SESSION['user']['email'])) {
    $userEmail = (string)$_SESSION['user']['email'];
  }

  $userRole = '';
  if (!empty($_SESSION['user']['role'])) {
    $userRole = (string)$_SESSION['user']['role'];
  } elseif (!empty($_SESSION['role'])) {
    $userRole = (string)$_SESSION['role'];
  }

  $isAdmin = ($userRole === 'admin' || $userRole === 'super_admin' || $userRole === 'administrator');
}

$GLOBALS['isLoggedIn'] = $isLoggedIn;
$GLOBALS['userId']     = $userId;
$GLOBALS['isAdmin']    = $isAdmin;

if (!function_exists('gdy_get_active_theme')) {
    $candidates = [
        __DIR__ . '/../../../includes/theme_utils.php',
        
        __DIR__ . '/../../includes/theme_utils.php',
        __DIR__ . '/../../../../includes/theme_utils.php',
    ];

    foreach ($candidates as $p) {
        if (is_file($p)) {
            require_once $p;
            break;
        }
    }

    
    if (!function_exists('gdy_get_active_theme')) {
        
        function gdy_get_active_theme($settings = null): string {
            $normalize = static function ($v): string {
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

            $theme = '';
            $settings = is_array($settings) ? $settings : [];

            foreach (['theme_name', 'theme_front', 'frontend_theme', 'theme'] as $k) {
                if (!empty($settings[$k])) { $theme = (string)$settings[$k]; break; }
            }

            
            if (trim((string)$theme) === '') {
                $docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
                $c = [];
                if ($docRoot !== '') {
                    $c[] = $docRoot . '/storage/config/site_theme.php';
                    $c[] = dirname($docRoot) . '/storage/config/site_theme.php';
                }
                
                $c[] = __DIR__ . '/../../../storage/config/site_theme.php';
                $c[] = __DIR__ . '/../../../../storage/config/site_theme.php';
                foreach ($c as $cfg) {
                    if (is_file($cfg)) {
                        $v = @include $cfg;
                        if (is_string($v) && trim($v) !== '') { $theme = trim($v); break; }
                        if (is_array($v) && !empty($v['theme'])) { $theme = (string)$v['theme']; break; }
                    }
                }
            }

            $theme = $normalize($theme);
            return $theme !== '' ? $theme : 'default';
        }
    }
}

$activeThemeInfo = gdy_get_active_theme($siteSettings);

if (!function_exists('gdy_normalize_theme_slug')) {
  
  function gdy_normalize_theme_slug($raw): string {
    if (is_array($raw)) {
      $raw = $raw['slug'] ?? $raw['theme'] ?? $raw['value'] ?? $raw['name'] ?? '';
    }
    $slug = strtolower(trim((string)($raw ?? '')));
    $slug = preg_replace('/^theme-/', '', $slug);
    $slug = preg_replace('/[^a-z0-9_-]/', '', $slug);
    return $slug !== '' ? $slug : 'default';
  }
}

if (is_array($activeThemeInfo)) {
  $activeTheme = gdy_normalize_theme_slug($activeThemeInfo);
  
  $themeBodyClass = 'theme-' . $activeTheme;
  $GLOBALS['gdy_active_theme_info'] = $activeThemeInfo;
} else {
  $activeTheme = gdy_normalize_theme_slug($activeThemeInfo);
  $themeBodyClass = 'theme-' . $activeTheme;
}

$GLOBALS['gdy_active_theme'] = $activeTheme;

$siteName = (string)__('brand.name', (string)($siteSettings['site_name'] ?? 'Godyar News'));
$siteSubtitle = (string)__('brand.tagline', (string)($siteSettings['site_description'] ?? ($siteSettings['site_slogan'] ?? '')));
$logoFallbackChar = mb_strtoupper(mb_substr(trim($siteName) ?: 'G', 0, 1, 'UTF-8'), 'UTF-8');

$brandingFile = __DIR__ . '/../../../storage/site_branding.php';
$brandingData = (is_file($brandingFile) ? (require $brandingFile) : []);
if (!is_array($brandingData)) { $brandingData = []; }
$siteLogo = (string)($siteSettings['site.logo'] ?? $siteSettings['site_logo'] ?? $siteSettings['logo'] ?? $siteSettings['site_logo_url'] ?? $brandingData['site.logo'] ?? $brandingData['site_logo'] ?? $brandingData['logo'] ?? '');
if ($siteLogo === '' && function_exists('settings_get')) {
  $siteLogo = (string)settings_get('site.logo', settings_get('site_logo', settings_get('logo', '')));
}
if ($siteLogo !== '' && function_exists('gdy_normalize_site_logo_value')) {
  $siteLogo = (string)gdy_normalize_site_logo_value($siteLogo, (string)($baseUrl ?: ''));
}
$logoUrl = '';
if ($siteLogo !== '') {
  if (preg_match('~^https?://~i', $siteLogo)) {
    $logoUrl = $siteLogo;
  } else {
    $siteLogo = ltrim($siteLogo, '/');
    $logoUrl = ($baseUrl ?: '') . '/' . $siteLogo;
  }
}

$gdyAssetVer = preg_replace('~[^0-9A-Za-z._-]~','',(string)($siteSettings['assets_version'] ?? (defined('GODYAR_VERSION') ? GODYAR_VERSION : (defined('GODYAR_CMS_VERSION') ? GODYAR_CMS_VERSION : '20260226'))));
if ($gdyAssetVer === '') $gdyAssetVer = '20260226';

$assetFallbackVer = isset($assetVer) ? (string)$assetVer : '1';
$asset = function(string $path) use ($baseUrl, $assetFallbackVer, $gdyAssetVer): string {
  $path = '/' . ltrim($path, '/');
  $disk = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/') . $path;
  $v = is_file($disk) ? (string)@filemtime($disk) : $assetFallbackVer;
  return ($baseUrl ?: '') . $path . '?v=' . rawurlencode((string)$v) . '&av=' . rawurlencode((string)$gdyAssetVer);
};

$langUrl = function(string $L) use ($__rootAbs, $baseUrl): string {
  $L = strtolower(trim($L));
  if ($L === '') $L = 'ar';

  if (function_exists('gdy_lang_url')) {
    return (string)gdy_lang_url($L);
  }

  $current = (string)($_SERVER['REQUEST_URI'] ?? '/');
  if ($current === '') $current = '/';

  
  $current = preg_replace('~^/(ar|en|fr)(/|$)~i', '/', $current);

  $root = ($__rootAbs ?: ($baseUrl ?: '')) ?: '';
  $root = rtrim($root, '/');

  
  if ($current[0] !== '/') $current = '/' . $current;

  return ($root !== '' ? $root : '') . '/' . $L . $current;
};

if (!isset($buildLangUrl) || !is_callable($buildLangUrl)) {
  $buildLangUrl = $langUrl;
}

?><!DOCTYPE html>
<html lang="<?= h($pageLang) ?>" dir="<?= h($pageDir) ?>" data-theme="light" class="js <?= h($themeBodyClass) ?>" >
<head><meta charset="utf-8">
<title><?= h($meta_title) ?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="description" content="<?= h($meta_description) ?>">
  <meta name="robots" content="index,follow">
  <link rel="canonical" href="<?= h($canonical_url) ?>">

  <?php
    
    $hrefAr = $buildLangUrl('ar');
    $hrefEn = $buildLangUrl('en');
    $hrefFr = $buildLangUrl('fr');
  ?>
  <link rel="alternate" hreflang="ar" href="<?= h($hrefAr) ?>">
  <link rel="alternate" hreflang="en" href="<?= h($hrefEn) ?>">
  <link rel="alternate" hreflang="fr" href="<?= h($hrefFr) ?>">
  <?php
    
    $xDefault = (string)($_SERVER['REQUEST_URI'] ?? '/');
    $xDefault = preg_replace('~^/(ar|en|fr)(/|$)~i', '/', $xDefault);
    $xDefault = ($baseUrl ?: '') . $xDefault;
  ?>
  <link rel="alternate" hreflang="x-default" href="<?= h($xDefault) ?>">

  <?php
    
    if (!empty($meta_prev_url)) {
      echo '<link rel="prev" href="' . h((string)$meta_prev_url) . '">';
    }
    if (!empty($meta_next_url)) {
      echo '<link rel="next" href="' . h((string)$meta_next_url) . '">';
    }
  ?>

  <link rel="alternate" type="application/rss+xml" title="RSS" href="<?= h(($baseUrl ?: '') . '/rss.xml') ?>">
  <link  rel="alternate" type="application/xml" href="<?= h(($baseUrl ?: '') . '/sitemap.xml') ?>">
  <link rel="manifest" href="<?= h(($baseUrl ?: '') . '/manifest.webmanifest') ?>">
  <meta name="theme-color" content="<?= h($themeColor ?? '#111111') ?>">
  <meta name="color-scheme" content="light dark">

  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= h($meta_title) ?>">
  <meta property="og:description" content="<?= h($meta_description) ?>">
  <meta property="og:url" content="<?= h($canonical_url) ?>">
  <?php
    
    $meta_image = $meta_image ?? '';
    if (is_string($meta_image) && $meta_image !== '') {
      $imgAbs = $meta_image;
      if (!preg_match('~^https?://~i', $imgAbs)) {
        $imgAbs = rtrim(($__rootAbs ?: ($baseUrl ?: '')), '/') . '/' . ltrim($imgAbs, '/');
      }
      echo '<meta property="og:image" content="' . h($imgAbs) . '">';
      echo '<meta name="twitter:image" content="' . h($imgAbs) . '">';
    }
  ?>
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= h($meta_title) ?>">
  <meta name="twitter:description" content="<?= h($meta_description) ?>">

  <?php
    $schemaOrg = [
      '@context' => 'https://schema.org',
      '@type' => 'Organization',
      'name' => $siteName,
      'url' => ($__rootAbs ?: ($baseUrl ?: '')) ?: '/',
    ];
    if ($logoUrl) $schemaOrg['logo'] = $logoUrl;

    $schemaSite = [
      '@context' => 'https://schema.org',
      '@type' => 'WebSite',
      'name' => $siteName,
      'url' => ($__rootAbs ?: ($baseUrl ?: '')) ?: '/',
    ];

    $schemaJson = json_encode([$schemaOrg, $schemaSite], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($schemaJson) {
      echo '<script' . $nonceAttr . ' type="application/ld+json">' . $schemaJson . '</script>';
    }
  ?>

  <?php
    
    if (isset($jsonLd) && is_array($jsonLd) && !empty($jsonLd)) {
      $extraJson = json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
      if ($extraJson) {
        echo '<script' . $nonceAttr . ' type="application/ld+json">' . $extraJson . '</script>';
      }
    }
  ?>

  <link rel="stylesheet" href="<?= h($asset('assets/css/app-core.bundle.css')) ?>">
  <?php if ($pageDir === 'rtl'): ?>
    <link rel="stylesheet" href="<?= h($asset('assets/css/app-rtl.bundle.css')) ?>">
  <?php endif; ?>

  <?php require __DIR__ . '/theme_head.php'; ?>

  <link rel="stylesheet" href="<?= h($asset('assets/css/app-layout.bundle.css')) ?>">
  <link rel="stylesheet" href="<?= h($asset('assets/css/responsive-fixes.css')) ?>">
  <link rel="stylesheet" href="<?= h($asset('assets/css/auth.css')) ?>">

  <!-- Hotfixes: readable text in LIGHT mode across themes (article/report pages). -->
  <link rel="stylesheet" href="<?= h($asset('assets/css/godyar-hotfixes.css')) ?>">
<?php
    $envPath = '/assets/js/gdy-env.js';
    $envDisk = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/') . $envPath;
    $envV = is_file($envDisk) ? (string)filemtime($envDisk) : $assetFallbackVer;
  ?>
  <script<?= $nonceAttr ?> src="<?= h(($baseUrl ?: '') . $envPath . '?v=' . rawurlencode($envV)) ?>" defer></script>

  <script defer src="<?= h($asset('assets/js/gdy-overlay-search.js')) ?>"></script>
  <script defer src="<?= h($asset('assets/js/godyar-search-suggest.js')) ?>"></script>
</head>

<body class="<?= h($themeBodyClass) ?><?= ($pageDir === 'rtl') ? ' rtl' : '' ?>"
      data-auth="<?= $isLoggedIn ? '1' : '0' ?>"
      data-user-id="<?= (int)$userId ?>"
      data-admin="<?= $isAdmin ? '1' : '0' ?>">

<header class="site-header" data-hdr-root>
  <div class="container">
    <div class="header-inner">
      <a href="<?= h((($__navRoot ?: $rootUrl) ?: '/') . '/') ?>" class="brand-block">
        <div class="brand-logo" aria-hidden="true">
          <?php if ($logoUrl): ?>
            <img src="<?= h($logoUrl) ?>" alt="<?= h($siteName) ?>" loading="lazy">
          <?php else: ?>
            <span class="brand-logo__fallback" aria-hidden="true"><?= h($logoFallbackChar) ?></span>
          <?php endif; ?>
        </div>

        <div class="brand-text">
          <div class="brand-title"><?= h($siteName) ?></div>
          <?php if ($siteSubtitle !== ''): ?>
            <div class="brand-subtitle"><?= h($siteSubtitle) ?></div>
          <?php endif; ?>
        </div>
      </a>
<div class="hdr-utils">
        <button type="button" class="hdr-dd-btn hdr-search-btn" title="بحث" aria-label="بحث" data-search-open>
          <svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="<?= h($asset('assets/icons/godyar-icons.svg')) ?>#search"></use></svg>
        </button>

<div class="hdr-dropdown hdr-lang" id="gdyLangDd">
          <button type="button" class="hdr-dd-btn" aria-haspopup="menu" aria-expanded="false" title="Language" data-hdr-dd>
            <svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="<?= h($asset('assets/icons/godyar-icons.svg')) ?>#globe"></use></svg>
            <span><?= h(strtoupper($pageLang)) ?></span>
            <svg class="gdy-icon chev" aria-hidden="true" focusable="false"><use href="<?= h($asset('assets/icons/godyar-icons.svg')) ?>#chevron-down"></use></svg>
          </button>
          <div class="hdr-dd-menu" role="menu" role="region" role="region" role="region" role="region" role="region" aria-label="&#039; . htmlspecialchars($label, ENT_QUOTES, &#039;UTF-8&#039;) . &#039;">
            <a role="menuitem" href="<?= h($langUrl('ar')) ?>" class="<?= $pageLang === 'ar' ? 'active' : '' ?>"><span>AR</span><span>العربية</span></a>
            <a role="menuitem" href="<?= h($langUrl('en')) ?>" class="<?= $pageLang === 'en' ? 'active' : '' ?>"><span>EN</span><span>English</span></a>
            <a role="menuitem" href="<?= h($langUrl('fr')) ?>" class="<?= $pageLang === 'fr' ? 'active' : '' ?>"><span>FR</span><span>Français</span></a>
          </div>
        </div>

        <button type="button" class="hdr-dd-btn hdr-theme-btn" id="gdyThemeToggle" title="الوضع الليلي" aria-pressed="false" data-gdy-theme-toggle>
          <svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="<?= h($asset('assets/icons/godyar-icons.svg')) ?>#moon"></use></svg>
        </button>

        <div class="hdr-dropdown hdr-user" id="gdyUserDd">
          <button type="button" class="hdr-dd-btn" aria-haspopup="menu" aria-expanded="false" data-hdr-dd>
            <svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="<?= h($asset('assets/icons/godyar-icons.svg')) ?>#user"></use></svg>
            <span>الحساب</span>
            <svg class="gdy-icon chev" aria-hidden="true" focusable="false"><use href="<?= h($asset('assets/icons/godyar-icons.svg')) ?>#chevron-down"></use></svg>
          </button>
          <div class="hdr-dd-menu" role="menu" role="region" role="region" role="region" role="region" role="region" aria-label="&#039; . htmlspecialchars($label, ENT_QUOTES, &#039;UTF-8&#039;) . &#039;">
            <?php if ($isLoggedIn): ?>
              <a role="menuitem" href="<?= h(($baseUrl ?: '') . '/my') ?>">
                <span>الملف الشخصي</span>
                <svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="<?= h($asset('assets/icons/godyar-icons.svg')) ?>#user"></use></svg>
              </a>
              <a role="menuitem" href="<?= h(($baseUrl ?: '') . '/logout') ?>">
                <span>تسجيل الخروج</span>
                <svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="<?= h($asset('assets/icons/godyar-icons.svg')) ?>#logout"></use></svg>
              </a>
              <?php if ($isAdmin): ?>
                <a role="menuitem" href="<?= h(($baseUrl ?: '') . '/admin') ?>">
                  <span>لوحة التحكم</span>
                  <svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="<?= h($asset('assets/icons/godyar-icons.svg')) ?>#settings"></use></svg>
                </a>
              <?php endif; ?>
            <?php else: ?>
              <a role="menuitem" href="<?= h(($baseUrl ?: '') . '/login') ?>">
                <span>تسجيل الدخول</span>
                <svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="<?= h($asset('assets/icons/godyar-icons.svg')) ?>#login"></use></svg>
              </a>
              <a role="menuitem" href="<?= h(($baseUrl ?: '') . '/register') ?>">
                <span>إنشاء حساب</span>
                <svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="<?= h($asset('assets/icons/godyar-icons.svg')) ?>#user"></use></svg>
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="header-secondary" role="navigation" role="region" role="region" role="region" role="region" role="region" aria-label="&#039; . htmlspecialchars($label, ENT_QUOTES, &#039;UTF-8&#039;) . &#039;">
    <div class="container">
      <div class="header-secondary-inner">
        <?php
          
          
          
          
          
          $__hdrCats = [];
          try {
            if (isset($pdo) && ($pdo instanceof PDO)) {
              
              try {
                $__st = $pdo->query("SELECT c.id, c.name, c.slug,
  (
    SELECT COUNT(1) FROM news n
    WHERE n.category_id = c.id
      AND (n.deleted_at IS NULL)
      AND (
        (n.status = 'published')
        OR (n.is_published = 1)
        OR (n.status IS NULL AND n.is_published IS NULL)
      )
  ) AS cnt
FROM categories c
WHERE (c.is_active = 1 OR c.is_active IS NULL)
  AND (c.status = 'active' OR c.status IS NULL OR c.status = '')
ORDER BY c.sort_order ASC, c.id ASC
LIMIT 12");
                $__hdrCats = $__st ? (array)$__st->fetchAll(PDO::FETCH_ASSOC) : [];
              } catch (\Throwable $__e1) {
                $__st = $pdo->query("SELECT id, name, slug FROM categories ORDER BY sort_order ASC, id ASC LIMIT 12");
                $__hdrCats = $__st ? (array)$__st->fetchAll(PDO::FETCH_ASSOC) : [];
              }
            }
            if (empty($__hdrCats) && !empty($categories) && is_array($categories)) {
              foreach ($categories as $__c) {
                if (!is_array($__c)) { continue; }
                $__hdrCats[] = [
                  'id' => (int)($__c['id'] ?? 0),
                  'name' => (string)($__c['name'] ?? ''),
                  'slug' => (string)($__c['slug'] ?? ''),
                  'cnt' => (int)($__c['cnt'] ?? 0),
                ];
              }
            }
          } catch (\Throwable $__e) {
            $__hdrCats = [];
          }
        ?>

        <nav class="quick-nav" aria-label="<?= h(__('nav.quick_links', 'روابط سريعة')) ?>">
          <a class="quick-nav__link" href="<?= h(($__navRoot ?: ($rootUrl ?: '/')) . '/') ?>"><?= h(gdy_clean_text($t_home ?? __('nav.home', 'الرئيسية'))) ?></a>
          <a class="quick-nav__link" href="<?= h(($__navRoot ?: ($baseUrl ?: '')) . '/news') ?>"><?= h(gdy_clean_text($t_news ?? __('nav.news', 'الأخبار'))) ?></a>

<?php
  
  
  
  
  $__catLinks = [];
  if (!empty($__hdrCats) && is_array($__hdrCats)) {
    foreach ($__hdrCats as $__c) {
      if (!is_array($__c)) continue;
      $__slug = (string)($__c['slug'] ?? '');
      $__name = (string)($__c['name'] ?? '');
      $__cnt  = (int)($__c['cnt'] ?? 0);
      if ($__slug === '' || $__name === '') continue;
      $__href = rtrim(($__navRoot ?: ($baseUrl ?: '')), '/') . '/category/' . rawurlencode($__slug);
      $__catLinks[] = ['name' => $__name, 'href' => $__href, 'cnt' => $__cnt];
    }
  }

  
  $__catPills = array_slice($__catLinks, 0, 4);
  $__catMore  = array_slice($__catLinks, 4);

  ?>

  <?php foreach ($__catPills as $__i => $__c):
    $cls = 'quick-nav__link quick-nav__link--cat';
    if ($__i === 0) { $cls .= ' quick-nav__link--cat-primary'; }
  ?>
    <a class="<?= h($cls) ?>" href="<?= h($__c['href']) ?>"><span class="nav-text"><?= h(gdy_clean_text($__c['name'])) ?></span><?php if (!empty($__c['cnt'])): ?><span class="nav-count" aria-hidden="true"><?= (int)$__c['cnt'] ?></span><?php endif; ?></a>
  <?php endforeach; ?>

<?php if (!empty($__catMore)): ?>
  <div class="quick-nav__more" data-more-menu>
    <button type="button" class="quick-nav__more-btn" aria-haspopup="true" aria-expanded="false" data-more-btn>
      <?= h(gdy_clean_text($t_more ?? __('nav.more', 'المزيد'))) ?>
      <svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="<?= h($asset('assets/icons/godyar-icons.svg')) ?>#chevron-down"></use></svg>
    </button>
    
<div class="quick-nav__more-menu quick-nav__mega" role="menu" data-more-menu-panel>
  <?php
    $__allCatsForMega = $__catLinks;
    $__featured = array_slice($__allCatsForMega, 0, 4);
    $__rest = array_slice($__allCatsForMega, 4);
    $__half = (int)ceil(count($__rest)/2);
    $__left = array_slice($__rest, 0, $__half);
    $__right = array_slice($__rest, $__half);
  ?>
  <div class="quick-nav__mega-head">
    <div class="quick-nav__mega-title"><?= h(gdy_clean_text($t_featured ?? __('nav.featured_sections', 'أبرز الأقسام'))) ?></div>
    <div class="quick-nav__mega-featured">
      <?php foreach ($__featured as $__c): ?>
        <a role="menuitem" class="quick-nav__mega-chip" href="<?= h($__c['href']) ?>"><?= h(gdy_clean_text($__c['name'])) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="quick-nav__mega-cols">
    <div class="quick-nav__mega-col">
      <?php foreach ($__left as $__c): ?>
        <a role="menuitem" class="quick-nav__more-item" href="<?= h($__c['href']) ?>"><span class="nav-text"><?= h(gdy_clean_text($__c['name'])) ?></span><?php if (!empty($__c['cnt'])): ?><span class="nav-count" aria-hidden="true"><?= (int)$__c['cnt'] ?></span><?php endif; ?></a>
      <?php endforeach; ?>
    </div>
    <div class="quick-nav__mega-col">
      <?php foreach ($__right as $__c): ?>
        <a role="menuitem" class="quick-nav__more-item" href="<?= h($__c['href']) ?>"><span class="nav-text"><?= h(gdy_clean_text($__c['name'])) ?></span><?php if (!empty($__c['cnt'])): ?><span class="nav-count" aria-hidden="true"><?= (int)$__c['cnt'] ?></span><?php endif; ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

  </div>
<?php endif; ?>
          <a class="quick-nav__link" href="<?= h(($__navRoot ?: ($baseUrl ?: '')) . '/archive') ?>"><?= h(gdy_clean_text($t_archive ?? __('nav.archive', 'الأرشيف'))) ?></a>
          <a class="quick-nav__link" href="<?= h(($__navRoot ?: ($baseUrl ?: '')) . '/page/contact') ?>"><?= h(gdy_clean_text($t_contact ?? __('nav.contact', 'تواصل'))) ?></a>
        </nav>
      </div>
    </div>
  </div>

  <!-- Search Overlay (BBC-style) -->
  <div class="gdy-search-overlay" data-search-overlay aria-hidden="true">
    <div class="gdy-search-overlay__backdrop" data-search-close></div>
    <div class="gdy-search-overlay__panel" role="dialog" aria-modal="true" role="region" role="region" role="region" role="region" role="region" aria-label="&#039; . htmlspecialchars($label, ENT_QUOTES, &#039;UTF-8&#039;) . &#039;">
      <form class="gdy-search-overlay__form" action="<?= h(($__navRoot ?: ($baseUrl ?: '')) . '/search') ?>" method="get" role="search">
        <label class="visually-hidden" for="gdyOverlaySearchQ"><?= h($searchLabel ?? __('search.label', 'بحث')) ?></label>
        <span class="gdy-search-overlay__icon" aria-hidden="true">
          <svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="<?= h($asset('assets/icons/godyar-icons.svg')) ?>#search"></use></svg>
        </span>
        <input id="gdyOverlaySearchQ" name="q" type="search" placeholder="<?= h($searchPlaceholder ?? __('search.placeholder_newsroom', 'ابحث في الأخبار، التقارير، والتحليلات')) ?>" autocomplete="off" inputmode="search">
        <button type="button" class="gdy-search-overlay__close" data-search-close aria-label="<?= h(__('action.close', 'إغلاق')) ?>">
          <svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="<?= h($asset('assets/icons/godyar-icons.svg')) ?>#close"></use></svg>
        </button>
      </form>
      <div class="gdy-search-overlay__suggest" data-search-suggest></div>
    </div>
  </div>

</header>

<?php
  
  $__breaking = null;
  if (isset($pdo) && ($pdo instanceof PDO)) {
    try {
      $__stmt = $pdo->query("
        SELECT id, title, slug
        FROM news
        WHERE status = 'published'
          AND deleted_at IS NULL
          AND (publish_at IS NULL OR publish_at <= NOW())
          AND is_breaking = 1
        ORDER BY COALESCE(publish_at, published_at, created_at) DESC
        LIMIT 1
      ");
      $__breaking = $__stmt ? $__stmt->fetch(PDO::FETCH_ASSOC) : null;
    } catch (Throwable $__e) { $__breaking = null; }
  }
?>
<?php if (!empty($__breaking['title']) && !empty($__breaking['slug'])): ?>
  <div class="gdy-breaking-ticker" role="region" role="region" role="region" role="region" role="region" role="region" aria-label="&#039; . htmlspecialchars($label, ENT_QUOTES, &#039;UTF-8&#039;) . &#039;">
    <div class="container">
      <div class="gdy-breaking-ticker__inner">
        <span class="gdy-breaking-ticker__badge"><?= h(__('breaking.label', 'عاجل')) ?></span>
        <a class="gdy-breaking-ticker__link" href="<?= h(rtrim(($__navRoot ?: ($baseUrl ?: '')), '/') . '/news/' . rawurlencode((string)$__breaking['slug'])) ?>">
          <?= h((string)$__breaking['title']) ?>
        </a>
      </div>
    </div>
  </div>
<?php endif; ?>

<div class="gdy-progress" id="gdyProgress"></div>