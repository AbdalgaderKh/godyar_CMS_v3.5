<article class="v4-search-card">
  <div class="v4-search-meta">
    <span class="v4-badge"><?= e($item['type'] ?? 'item') ?></span>
    <time><?= e(substr((string)($item['updated_at'] ?? ''), 0, 10)) ?></time>
  </div>
  <h3><a href="<?= e($item['url'] ?? '#') ?>"><?= e($item['title'] ?? '') ?></a></h3>
  <?php if (!empty($item['excerpt'])): ?>
    <p><?= e($item['excerpt']) ?></p>
  <?php endif; ?>
</article>
