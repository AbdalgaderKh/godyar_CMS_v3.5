<?php if (empty($mediaItems)): return; endif; ?>
<section class="g-v4-card v4-media-card">
    <h2>الوسائط</h2>
    <div class="v4-media-grid">
        <?php foreach ($mediaItems as $media): ?>
            <figure class="v4-media-item">
                <picture>
                    <?php if (!empty($media['webp_url'])): ?><source srcset="<?= e($media['webp_url']) ?>" type="image/webp"><?php endif; ?>
                    <img src="<?= e($media['url'] ?? '') ?>" alt="<?= e($media['alt_text'] ?? '') ?>" loading="lazy">
                </picture>
                <?php if (!empty($media['alt_text'])): ?><figcaption><?= e($media['alt_text']) ?></figcaption><?php endif; ?>
            </figure>
        <?php endforeach; ?>
    </div>
</section>
