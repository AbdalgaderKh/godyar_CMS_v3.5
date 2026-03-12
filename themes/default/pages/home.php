<section class="g-v4-wrap g-v4-hero">
    <div>
        <span class="g-v4-kicker">Godyar CMS v4.1</span>
        <h1>نسخة مستقرة بواجهة أسرع وثيمات أوضح</h1>
        <p>تم تنظيم الثيمات وتحسين قابلية القراءة وإضافة الوضع الليلي وشريط الأخبار العاجلة وتحسين بطاقات الأخبار.</p>
    </div>
</section>
<?php if (!empty($news)): ?>
<section class="g-v4-wrap g-v4-trending-strip" aria-label="الأكثر تداولاً">
    <strong>الأكثر تداولاً</strong>
    <div class="g-v4-trending-track">
        <?php foreach (array_slice($news, 0, 6) as $trend): ?>
            <a href="<?= e(godyar_v4_url($locale ?? 'ar', 'news/' . ($trend['slug'] ?? ''))) ?>"><?= e($trend['title'] ?? '') ?></a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
<section class="g-v4-wrap g-v4-section-head">
    <h2>أحدث الأخبار</h2>
    <p>عرض موحد ببطاقات أخف وتحميل صور lazy loading.</p>
</section>
<section class="g-v4-wrap g-v4-grid g-v4-news-grid">
    <?php foreach (($news ?? []) as $item): ?>
        <?php $theme->partial('news-card', ['item' => $item, 'locale' => $locale ?? 'ar']); ?>
    <?php endforeach; ?>
</section>
