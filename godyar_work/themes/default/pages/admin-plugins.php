<section class="g-card">
    <div class="g-admin-head"><h1>إدارة الإضافات</h1><p>تفعيل وتعطيل الإضافات الجاهزة ضمن طبقة v4.</p></div>
    <?php if (!empty($flash)): ?><div class="g-alert g-alert-<?= e($flash['type'] ?? 'info') ?>"><?= e($flash['message'] ?? '') ?></div><?php endif; ?>
    <div class="g-table-wrap"><table class="g-table"><thead><tr><th>الإضافة</th><th>الإصدار</th><th>الوصف</th><th>الحالة</th><th></th></tr></thead><tbody>
    <?php foreach (($rows ?? []) as $row): ?>
        <tr>
            <td><strong><?= e($row['name'] ?? '') ?></strong><div class="g-muted"><?= e($row['key'] ?? '') ?></div></td>
            <td><?= e($row['version'] ?? '') ?></td>
            <td><?= e($row['description'] ?? '') ?></td>
            <td><span class="g-badge <?= !empty($row['enabled']) ? 'is-success' : 'is-muted' ?>"><?= !empty($row['enabled']) ? 'مفعلة' : 'متوقفة' ?></span></td>
            <td>
                <form method="post" action="<?= e(godyar_v4_base_url('/v4/admin/plugins/toggle')) ?>">
                    <?= godyar_v4_csrf_field() ?>
                    <input type="hidden" name="key" value="<?= e($row['key'] ?? '') ?>">
                    <input type="hidden" name="enabled" value="<?= !empty($row['enabled']) ? '0' : '1' ?>">
                    <button class="g-btn" type="submit"><?= !empty($row['enabled']) ? 'إيقاف' : 'تفعيل' ?></button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody></table></div>
</section>
