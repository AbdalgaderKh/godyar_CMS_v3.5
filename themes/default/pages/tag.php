<section class="v4-page-shell">
    <header class="v4-page-head v4-profile-head">
        <p class="g-v4-meta">أرشيف وسم</p>
        <h1>#<?= e($tag['name'] ?? '') ?></h1>
        <p>عرض الأخبار المرتبطة بهذا الوسم ضمن طبقة v4.</p>
    </header>

    <?php if (!empty($articles)): ?>
    <div class="g-v4-grid">
        <?php foreach ($articles as $item): ?>
            <?php $theme->partial('news-card', ['item' => $item, 'locale' => $locale ?? 'ar']); ?>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="v4-empty-state">لا توجد نتائج لهذا الوسم حاليًا.</div>
    <?php endif; ?>
</section>
