<section class="v4-page-shell">
  <div class="v4-page-head">
    <h1>Health Check</h1>
    <p>فحص جاهزية طبقة v4 قبل التفعيل الكامل.</p>
  </div>

  <div class="v4-kpi-strip">
    <div class="v4-kpi-card"><strong><?= (int)($summary['ok'] ?? 0) ?></strong><span>سليم</span></div>
    <div class="v4-kpi-card"><strong><?= (int)($summary['warn'] ?? 0) ?></strong><span>تحذيرات</span></div>
    <div class="v4-kpi-card"><strong><?= (int)($summary['fail'] ?? 0) ?></strong><span>أعطال</span></div>
  </div>

  <div class="v4-table-wrap">
    <table class="v4-table">
      <thead>
        <tr><th>العنصر</th><th>الحالة</th><th>الرسالة</th><th>المسار</th></tr>
      </thead>
      <tbody>
      <?php foreach (($summary['checks'] ?? []) as $check): ?>
        <tr>
          <td><?= e($check['name'] ?? '') ?></td>
          <td><span class="v4-badge v4-badge--<?= e($check['status'] ?? 'warn') ?>"><?= e($check['status'] ?? '') ?></span></td>
          <td><?= e($check['message'] ?? '') ?></td>
          <td><code><?= e($check['path'] ?? '') ?></code></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
