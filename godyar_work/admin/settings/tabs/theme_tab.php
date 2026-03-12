<?php

$csrf_token = function_exists('csrf_token') ? csrf_token() : ($_SESSION['csrf_token'] ?? '');

$themes = [
  'dark-pro' => 'Dark Pro',
  'classic'  => 'Classic',
];

$activeTheme = function_exists('get_setting') ? (string)get_setting('admin_theme', 'dark-pro') : 'dark-pro';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (function_exists('verify_csrf') && !verify_csrf($_POST['csrf_token'] ?? '')) {
    $flash_error = 'CSRF token غير صالح.';
  } else {
    $newTheme = (string)($_POST['admin_theme'] ?? 'dark-pro');
    if (!array_key_exists($newTheme, $themes)) $newTheme = 'dark-pro';

    if (function_exists('set_setting')) {
      set_setting('admin_theme', $newTheme);
      $activeTheme = $newTheme;
      $flash_success = 'تم حفظ إعدادات المظهر بنجاح.';
    } else {
      $flash_error = 'تعذّر حفظ الإعدادات: دالة set_setting غير متاحة.';
    }
  }
}
?>

<div class="card gdy-card">
  <div class="gdy-card-header"><strong>المظهر</strong></div>
  <div class="card-body">

    <?php if (!empty($flash_success)): ?>
      <div class="alert alert-success mb-3"><?= h($flash_success) ?></div>
    <?php endif; ?>

    <?php if (!empty($flash_error)): ?>
      <div class="alert alert-danger mb-3"><?= h($flash_error) ?></div>
    <?php endif; ?>

    <form method="post" action="index.php?tab=theme" class="row g-3">
      <input type="hidden" name="csrf_token" value="<?= h($csrf_token) ?>">

      <div class="col-md-6">
        <label class="form-label gdy-form-label" for="adminThemeSelect">ثيم لوحة التحكم</label>
        <select class="form-select" id="adminThemeSelect" name="admin_theme" autocomplete="off">
          <?php foreach ($themes as $key => $label): ?>
            <option value="<?= h($key) ?>" <?= $key === $activeTheme ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="gdy-helper-text mt-1">اختر مظهر لوحة الإدارة.</div>
      </div>

      <div class="col-12 d-flex align-items-center gap-2">
        <button type="submit" class="btn btn-primary btn-sm">حفظ</button>
        <a class="btn btn-outline-secondary btn-sm" href="theme.php">فتح الصفحة الكاملة</a>
      </div>
    </form>

    <hr class="my-4">

    <div class="small text-muted">
      ملاحظة: إذا لم يظهر التغيير فوراً، جرّب تحديث الصفحة مع تفريغ الكاش (Ctrl+F5).
    </div>

  </div>
</div>
