<section class="v4-page-shell">
  <div class="v4-page-head">
    <h1>Media Usage Map</h1>
    <p>خريطة سريعة تربط ملفات الوسائط بالأخبار والصفحات لاكتشاف العناصر المستخدمة وغير المستخدمة قبل التنظيف.</p>
  </div>

  <div class="v4-kpi-strip">
    <div class="v4-kpi-card"><strong><?= (int)($usage['total_count'] ?? 0) ?></strong><span>إجمالي الملفات</span></div>
    <div class="v4-kpi-card"><strong><?= (int)($usage['used_count'] ?? 0) ?></strong><span>مستخدمة</span></div>
    <div class="v4-kpi-card"><strong><?= (int)($usage['unused_count'] ?? 0) ?></strong><span>غير مستخدمة</span></div>
  </div>

  <div class="v4-media-usage-grid">
    <?php foreach (($usage['items'] ?? []) as $item): ?>
      <article class="v4-usage-card <?= !empty($item['is_used']) ? 'is-used' : 'is-unused' ?>">
        <div class="v4-usage-card__media">
          <img src="<?= e($item['url'] ?? '') ?>" alt="<?= e($item['alt_text'] ?? '') ?>">
        </div>
        <div class="v4-usage-card__body">
          <strong><?= e($item['alt_text'] ?: basename((string)($item['path'] ?? ''))) ?></strong>
          <code><?= e($item['path'] ?? '') ?></code>
          <p><?= !empty($item['is_used']) ? 'الملف مستخدم داخل المحتوى.' : 'الملف يبدو غير مستخدم حاليًا.' ?></p>
          <?php if (!empty($item['references'])): ?>
            <ul class="v4-usage-list">
              <?php foreach ($item['references'] as $ref): ?>
                <li>
                  <span class="v4-badge"><?= e($ref['type'] ?? '') ?></span>
                  <?php if (!empty($ref['url'])): ?>
                    <a href="<?= e($ref['url']) ?>" target="_blank" rel="noopener"><?= e($ref['title'] ?? '') ?></a>
                  <?php else: ?>
                    <span><?= e($ref['title'] ?? '') ?></span>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
