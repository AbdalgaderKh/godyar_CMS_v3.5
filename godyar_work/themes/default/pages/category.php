<section class="g-v4-wrap g-v4-card g-v4-category-hero">
    <span class="g-v4-kicker">تصنيف</span>
    <h1><?= e($category['name'] ?? '') ?></h1>
    <p><?= e($category['description'] ?? '') ?></p>
</section>
<section class="g-v4-wrap g-v4-grid">
    <?php foreach (($items ?? []) as $item): ?>
        <?php $theme->partial('news-card', ['item' => $item, 'locale' => $locale ?? 'ar']); ?>
    <?php endforeach; ?>
</section>
