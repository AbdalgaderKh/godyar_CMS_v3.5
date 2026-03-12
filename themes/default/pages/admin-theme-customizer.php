<section class="g-card"> 
    <div class="g-admin-head"><div><h1>المخصص المباشر للثيم</h1><p>عدّل الألوان والهوية واعرض معاينة فورية قبل الحفظ.</p></div><div class="g-inline-stats"><a class="g-btn g-btn-soft" href="<?= e(godyar_v4_base_url('/v4/admin/theme-settings')) ?>">الإعدادات التفصيلية</a></div></div>
    <?php if (!empty($flash)): ?><div class="g-alert g-alert-<?= e($flash['type'] ?? 'info') ?>"><?= e($flash['message'] ?? '') ?></div><?php endif; ?>
    <form class="g-customizer-layout" method="post" action="<?= e(godyar_v4_base_url('/v4/admin/theme-customizer/save')) ?>">
        <?= godyar_v4_csrf_field() ?>
        <div class="g-card g-customizer-controls" data-customizer-root>
            <label>اللون الأساسي<input data-preview-var="--g-primary" type="color" name="primary_color" value="<?= e($rows['primary_color'] ?? '#0f766e') ?>"></label>
            <label>الخلفية<input data-preview-var="--g-bg" type="color" name="background_color" value="<?= e($rows['background_color'] ?? '#f7f9fc') ?>"></label>
            <label>النص<input data-preview-var="--g-text" type="color" name="text_color" value="<?= e($rows['text_color'] ?? '#152033') ?>"></label>
            <label>البطاقات<input data-preview-var="--g-card" type="color" name="card_color" value="<?= e($rows['card_color'] ?? '#ffffff') ?>"></label>
            <label>الحدود<input data-preview-var="--g-border" type="color" name="border_color" value="<?= e($rows['border_color'] ?? '#dce5f1') ?>"></label>
            <label>النص الثانوي<input data-preview-var="--g-muted" type="color" name="muted_color" value="<?= e($rows['muted_color'] ?? '#61708a') ?>"></label>
            <label>عرض القالب<input data-preview-var="--g-shell-width" type="text" name="shell_width" value="<?= e($rows['shell_width'] ?? '1120px') ?>"></label>
            <label>استدارة البطاقات<input data-preview-var="--g-radius-xl" type="text" name="radius" value="<?= e($rows['radius'] ?? '20px') ?>"></label>
            <label>نص الشعار<input type="text" name="logo_text" value="<?= e($rows['logo_text'] ?? '') ?>"></label>
            <label>نص عاجل<input type="text" name="breaking_text" value="<?= e($rows['breaking_text'] ?? '') ?>"></label>
            <input type="hidden" name="show_breaking_bar" value="<?= !empty($rows['show_breaking_bar']) ? '1' : '0' ?>">
            <input type="hidden" name="show_reading_progress" value="<?= !empty($rows['show_reading_progress']) ? '1' : '0' ?>">
            <input type="hidden" name="show_sticky_header" value="<?= !empty($rows['show_sticky_header']) ? '1' : '0' ?>">
            <input type="hidden" name="compact_cards" value="<?= !empty($rows['compact_cards']) ? '1' : '0' ?>">
            <input type="hidden" name="dark_mode_default" value="<?= e($rows['dark_mode_default'] ?? 'light') ?>">
            <input type="hidden" name="header_layout" value="<?= e($rows['header_layout'] ?? 'default') ?>">
            <input type="hidden" name="footer_layout" value="<?= e($rows['footer_layout'] ?? 'default') ?>">
            <div class="g-inline-stats"><button class="g-btn" type="submit">حفظ التخصيص</button></div>
        </div>
        <div class="g-card g-customizer-preview">
            <div class="g-preview-shell" data-preview-shell>
                <div class="g-preview-top">
                    <div class="g-preview-brand"><?= e($rows['logo_text'] ?: 'Godyar') ?></div>
                    <div class="g-preview-pill">عاجل</div>
                </div>
                <div class="g-preview-hero">
                    <h3>معاينة واجهة الثيم</h3>
                    <p>هذه البطاقة تعكس الألوان والعرض والاستدارة مباشرة عند التعديل.</p>
                </div>
                <div class="g-preview-grid">
                    <article class="g-preview-card"><strong>بطاقة خبر</strong><p>محتوى مختصر ومعاينة للعناصر.</p></article>
                    <article class="g-preview-card"><strong>بطاقة ثانية</strong><p>للتحقق من المسافات والحدود.</p></article>
                </div>
            </div>
        </div>
    </form>
</section>
