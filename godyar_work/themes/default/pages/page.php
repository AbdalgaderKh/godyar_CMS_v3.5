<article class="g-v4-wrap g-v4-card g-v4-prose g-v4-article">
    <div class="g-v4-page-hero">
        <span class="g-v4-kicker">صفحة</span>
        <h1><?= e($page['title'] ?? '') ?></h1>
    </div>
    <div class="g-v4-content"><?= $page['content'] ?? '' ?></div>
</article>
