<section class="g-card">
    <div class="g-admin-head"><h1>مركز الكاش</h1><p>إدارة الكاش المولد لصفحات الواجهة في v4.</p></div>
    <?php if (!empty($flash)): ?><div class="g-alert g-alert-<?= e($flash['type'] ?? 'info') ?>"><?= e($flash['message'] ?? '') ?></div><?php endif; ?>
    <form method="post" action="<?= e(godyar_v4_base_url('/v4/admin/cache-center/clear')) ?>" class="g-inline-form"><?= godyar_v4_csrf_field() ?><button class="g-btn g-btn-danger" type="submit">مسح كل الكاش</button></form>
    <div class="g-table-wrap"><table class="g-table"><thead><tr><th>الملف</th><th>الحجم</th><th>آخر تحديث</th></tr></thead><tbody>
    <?php foreach (($rows ?? []) as $row): ?>
        <tr><td><?= e($row['file'] ?? '') ?></td><td><?= number_format((int)($row['size'] ?? 0)) ?> B</td><td><?= e($row['updated_at'] ?? '') ?></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
</section>
