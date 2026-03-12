<?php

$__gdy_embed = (defined('GDY_SETTINGS_EMBED') && GDY_SETTINGS_EMBED === true);
if ($__gdy_embed === false) {
    require_once __DIR__ . '/_settings_guard.php';
    require_once __DIR__ . '/_settings_meta.php';
    settings_apply_context();
    require_once __DIR__ . '/../layout/app_start.php';
}

$__gdy_tab = 'header_footer';

$notice = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($__gdy_embed === false || (string)($_POST['settings_tab'] ?? '') === $__gdy_tab)) {
    if (function_exists('verify_csrf') === true) { verify_csrf(); }

    try {
        settings_save([
            'advanced.extra_head' => (string)($_POST['extra_head_code'] ?? ''),
            'advanced.extra_body' => (string)($_POST['extra_body_code'] ?? ''),
        ]);
        $notice = __('t_f6425ed4f7', 'تم حفظ أكواد الهيدر/الفوتر بنجاح.');
    } catch (\Throwable $e) {
        $error = __('t_4fa410044f', 'حدث خطأ أثناء الحفظ.');
        error_log('[settings_header_footer] ' . $e->getMessage());
    }
}

$extra_head_code = settings_get('advanced.extra_head', '');
$extra_body_code = settings_get('advanced.extra_body', '');
?>

<div class = "row g-3">
    <div class = "col-md-3">
      <?php include __DIR__ . '/_settings_nav.php'; ?>
    </div>

    <div class = "col-md-9">
      <div class = "card p-4">
<?php if ((empty($notice) === false)): ?>
          <div class = "alert alert-success"><?php echo h($notice); ?></div>
        <?php endif; ?>
        <?php if ((empty($error) === false)): ?>
          <div class = "alert alert-danger"><?php echo h($error); ?></div>
        <?php endif; ?>

        <form method = "post">
          <?php if (function_exists('csrf_token') === true): ?>
            <input type = "hidden" name = "csrf_token" value = "<?php echo h(csrf_token()); ?>">
          <?php endif; ?>
          <input type = "hidden" name = "settings_tab" value = "<?php echo h($__gdy_tab); ?>">

          <div class = "mb-3">
            <label class = "form-label"><?php echo h(__('t_709326ebec', 'كود إضافي داخل &lt;head&gt;')); ?></label>
            <textarea class = "form-control" rows = "7" name = "extra_head_code"><?php echo h($extra_head_code); ?></textarea>
            <div class = "form-text"><?php echo h(__('t_c9593b4c69', 'مثال: أكواد التحقق، ميتا، خطوط…')); ?></div>
          </div>

          <div class = "mb-3">
            <label class = "form-label"><?php echo h(__('t_f02f35b274', 'كود إضافي قبل &lt;/body&gt;')); ?></label>
            <textarea class = "form-control" rows = "7" name = "extra_body_code"><?php echo h($extra_body_code); ?></textarea>
            <div class = "form-text"><?php echo h(__('t_22e2d23204', 'مثال: سكربتات، تتبع، شات…')); ?></div>
          </div>

          <button class = "btn btn-primary"><?php echo h(__('t_871a087a1d', 'حفظ')); ?></button>
        </form>
      </div>
    </div>
  </div>

<?php if ($__gdy_embed === false) { require_once __DIR__ . '/../layout/app_end.php'; } ?>
