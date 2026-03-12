<div class="v4-compare-grid">
  <div class="v4-kpi-card">
    <strong><?= e($comparison['left']['id'] ?? '—') ?></strong>
    <span>النسخة الأولى</span>
    <p class="v4-compare-meta"><?= e($comparison['left']['editor_name'] ?? '') ?> — <?= e($comparison['left']['created_at'] ?? '') ?></p>
  </div>
  <div class="v4-kpi-card">
    <strong><?= e($comparison['right']['id'] ?? '—') ?></strong>
    <span>النسخة الثانية</span>
    <p class="v4-compare-meta"><?= e($comparison['right']['editor_name'] ?? '') ?> — <?= e($comparison['right']['created_at'] ?? '') ?></p>
  </div>
</div>

<?php if (!empty($comparison['fields'])): ?>
  <div class="v4-compare-list">
    <?php foreach (($comparison['fields'] ?? []) as $field): ?>
      <article class="v4-compare-card">
        <h3><?= e($field['label'] ?? $field['key'] ?? '') ?></h3>
        <div class="v4-compare-columns">
          <div>
            <h4>الأقدم/الأولى</h4>
            <pre><?= e($field['left'] ?? '') ?></pre>
          </div>
          <div>
            <h4>الأحدث/الثانية</h4>
            <pre><?= e($field['right'] ?? '') ?></pre>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="v4-empty-state">
    <h2>لا توجد فروق قابلة للعرض</h2>
    <p>إما أن النسختين متطابقتان، أو أن السجل الحالي لا يحتوي snapshot نصي كامل للمقارنة.</p>
  </div>
<?php endif; ?>
