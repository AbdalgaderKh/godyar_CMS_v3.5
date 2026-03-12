<section class="g-card">
    <div class="g-admin-head"><h1>محسن الوسائط</h1><p>إنشاء نسخ WebP و AVIF عند توفر مكتبة GD.</p></div>
    <?php if (!empty($flash)): ?><div class="g-alert g-alert-<?= e($flash['type'] ?? 'info') ?>"><?= e($flash['message'] ?? '') ?></div><?php endif; ?>
    <div class="g-inline-stats">
        <span class="g-badge <?= !empty($caps['gd']) ? 'is-success' : 'is-danger' ?>">GD <?= !empty($caps['gd']) ? 'متوفرة' : 'غير متوفرة' ?></span>
        <span class="g-badge <?= !empty($caps['webp']) ? 'is-success' : 'is-muted' ?>">WebP</span>
        <span class="g-badge <?= !empty($caps['avif']) ? 'is-success' : 'is-muted' ?>">AVIF</span>
    </div>
    <div class="g-table-wrap"><table class="g-table"><thead><tr><th>الملف</th><th>الحجم</th><th>WebP</th><th>AVIF</th><th></th></tr></thead><tbody>
    <?php foreach (($rows ?? []) as $row): ?>
        <tr>
            <td><?= e($row['relative'] ?? '') ?></td>
            <td><?= number_format((int)($row['size'] ?? 0)) ?> B</td>
            <td><?= !empty($row['webp_exists']) ? 'نعم' : 'لا' ?></td>
            <td><?= !empty($row['avif_exists']) ? 'نعم' : 'لا' ?></td>
            <td>
                <form method="post" action="<?= e(godyar_v4_base_url('/v4/admin/media-optimizer/run')) ?>">
                    <?= godyar_v4_csrf_field() ?>
                    <input type="hidden" name="relative" value="<?= e($row['relative'] ?? '') ?>">
                    <button class="g-btn" type="submit">تحسين</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody></table></div>
</section>
