<article class="g-v4-wrap g-v4-card g-v4-prose g-v4-article">
    <?php if (!empty($news['image'])): ?>
        <div class="g-v4-cover is-skeleton"><img data-src="<?= e($news['image']) ?>" alt="<?= e($news['title'] ?? '') ?>" loading="lazy"></div>
    <?php endif; ?>
    <div class="g-v4-article-head">
        <p class="g-v4-meta"><?= e((string)($news['published_at'] ?? '')) ?></p>
        <h1><?= e($news['title'] ?? '') ?></h1>
        <p class="g-v4-summary"><?= e($news['excerpt'] ?? '') ?></p>
    </div>
    <div class="g-v4-share">
        <span>مشاركة:</span>
        <a target="_blank" rel="noopener" href="https://wa.me/?text=<?= urlencode((string)($seo['canonical'] ?? '')) ?>">واتساب</a>
        <a target="_blank" rel="noopener" href="https://t.me/share/url?url=<?= urlencode((string)($seo['canonical'] ?? '')) ?>">تيليجرام</a>
    </div>
    <div class="g-v4-content"><?= $news['content'] ?? '' ?></div>
</article>
<?php if (!empty($related)): ?>
<section class="g-v4-wrap g-v4-section-head">
    <h2>أخبار ذات صلة</h2>
    <p>محتوى مقترح لتحسين التنقل الداخلي.</p>
</section>
<section class="g-v4-wrap g-v4-grid g-v4-news-grid">
    <?php foreach ($related as $item): ?>
        <?php $theme->partial('news-card', ['item' => $item, 'locale' => $locale ?? 'ar']); ?>
    <?php endforeach; ?>
</section>
<?php endif; ?>
