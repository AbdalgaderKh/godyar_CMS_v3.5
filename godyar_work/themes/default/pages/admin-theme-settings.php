<section class="g-card"> 
    <div class="g-admin-head"><div><h1>إعدادات الثيم</h1><p>تحكم سريع في شكل الواجهة الافتراضي بدون تعديل يدوي للملفات.</p></div><div class="g-inline-stats"><a class="g-btn g-btn-soft" href="<?= e(godyar_v4_base_url('/v4/admin/theme-customizer')) ?>">المخصص المباشر</a></div></div>
    <?php if (!empty($flash)): ?><div class="g-alert g-alert-<?= e($flash['type'] ?? 'info') ?>"><?= e($flash['message'] ?? '') ?></div><?php endif; ?>
    <form class="g-theme-form" method="post" action="<?= e(godyar_v4_base_url('/v4/admin/theme-settings/save')) ?>"> 
        <?= godyar_v4_csrf_field() ?>
        <div class="g-grid g-grid-3">
            <label>اللون الأساسي<input type="color" name="primary_color" value="<?= e($rows['primary_color'] ?? '#0f766e') ?>"></label>
            <label>لون الخلفية<input type="color" name="background_color" value="<?= e($rows['background_color'] ?? '#f7f9fc') ?>"></label>
            <label>لون النص<input type="color" name="text_color" value="<?= e($rows['text_color'] ?? '#152033') ?>"></label>
            <label>لون البطاقات<input type="color" name="card_color" value="<?= e($rows['card_color'] ?? '#ffffff') ?>"></label>
            <label>لون النص الثانوي<input type="color" name="muted_color" value="<?= e($rows['muted_color'] ?? '#61708a') ?>"></label>
            <label>لون الحدود<input type="color" name="border_color" value="<?= e($rows['border_color'] ?? '#dce5f1') ?>"></label>
            <label>عرض المحتوى<input type="text" name="shell_width" value="<?= e($rows['shell_width'] ?? '1120px') ?>" placeholder="1120px"></label>
            <label>استدارة البطاقات<input type="text" name="radius" value="<?= e($rows['radius'] ?? '20px') ?>" placeholder="20px"></label>
            <label>الوضع الافتراضي<select name="dark_mode_default"><option value="light" <?= (($rows['dark_mode_default'] ?? 'light') === 'light') ? 'selected' : '' ?>>فاتح</option><option value="dark" <?= (($rows['dark_mode_default'] ?? 'light') === 'dark') ? 'selected' : '' ?>>داكن</option></select></label>
            <label>تخطيط الهيدر<select name="header_layout"><option value="default" <?= (($rows['header_layout'] ?? 'default') === 'default') ? 'selected' : '' ?>>افتراضي</option><option value="centered" <?= (($rows['header_layout'] ?? 'default') === 'centered') ? 'selected' : '' ?>>مركزي</option><option value="split" <?= (($rows['header_layout'] ?? 'default') === 'split') ? 'selected' : '' ?>>منفصل</option></select></label>
            <label>تخطيط الفوتر<select name="footer_layout"><option value="default" <?= (($rows['footer_layout'] ?? 'default') === 'default') ? 'selected' : '' ?>>افتراضي</option><option value="centered" <?= (($rows['footer_layout'] ?? 'default') === 'centered') ? 'selected' : '' ?>>مركزي</option><option value="split" <?= (($rows['footer_layout'] ?? 'default') === 'split') ? 'selected' : '' ?>>منفصل</option></select></label>
            <label>نص الشعار<input type="text" name="logo_text" value="<?= e($rows['logo_text'] ?? '') ?>" placeholder="يظهر بدل اسم الموقع عند الحاجة"></label>
            <label class="g-span-3">نص الشريط العاجل<input type="text" name="breaking_text" value="<?= e($rows['breaking_text'] ?? '') ?>"></label>
            <label class="g-span-3">ملاحظة الفوتر<textarea name="footer_note" rows="3"><?= e($rows['footer_note'] ?? '') ?></textarea></label>
        </div>
        <div class="g-inline-stats g-check-row">
            <label class="g-check"><input type="checkbox" name="show_breaking_bar" value="1" <?= !empty($rows['show_breaking_bar']) ? 'checked' : '' ?>> إظهار الشريط العاجل</label>
            <label class="g-check"><input type="checkbox" name="show_reading_progress" value="1" <?= !empty($rows['show_reading_progress']) ? 'checked' : '' ?>> إظهار شريط التقدم</label>
            <label class="g-check"><input type="checkbox" name="show_sticky_header" value="1" <?= !empty($rows['show_sticky_header']) ? 'checked' : '' ?>> تثبيت الهيدر</label>
            <label class="g-check"><input type="checkbox" name="compact_cards" value="1" <?= !empty($rows['compact_cards']) ? 'checked' : '' ?>> بطاقات مضغوطة</label>
        </div>
        <div class="g-inline-stats"><button class="g-btn" type="submit">حفظ الإعدادات</button></div>
    </form>
</section>
