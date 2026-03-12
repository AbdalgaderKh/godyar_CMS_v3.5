<section class="v4-page-shell">
  <div class="v4-page-head">
    <h1>Contact Inbox</h1>
    <p>مراجعة آخر رسائل التواصل المحفوظة من طبقة v4.</p>
  </div>

  <div class="v4-kpi-strip">
    <div class="v4-kpi-card"><strong><?= count($items ?? []) ?></strong><span>رسالة معروضة</span></div>
  </div>

  <?php if (!empty($items)): ?>
  <div class="v4-inbox-list">
    <?php foreach ($items as $item): ?>
      <article class="v4-inbox-card">
        <header>
          <h2><?= e($item['subject'] ?? 'رسالة بدون عنوان') ?></h2>
          <p><?= e($item['name'] ?? '') ?> — <?= e($item['email'] ?? '') ?></p>
        </header>
        <div class="v4-inbox-meta">
          <span><?= e($item['locale'] ?? 'ar') ?></span>
          <span><?= e($item['created_at'] ?? '') ?></span>
          <span><?= e($item['ip_address'] ?? '') ?></span>
        </div>
        <p><?= nl2br(e($item['message'] ?? '')) ?></p>
      </article>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="v4-empty-state">
    <h2>لا توجد رسائل بعد</h2>
    <p>سيتم عرض الرسائل هنا بعد استخدام نموذج التواصل.</p>
  </div>
  <?php endif; ?>
</section>
