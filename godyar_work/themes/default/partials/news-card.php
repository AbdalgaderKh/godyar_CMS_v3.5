<article class="g-v4-news-card is-skeleton">
    <?php if (!empty($item['image'])): ?>
        <a class="g-v4-news-thumb" href="<?= e(godyar_v4_url($locale ?? 'ar', 'news/' . ($item['slug'] ?? ''))) ?>">
            <img data-src="<?= e($item['image']) ?>" alt="<?= e($item['title'] ?? '') ?>" loading="lazy">
        </a>
    <?php endif; ?>
    <div class="g-v4-news-body">
        <?php if (!empty($item['category_name'])): ?><span class="g-v4-chip"><?= e((string)$item['category_name']) ?></span><?php endif; ?>
        <h3><a href="<?= e(godyar_v4_url($locale ?? 'ar', 'news/' . ($item['slug'] ?? ''))) ?>"><?= e($item['title'] ?? '') ?></a></h3>
        <?php if (!empty($item['published_at'])): ?><p class="g-v4-meta"><?= e((string)$item['published_at']) ?></p><?php endif; ?>
        <p><?= e($item['excerpt'] ?? '') ?></p>
    </div>
</article>
