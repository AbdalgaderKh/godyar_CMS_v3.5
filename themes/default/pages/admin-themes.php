<section class="g-card">
    <div class="g-admin-head"><h1>مركز الثيمات</h1><p>تفعيل الثيمات الجاهزة مباشرة من لوحة v4.</p></div>
    <?php if (!empty($flash)): ?><div class="g-alert g-alert-<?= e($flash['type'] ?? 'info') ?>"><?= e($flash['message'] ?? '') ?></div><?php endif; ?>
    <div class="g-grid g-grid-3">
        <?php foreach (($rows ?? []) as $row): ?>
            <article class="g-theme-card">
                <h3><?= e($row['name'] ?? '') ?></h3>
                <p class="g-muted"><?= e($row['description'] ?? '') ?></p>
                <div class="g-meta-list"><span>المفتاح: <?= e($row['key'] ?? '') ?></span><span>الإصدار: <?= e($row['version'] ?? '') ?></span><span>الكاتب: <?= e($row['author'] ?? '') ?></span><span>CSS: <?= e($row['site_theme'] ?? '') ?></span></div>
                <div class="g-theme-actions">
                    <div class="g-badge <?= !empty($row['active']) ? 'is-success' : 'is-muted' ?>"><?= !empty($row['active']) ? 'الثيم الحالي' : 'متاح' ?></div>
                    <?php if (empty($row['active'])): ?>
                    <form method="post" action="<?= e(godyar_v4_base_url('/v4/admin/themes/activate')) ?>">
                        <?= godyar_v4_csrf_field() ?>
                        <input type="hidden" name="key" value="<?= e($row['key'] ?? '') ?>">
                        <button class="g-btn" type="submit">تفعيل</button>
                    </form>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
