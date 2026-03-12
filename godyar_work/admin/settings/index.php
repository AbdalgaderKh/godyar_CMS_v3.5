<?php

@ini_set('display_errors', '0');

$__gdy_app_start_candidates = [
  __DIR__ . '/../includes/app_start.php',
  dirname(__DIR__, 2) . '/includes/app_start.php',
  __DIR__ . '/../inc/app_start.php',
  dirname(__DIR__, 2) . '/inc/app_start.php',
  __DIR__ . '/../bootstrap/app_start.php',
  dirname(__DIR__, 2) . '/bootstrap/app_start.php',
  
  __DIR__ . '/../layout/app_start.php',
  dirname(__DIR__, 2) . '/admin/layout/app_start.php',
  dirname(__DIR__, 2) . '/layout/app_start.php',
];

$__gdy_app_start = null;
foreach ($__gdy_app_start_candidates as $__p) {
  if (is_file($__p)) { $__gdy_app_start = $__p; break; }
}

if ($__gdy_app_start) {
  require_once $__gdy_app_start;
} else {
  if (!headers_sent()) { http_response_code(500); }
  if (!defined('GODYAR_BASE_URL'))  define('GODYAR_BASE_URL', '');
  
  if (defined('GODYAR_BASE_URL') && GODYAR_BASE_URL === '') {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? '';
    if ($host) {
      define('GODYAR_BASE_URL', $scheme . '://' . $host);
    }
  }
  if (!function_exists('h')) {
    function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
  }
  $__gdy_admin_base = '/admin';
  $__gdy_bootstrap_error = "Godyar News Platform: لم يتم العثور على ملف الإقلاع app_start.php";
}

$__gdy_admin_base = (defined('GDY_ADMIN_URL') && GDY_ADMIN_URL) ? GDY_ADMIN_URL : '/admin';

