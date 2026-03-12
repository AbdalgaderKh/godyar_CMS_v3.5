<?php

require_once __DIR__ . '/../_admin_guard.php';
require_once __DIR__ . '/../../includes/lang.php';

if (!function_exists('h')) {
    function h($v): string {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

$cspNonce = $cspNonce ?? (defined('GDY_CSP_NONCE') ? (string) GDY_CSP_NONCE : '');

$lang = function_exists('gdy_lang') ? (string) gdy_lang() : (string) ($_SESSION['lang'] ?? 'ar');
$lang = strtolower(trim($lang));
if (!in_array($lang, ['ar', 'en', 'fr'], true)) {
    $lang = 'ar';
}
$dir = ($lang === 'ar') ? 'rtl' : 'ltr';

$base = '';
if (defined('ROOT_URL')) {
    $base = rtrim((string) ROOT_URL, '/');
} elseif (defined('BASE_URL')) {
    $base = rtrim((string) BASE_URL, '/');
} elseif (defined('GODYAR_BASE_URL')) {
    $base = rtrim((string) GODYAR_BASE_URL, '/');
}

if ($base === '') {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    if ($host !== '') {
        $base = $scheme . '://' . $host;
    }
}

if ($base !== '') {
    $scheme = parse_url($base, PHP_URL_SCHEME);
    $host   = parse_url($base, PHP_URL_HOST);
    $port   = parse_url($base, PHP_URL_PORT);
    $path   = (string) (parse_url($base, PHP_URL_PATH) ?? '');
    $path = rtrim($path, '/');

    if (preg_match('~/(admin)$~i', $path)) {
        $path = preg_replace('~/admin$~i', '', $path);
    }
    if (preg_match('~/(ar|en|fr)$~i', $path)) {
        $path = preg_replace('~/(ar|en|fr)$~i', '', $path);
    }

    if ($scheme && $host) {
        $base = $scheme . '://' . $host . ($port ? ':' . $port : '') . $path;
    } else {
        $base = $path;
    }
}

$adminBase = ($base !== '' ? $base : '') . '/admin';

$pageTitle   = $pageTitle ?? (function_exists('__') ? __('dashboard', [], 'لوحة التحكم') : 'لوحة التحكم');
$pageHead    = $pageHead ?? '';
$pageScripts = $pageScripts ?? '';

$root = defined('ROOT_PATH') ? (string) ROOT_PATH : (string) dirname(__DIR__, 2);
$cssFileName = ($dir === 'rtl') ? 'bootstrap.rtl.min.css' : 'bootstrap.min.css';
$jsFileName  = 'bootstrap.bundle.min.js';

$localCssFile = rtrim($root, '/\\') . '/assets/vendor/bootstrap/css/' . $cssFileName;
$localJsFile  = rtrim($root, '/\\') . '/assets/vendor/bootstrap/js/' . $jsFileName;

$bootstrapCss = is_file($localCssFile) ? ($base . '/assets/vendor/bootstrap/css/' . $cssFileName) : ('/assets/vendor/bootstrap/css/' . $cssFileName);
$bootstrapJs  = is_file($localJsFile)  ? ($base . '/assets/vendor/bootstrap/js/' . $jsFileName)  : ('/assets/vendor/bootstrap/js/' . $jsFileName);

$uiCssPath      = __DIR__ . '/../assets/css/admin-ui.css';
$shellCssPath   = __DIR__ . '/../assets/css/admin-shell.css';
$sidebarCssPath = __DIR__ . '/../assets/css/admin-sidebar.css';

$uiVer      = is_file($uiCssPath) ? (string) filemtime($uiCssPath) : (string) time();
$shellVer   = is_file($shellCssPath) ? (string) filemtime($shellCssPath) : (string) time();
$sidebarVer = is_file($sidebarCssPath) ? (string) filemtime($sidebarCssPath) : (string) time();

$adminTheme = 'blue';
$__adminThemeFound = false;
$pdo = null;

try {
    if (class_exists('Godyar\\DB') && method_exists('Godyar\\DB', 'pdoOrNull')) {
        $pdo = \Godyar\DB::pdoOrNull();
        if ($pdo instanceof \PDO) {
            $col = function_exists('gdy_settings_value_column') ? gdy_settings_value_column($pdo) : 'value';

            $hasUpdatedAt = false;
            try {
                $cols = $pdo->query('SHOW COLUMNS FROM settings')->fetchAll(\PDO::FETCH_COLUMN);
                $hasUpdatedAt = (is_array($cols) && in_array('updated_at', $cols, true));
            } catch (\Throwable $e) {
                $hasUpdatedAt = false;
            }

            $sql = "SELECT {$col} FROM settings WHERE setting_key = 'admin.theme'";
            if ($hasUpdatedAt) {
                $sql .= ' ORDER BY updated_at DESC';
            }
            $sql .= ' LIMIT 1';

            $st = $pdo->prepare($sql);
            $st->execute();
            $raw = $st->fetchColumn();

            if ($raw !== false && $raw !== null && trim((string)$raw) !== '') {
                $v = strtolower(trim((string)$raw));
                if (in_array($v, ['default', 'blue', 'red', 'green', 'brown', 'purple', 'orange', 'teal', 'pink'], true)) {
                    $adminTheme = $v;
                    $__adminThemeFound = true;
                }
            }
        }
    }
} catch (\Throwable $e) {
}

if (!$__adminThemeFound) {
    $siteThemePath = '';

    try {
        if ($pdo instanceof \PDO) {
            $col = function_exists('gdy_settings_value_column') ? gdy_settings_value_column($pdo) : 'value';
            $st2 = $pdo->prepare("SELECT {$col} FROM settings WHERE setting_key = 'site_theme' LIMIT 1");
            $st2->execute();
            $siteThemePath = (string)($st2->fetchColumn() ?: '');
        }
    } catch (\Throwable $e) {
    }

    if (!$siteThemePath) {
        $cfg = __DIR__ . '/../../storage/config/site_theme.php';
        if (is_file($cfg)) {
            $v = trim((string) @file_get_contents($cfg));
            if ($v !== '') {
                $siteThemePath = $v;
            }
        }
    }

    $t = strtolower(basename($siteThemePath));
    $name = '';
    if (preg_match('/theme-([a-z0-9_-]+)\.css/', $t, $mm)) {
        $name = $mm[1];
    }

    $map = [
        'default' => 'default',
        'red'     => 'red',
        'blue'    => 'blue',
        'green'   => 'green',
        'brown'   => 'brown',
        'purple'  => 'purple',
        'orange'  => 'orange',
        'teal'    => 'teal',
        'pink'    => 'pink',
        'beige'   => 'brown',
        'emerald' => 'green',
        'light'   => 'default',
        'core'    => 'default',
    ];

    if ($name && isset($map[$name])) {
        $adminTheme = $map[$name];
    }
}

$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

$godyarBaseUrl = $base ?: '';
$gdyAdminUrl   = $adminBase;
$gdyIconSprite = ($base ?: '') . '/assets/icons/gdy-icons.svg';

?><!doctype html>
<html lang="<?php echo h($lang); ?>" dir="<?php echo h($dir); ?>" data-admin-theme="<?php echo h($adminTheme); ?>" data-admin-mode="light">
<head><meta charset="utf-8">
<title><?php echo h($pageTitle); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <?php if (function_exists('csrf_token')): ?>
    <meta name="csrf-token" content="<?php echo h(csrf_token()); ?>">
  <?php endif; ?>

  <script nonce="<?php echo h($cspNonce); ?>">
    window.GODYAR_BASE_URL = <?php echo json_encode($godyarBaseUrl, $jsonFlags); ?>;
    window.GDY_ADMIN_URL   = <?php echo json_encode($gdyAdminUrl, $jsonFlags); ?>;
    window.GDY_ICON_SPRITE = <?php echo json_encode($gdyIconSprite, $jsonFlags); ?>;

    (function(){
      try {
        var key = 'godyar_admin_mode';
        var saved = localStorage.getItem(key);
        var mode = (saved === 'dark' || saved === 'light') ? saved : 'light';
        document.documentElement.setAttribute('data-admin-mode', mode);

        document.addEventListener('DOMContentLoaded', function(){
          var btn = document.getElementById('adminModeToggle');
          if (!btn) return;
          btn.addEventListener('click', function(){
            var cur = document.documentElement.getAttribute('data-admin-mode') || 'light';
            var next = (cur === 'dark') ? 'light' : 'dark';
            document.documentElement.setAttribute('data-admin-mode', next);
            try { localStorage.setItem(key, next); } catch(e) {}
          });
        });
      } catch(e) {}
    })();

    (function(){
      function patch(){
        try {
          var sprite = window.GDY_ICON_SPRITE;
          if (!sprite) return;
          var uses = document.querySelectorAll('svg use');
          uses.forEach(function(u){
            var href = u.getAttribute('href') || u.getAttribute('xlink:href') || '';
            if (href && href.charAt(0) === '#') {
              var full = sprite + href;
              u.setAttribute('href', full);
              u.setAttribute('xlink:href', full);
            }
          });
        } catch(e) {}
      }
      if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', patch);
      else patch();
    })();
  </script>

  <link rel="stylesheet" href="<?php echo h($bootstrapCss); ?>">
  <link rel="stylesheet" href="<?php echo h($adminBase . '/assets/css/admin-ui.css?v=' . $uiVer); ?>">
  <link rel="stylesheet" href="<?php echo h($adminBase . '/assets/css/admin-shell.css?v=' . $shellVer); ?>">
  <link rel="stylesheet" href="<?php echo h($adminBase . '/assets/css/admin-sidebar.css?v=' . $sidebarVer); ?>">
  <link rel="stylesheet" href="<?php echo h($adminBase . '/themes/dark-pro/dark-pro.css?v=' . (string)@filemtime(__DIR__ . '/../themes/dark-pro/dark-pro.css')); ?>">
  <link rel="stylesheet" href="<?php echo h($adminBase . '/assets/css/admin-brand.css?v=' . $uiVer); ?>">
  <link rel="stylesheet" href="<?php echo h($adminBase . '/assets/css/admin-settings.css?v=' . $uiVer); ?>">

  <?php if ($pageHead !== '') { echo $pageHead; } ?>
</head>
<body class="gdy-theme-dark-pro">

<?php if (function_exists('csrf_token')): ?>
  <input type="hidden" id="gdyGlobalCsrfToken" value="<?php echo h(csrf_token()); ?>" style="display:none">
<?php endif; ?>

<script nonce="<?php echo h($cspNonce); ?>" src="<?php echo h($bootstrapJs); ?>"></script>
<script nonce="<?php echo h($cspNonce); ?>" src="<?php echo h($adminBase . '/themes/dark-pro/dark-pro.js?v=' . (string)@filemtime(__DIR__ . '/../themes/dark-pro/dark-pro.js')); ?>"></script>