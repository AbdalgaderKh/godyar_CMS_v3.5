<section class="v4-page-shell">
  <div class="v4-page-head">
    <h1>Tags Manager</h1>
    <p>إدارة انتقالية للوسوم مع إنشاء وتعديل وحذف العناصر المخزنة في طبقة v4.</p>
  </div>

  <?php if (!empty($flash)): ?>
    <div class="v4-flash v4-flash-<?= e($flash['type'] ?? 'info') ?>"><?= e($flash['message'] ?? '') ?></div>
  <?php endif; ?>

  <div class="v4-admin-form-card">
    <h2>إضافة وسم</h2>
    <form method="post" action="<?= e(godyar_v4_base_url('/v4/admin/tags/create')) ?>" class="v4-admin-form-grid">
      <?= godyar_v4_csrf_field() ?>
      <label><span>الاسم</span><input type="text" name="name" required></label>
      <label><span>Slug</span><input type="text" name="slug" placeholder="auto-from-name"></label>
      <div class="v4-span-2"><button type="submit" class="v4-btn-primary">إضافة الوسم</button></div>
    </form>
  </div>

  <div class="v4-kpi-strip">
    <div class="v4-kpi-card"><strong><?= count($items ?? []) ?></strong><span>إجمالي الوسوم</span></div>
    <div class="v4-kpi-card"><strong><?= count(array_unique(array_map(fn($i) => (string)($i['slug'] ?? ''), $items ?? []))) ?></strong><span>Slug فريد</span></div>
    <div class="v4-kpi-card"><strong><?= count(array_filter($items ?? [], fn($i) => ($i['source'] ?? '') === 'storage')) ?></strong><span>من طبقة v4</span></div>
  </div>

  <div class="v4-admin-tag-list">
    <?php foreach (($items ?? []) as $item): ?>
      <div class="v4-admin-tag-card">
        <a class="v4-admin-chip" href="<?= e(godyar_v4_url('ar', 'tag/' . ($item['slug'] ?? ''))) ?>" target="_blank" rel="noopener">
          <strong><?= e($item['name'] ?? '') ?></strong>
          <span><?= e($item['slug'] ?? '') ?></span>
        </a>
        <small>المصدر: <?= e($item['source'] ?? 'db') ?></small>
        <form method="post" action="<?= e(godyar_v4_base_url('/v4/admin/tags/' . ($item['slug'] ?? '') . '/update')) ?>" class="v4-inline-tag-form">
          <?= godyar_v4_csrf_field() ?>
          <input type="text" name="name" value="<?= e($item['name'] ?? '') ?>">
          <button type="submit" class="v4-btn-secondary">حفظ</button>
        </form>
        <?php if (($item['source'] ?? '') === 'storage'): ?>
        <form method="post" action="<?= e(godyar_v4_base_url('/v4/admin/tags/' . ($item['slug'] ?? '') . '/delete')) ?>" onsubmit="return confirm('حذف الوسم؟');">
          <?= godyar_v4_csrf_field() ?>
          <button type="submit" class="v4-btn-danger">حذف</button>
        </form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</section>
