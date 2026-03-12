<section class="v4-page-shell">
  <div class="v4-page-head">
    <h1>Authors Manager</h1>
    <p>إدارة انتقالية آمنة للكتّاب داخل طبقة v4. العناصر الجديدة والحساسة تُخزن في طبقة v4 دون كسر البيانات القديمة.</p>
  </div>

  <?php if (!empty($flash)): ?>
    <div class="v4-flash v4-flash-<?= e($flash['type'] ?? 'info') ?>"><?= e($flash['message'] ?? '') ?></div>
  <?php endif; ?>

  <div class="v4-admin-form-card">
    <h2>إضافة كاتب</h2>
    <form method="post" action="<?= e(godyar_v4_base_url('/v4/admin/authors/create')) ?>" class="v4-admin-form-grid">
      <?= godyar_v4_csrf_field() ?>
      <label><span>الاسم</span><input type="text" name="name" required></label>
      <label><span>Slug</span><input type="text" name="slug" placeholder="auto-from-name"></label>
      <label class="v4-span-2"><span>رابط الصورة / الأفاتار</span><input type="text" name="avatar" placeholder="/uploads/... أو رابط كامل"></label>
      <label class="v4-span-2"><span>النبذة</span><textarea name="bio" rows="3"></textarea></label>
      <div class="v4-span-2"><button type="submit" class="v4-btn-primary">إضافة الكاتب</button></div>
    </form>
  </div>

  <div class="v4-kpi-strip">
    <div class="v4-kpi-card"><strong><?= count($items ?? []) ?></strong><span>عدد الكتّاب</span></div>
    <div class="v4-kpi-card"><strong><?= count(array_filter($items ?? [], fn($i) => !empty($i['avatar'] ?? ''))) ?></strong><span>بصورة شخصية</span></div>
    <div class="v4-kpi-card"><strong><?= count(array_filter($items ?? [], fn($i) => !empty($i['bio'] ?? ''))) ?></strong><span>بوصف</span></div>
  </div>

  <div class="v4-table-wrap">
    <table class="v4-table">
      <thead>
        <tr>
          <th>الكاتب</th>
          <th>Slug</th>
          <th>نبذة</th>
          <th>المصدر</th>
          <th>آخر تحديث</th>
          <th>رابط عام</th>
          <th>إجراءات</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach (($items ?? []) as $item): ?>
        <tr>
          <td><strong><?= e($item['name'] ?? '') ?></strong></td>
          <td><code><?= e($item['slug'] ?? '') ?></code></td>
          <td><?= e(mb_strimwidth((string)($item['bio'] ?? ''), 0, 120, '…', 'UTF-8')) ?></td>
          <td><?= e($item['source'] ?? 'db') ?></td>
          <td><?= e($item['updated_at'] ?? '') ?></td>
          <td><a class="v4-admin-link" href="<?= e(godyar_v4_url('ar', 'author/' . ($item['slug'] ?? ''))) ?>" target="_blank" rel="noopener">فتح الصفحة</a></td>
          <td>
            <details class="v4-inline-editor">
              <summary>تعديل</summary>
              <form method="post" action="<?= e(godyar_v4_base_url('/v4/admin/authors/' . ($item['slug'] ?? '') . '/update')) ?>" class="v4-admin-form-grid compact">
                <?= godyar_v4_csrf_field() ?>
                <label><span>الاسم</span><input type="text" name="name" value="<?= e($item['name'] ?? '') ?>"></label>
                <label><span>الصورة</span><input type="text" name="avatar" value="<?= e($item['avatar'] ?? '') ?>"></label>
                <label class="v4-span-2"><span>النبذة</span><textarea name="bio" rows="2"><?= e($item['bio'] ?? '') ?></textarea></label>
                <div><button type="submit" class="v4-btn-secondary">حفظ</button></div>
              </form>
              <?php if (($item['source'] ?? '') === 'storage'): ?>
              <form method="post" action="<?= e(godyar_v4_base_url('/v4/admin/authors/' . ($item['slug'] ?? '') . '/delete')) ?>" onsubmit="return confirm('حذف هذا الكاتب من طبقة v4؟');">
                <?= godyar_v4_csrf_field() ?>
                <button type="submit" class="v4-btn-danger">حذف</button>
              </form>
              <?php endif; ?>
            </details>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
