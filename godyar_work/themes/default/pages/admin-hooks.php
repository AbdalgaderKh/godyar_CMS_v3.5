<section class="g-card">
  <div class="g-admin-head"><h1>Hook Inspector</h1><p>مراجعة نقاط الربط النشطة والحالة الحالية للإضافات وإعدادات التجربة.</p></div>
  <div class="g-grid g-grid-2">
    <article class="g-card-sub">
      <h3>الأحداث المسجلة</h3>
      <div class="g-table-wrap"><table class="g-table"><thead><tr><th>الحدث</th><th>عدد المستمعين</th></tr></thead><tbody>
      <?php foreach (($rows ?? []) as $row): ?>
      <tr><td><?= e($row['event'] ?? '') ?></td><td><?= e($row['count'] ?? 0) ?></td></tr>
      <?php endforeach; ?>
      </tbody></table></div>
    </article>
    <article class="g-card-sub">
      <h3>الإضافات</h3>
      <ul class="g-simple-list">
      <?php foreach (($plugins_rows ?? []) as $row): ?>
        <li><strong><?= e($row['name'] ?? '') ?></strong> — <span class="g-muted"><?= !empty($row['enabled']) ? 'مفعلة' : 'متوقفة' ?></span></li>
      <?php endforeach; ?>
      </ul>
    </article>
  </div>
  <article class="g-card-sub">
    <h3>Runtime Settings</h3>
    <div class="g-table-wrap"><table class="g-table"><thead><tr><th>المفتاح</th><th>القيمة</th></tr></thead><tbody>
    <?php foreach (($settings_rows ?? []) as $key => $value): ?>
      <tr><td><?= e((string)$key) ?></td><td><?= e(is_scalar($value) ? (string)$value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
  </article>
</section>
