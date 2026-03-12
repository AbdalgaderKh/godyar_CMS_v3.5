<section class="v4-page-shell">
  <div class="v4-page-head">
    <h1>Redirect Center</h1>
    <p>مراجعة التحويلات القديمة المفعلة في طبقة v4.</p>
  </div>

  <div class="v4-table-wrap">
    <table class="v4-table">
      <thead>
      <tr><th>المسار القديم</th><th>المسار الجديد</th><th>الكود</th><th>المصدر</th><th>Hits</th></tr>
      </thead>
      <tbody>
      <?php foreach (($items ?? []) as $item): ?>
        <tr>
          <td><code><?= e($item['old_path'] ?? '') ?></code></td>
          <td><code><?= e($item['new_path'] ?? '') ?></code></td>
          <td><?= (int)($item['status_code'] ?? 301) ?></td>
          <td><?= e($item['source'] ?? '') ?></td>
          <td><?= (int)($item['hits'] ?? 0) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
