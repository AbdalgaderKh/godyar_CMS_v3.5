<section class="v4-page-shell">
    <header class="v4-page-head v4-profile-head">
        <p class="g-v4-meta">صفحة كاتب</p>
        <h1><?= e($author['name'] ?? '') ?></h1>
        <?php if (!empty($author['bio'])): ?><p><?= e($author['bio'] ?? '') ?></p><?php endif; ?>
    </header>

    <?php if (!empty($articles)): ?>
    <div class="g-v4-grid">
        <?php foreach ($articles as $item): ?>
            <?php $theme->partial('news-card', ['item' => $item, 'locale' => $locale ?? 'ar']); ?>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="v4-empty-state">لا توجد مقالات مرتبطة بهذا الكاتب حاليًا.</div>
    <?php endif; ?>
</section>
