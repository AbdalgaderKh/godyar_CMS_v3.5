<section class="v4-page-shell">
  <div class="v4-page-head">
    <h1>Media Manager</h1>
    <p>رفع ملفات الوسائط داخل طبقة v4 مع مشتقات أولية: WebP ونسخة مصغرة متى ما كانت بيئة الخادم تدعم GD.</p>
  </div>

  <?php if (!empty($flash)): ?>
    <div class="v4-flash v4-flash-<?= e($flash['type'] ?? 'info') ?>"><?= e($flash['message'] ?? '') ?></div>
  <?php endif; ?>

  <div class="v4-admin-form-card">
    <h2>رفع وسيط جديد</h2>
    <form method="post" enctype="multipart/form-data" action="<?= e(godyar_v4_base_url('/v4/admin/media/upload')) ?>" class="v4-admin-form-grid">
      <?= godyar_v4_csrf_field() ?>
      <label><span>الملف</span><input type="file" name="media_file" accept="image/*,.svg" required></label>
      <label><span>Alt Text</span><input type="text" name="alt_text"></label>
      <div class="v4-span-2"><button type="submit" class="v4-btn-primary">رفع الملف</button></div>
    </form>
  </div>

  <div class="v4-kpi-strip">
    <div class="v4-kpi-card"><strong><?= count($items ?? []) ?></strong><span>إجمالي العناصر</span></div>
    <div class="v4-kpi-card"><strong><?= count(array_filter($items ?? [], fn($i) => !empty($i['webp_url'] ?? ''))) ?></strong><span>لها WebP</span></div>
    <div class="v4-kpi-card"><strong><?= array_sum(array_map(fn($i) => (int)($i['size_kb'] ?? 0), $items ?? [])) ?></strong><span>الحجم بالكيلوبايت</span></div>
  </div>

  <div class="v4-media-admin-grid">
    <?php foreach (($items ?? []) as $item): ?>
      <figure class="v4-media-admin-card">
        <a href="<?= e($item['url'] ?? '') ?>" target="_blank" rel="noopener">
          <img src="<?= e($item['url'] ?? '') ?>" alt="<?= e($item['alt_text'] ?? '') ?>">
        </a>
        <figcaption>
          <strong><?= e($item['alt_text'] ?: basename((string)($item['path'] ?? ''))) ?></strong>
          <span><code><?= e($item['path'] ?? '') ?></code></span>
          <span><?= e($item['mime_type'] ?? '') ?><?= !empty($item['size_kb']) ? ' · ' . (int)$item['size_kb'] . ' KB' : '' ?></span>
          <span><?= !empty($item['webp_url']) ? 'WebP متاح' : 'بدون WebP' ?></span>
          <?php if (!empty($item['webp_url'])): ?><a class="v4-admin-link" href="<?= e($item['webp_url']) ?>" target="_blank">فتح WebP</a><?php endif; ?>
        </figcaption>
      </figure>
    <?php endforeach; ?>
  </div>
</section>