if (!function_exists('h')) {
  function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
function __gdy_first_file(array $candidates){
  foreach ($candidates as $p) { if (is_file($p)) return $p; }
  return null;
}

$tab = isset($_GET['tab']) ? preg_replace('~[^a-z0-9_\-]~i', '', (string)$_GET['tab']) : '';

$tabMap = [
  'general'       => __DIR__ . '/general.php',
  'theme'         => __DIR__ . '/theme.php',
  'seo'           => __DIR__ . '/seo.php',
  'og'            => __DIR__ . '/og.php',
  'pwa'           => __DIR__ . '/pwa.php',
  'push'          => __DIR__ . '/push.php',
  'social'        => __DIR__ . '/social.php',
  'social_media'  => __DIR__ . '/social.php',
  'cache'         => __DIR__ . '/cache.php',
  'header'        => __DIR__ . '/header_footer.php',
  'footer'        => __DIR__ . '/header_footer.php',
  'header_footer' => __DIR__ . '/header_footer.php',
  'time_lang'     => __DIR__ . '/time_lang.php',
  'time'          => __DIR__ . '/time_lang.php',
];

$renderTab = ($tab && isset($tabMap[$tab]) && is_file($tabMap[$tab]));

if (!defined('GDY_ADMIN_BOOT')) {
  define('GDY_ADMIN_BOOT', true);
}

$__gdy_admin_header = __gdy_first_file([
  
  __DIR__ . '/../includes/admin_header.php',
  __DIR__ . '/../layout/admin_header.php',
  
  __DIR__ . '/../layout/header.php',
  __DIR__ . '/../layout/header.inc.php',
  __DIR__ . '/../layout/_header.php',
]);

$__gdy_admin_footer = __gdy_first_file([
  
  __DIR__ . '/../includes/admin_footer.php',
  __DIR__ . '/../layout/admin_footer.php',
  
  __DIR__ . '/../layout/footer.php',
  __DIR__ . '/../layout/footer.inc.php',
  __DIR__ . '/../layout/_footer.php',
]);

$GDY_PAGE_TITLE = 'الإعدادات';

if ($__gdy_admin_header) {
  require_once $__gdy_admin_header;
} else {
?><!doctype html>
<html lang="ar" dir="rtl">
<head><meta charset="utf-8">
<title><?= h($GDY_PAGE_TITLE) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="<?= h((defined('GODYAR_BASE_URL') ? GODYAR_BASE_URL : '')) ?>/assets/vendor/bootstrap/css/bootstrap.rtl.min.css">
  <style>
    body{background:#0b1120;color:#e5e7eb}
    .container{max-width:980px}
    .card{background:rgba(15,23,42,.95);border:1px solid rgba(148,163,184,.18);border-radius:18px}
    a{color:#93c5fd}
  </style>
</head>
<body>
<div class="container py-4">
<?php
}

?>
<link rel="stylesheet" href="<?= h($__gdy_admin_base) ?>/assets/css/admin-settings-hub.css?v=<?= (int)time() ?>">
<?php

if (isset($__gdy_bootstrap_error)): ?>
  <div class="alert alert-danger">
    <div class="fw-bold mb-1"><?= h($__gdy_bootstrap_error) ?></div>
    <div class="small text-muted mb-2">المسارات التي تم تجربتها:</div>
    <pre class="small mb-0" style="white-space:pre-wrap;"><?= h(implode("\n", $__gdy_app_start_candidates)) ?></pre>
  </div>
<?php endif; ?>

<?php

if (!$__gdy_admin_header || !$__gdy_admin_footer):
  $hdrCandidates = [
    __DIR__ . '/../includes/admin_header.php',
    __DIR__ . '/../layout/admin_header.php',
    __DIR__ . '/../layout/header.php',
  ];
  $ftrCandidates = [
    __DIR__ . '/../includes/admin_footer.php',
    __DIR__ . '/../layout/admin_footer.php',
    __DIR__ . '/../layout/footer.php',
  ];
?>
  <div class="alert alert-warning">
    <div class="fw-bold mb-1">Godyar News Platform: ملفات واجهة لوحة التحكم غير موجودة (admin_header/admin_footer أو header/footer).</div>
    <div class="small text-muted">Header candidates:</div>
    <pre class="small mb-2" style="white-space:pre-wrap;"><?= h(implode("\n", $hdrCandidates)) ?></pre>
    <div class="small text-muted">Footer candidates:</div>
    <pre class="small mb-0" style="white-space:pre-wrap;"><?= h(implode("\n", $ftrCandidates)) ?></pre>
  </div>
<?php endif; ?>

<?php
if ($renderTab):
  $tabs = [
    'general' => ['label' => 'الإعدادات العامة'],
    'theme'   => ['label' => 'المظهر'],
    'seo'     => ['label' => 'SEO'],
    'og'      => ['label' => 'OG'],
    'pwa'     => ['label' => 'PWA & Push'],
    'social'  => ['label' => 'السوشيال'],
    'header'  => ['label' => 'الهيدر والفوتر'],
    'cache'   => ['label' => 'الكاش'],
    'time'    => ['label' => 'الوقت واللغة'],
  ];
?>
<div class="gdy-admin-container">
  <div class="gdy-settings-tabs" role="navigation" role="region" role="region" role="region" role="region" role="region" aria-label="&#039; . htmlspecialchars($label, ENT_QUOTES, &#039;UTF-8&#039;) . &#039;">
    <?php foreach ($tabs as $key => $t):
      $isActive = ($key === $tab) || ($key === 'social' && $tab === 'social_media') || ($key === 'header' && $tab === 'header_footer') || ($key === 'time' && $tab === 'time_lang');
      $href = $__gdy_admin_base . '/settings/index.php?tab=' . urlencode($key);
    ?>
      <a class="gdy-settings-tab <?= $isActive ? 'is-active' : '' ?>" href="<?= h($href) ?>">
        <span class="gdy-settings-tab__label"><?= h($t['label']) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</div>
<?php
  include $tabMap[$tab];
else:
?>
<div class="gdy-admin-container">
  <div class="gdy-page-header">
    <h1 class="gdy-page-title h4 mb-2">لوحة الإعدادات</h1>
    <div class="text-muted">إدارة إعدادات الموقع بشكل منظم</div>
  </div>

  <div class="gdy-settings-hub">
    <a class="gdy-settings-card" href="<?= h($__gdy_admin_base) ?>/settings/index.php?tab=general">
      <div class="gdy-settings-card__title">الإعدادات العامة</div>
      <div class="gdy-settings-card__desc">اسم الموقع، البريد، خيارات عامة</div>
    </a>

    <a class="gdy-settings-card" href="<?= h($__gdy_admin_base) ?>/settings/index.php?tab=theme">
      <div class="gdy-settings-card__title">المظهر</div>
      <div class="gdy-settings-card__desc">الألوان، الثيم، نمط اللوحة</div>
    </a>

    <a class="gdy-settings-card" href="<?= h($__gdy_admin_base) ?>/settings/index.php?tab=seo">
      <div class="gdy-settings-card__title">SEO</div>
      <div class="gdy-settings-card__desc">محركات البحث، العناوين والوصف</div>
    </a>

    <a class="gdy-settings-card" href="<?= h($__gdy_admin_base) ?>/settings/index.php?tab=og">
      <div class="gdy-settings-card__title">OG</div>
      <div class="gdy-settings-card__desc">Open Graph لمشاركة الروابط</div>
    </a>

    <a class="gdy-settings-card" href="<?= h($__gdy_admin_base) ?>/settings/index.php?tab=pwa">
      <div class="gdy-settings-card__title">PWA & Push</div>
      <div class="gdy-settings-card__desc">إعدادات PWA والإشعارات</div>
    </a>

    <a class="gdy-settings-card" href="<?= h($__gdy_admin_base) ?>/settings/index.php?tab=social">
      <div class="gdy-settings-card__title">السوشيال</div>
      <div class="gdy-settings-card__desc">روابط الشبكات الاجتماعية</div>
    </a>

    <a class="gdy-settings-card" href="<?= h($__gdy_admin_base) ?>/settings/index.php?tab=header">
      <div class="gdy-settings-card__title">الهيدر والفوتر</div>
      <div class="gdy-settings-card__desc">أكواد وإضافات الرأس والتذييل</div>
    </a>

    <a class="gdy-settings-card" href="<?= h($__gdy_admin_base) ?>/settings/index.php?tab=cache">
      <div class="gdy-settings-card__title">الكاش</div>
      <div class="gdy-settings-card__desc">تفريغ وإعدادات التخزين المؤقت</div>
    </a>

    <a class="gdy-settings-card" href="<?= h($__gdy_admin_base) ?>/settings/index.php?tab=time">
      <div class="gdy-settings-card__title">الوقت واللغة</div>
      <div class="gdy-settings-card__desc">التوقيت واللغات المتاحة</div>
    </a>
  </div>
</div>
<?php
endif;

if ($__gdy_admin_footer) {
  require_once $__gdy_admin_footer;
} else {
?>
</div>
</body>
</html>
<?php
}
