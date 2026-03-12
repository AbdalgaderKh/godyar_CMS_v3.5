<?php

if (!function_exists('h')) {
  function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}
$__uName = $_SESSION['user']['name'] ?? ($_SESSION['user']['display_name'] ?? 'مشرف النظام');
$__base = function_exists('site_url') ? rtrim((string)site_url(), '/') : (function_exists('base_url') ? rtrim((string)base_url(), '/') : '');
$__adminBase = $__base . '/admin';
?>
<div class="gdy-topbar" role="banner">
  <div class="gdy-topbar__inner">
    <div class="gdy-topbar__left">
      <button class="gdy-topbar__burger" type="button" id="gdyTopbarBurger" aria-label="القائمة">
        <svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#menu"></use></svg>
      </button>
      <div class="gdy-topbar__search">
        <input id="gdyTopbarSearch" type="search" placeholder="بحث سريع..." autocomplete="off">
      </div>
    </div>

    <div class="d-flex align-items-center gap-2">
      <a class="btn btn-sm btn-outline-primary" href="<?php echo h($__base); ?>/">عرض الموقع</a>
      <button class="btn btn-sm btn-outline-secondary" type="button" id="adminModeToggle" aria-label="الوضع الليلي">
        <svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#moon"></use></svg>
      </button>
      <div class="gdy-pill d-none d-md-inline-flex" style="border:1px solid rgba(148,163,184,.16);background:rgba(15,23,42,.35);border-radius:999px;padding:.25rem .6rem;color:rgba(226,232,240,.92);font-weight:800;">
        <?php echo h((string)$__uName); ?>
      </div>
    </div>
  </div>
</div>