<?php
$query = (string)($query ?? '');
$results = $results ?? [];
$highlight = static function (string $text) use ($query): string {
    $safe = e($text);
    if ($query === '') {
        return $safe;
    }
    $pattern = '/' . preg_quote($query, '/') . '/iu';
    return (string)preg_replace($pattern, '<mark>$0</mark>', $safe);
};
?>
<section class="g-v4-wrap g-v4-section-head">
    <div>
        <h1>البحث</h1>
        <p>ابحث في الأخبار والمحتوى المنشور داخل الموقع.</p>
    </div>
</section>
<section class="g-v4-wrap g-v4-card g-v4-search-page">
    <form method="get" action="<?= e(godyar_v4_url($locale ?? 'ar', 'search')) ?>" class="g-v4-search-form g-v4-search-form--page" data-search-page-form>
        <input type="search" name="q" value="<?= e($query) ?>" placeholder="اكتب عبارة البحث" autocomplete="off">
        <button type="submit">بحث</button>
    </form>
    <div class="g-v4-search-page-meta">
        <div>
            <strong><?= $query !== '' ? ('نتائج البحث عن: ' . e($query)) : 'ابدأ بالبحث' ?></strong>
            <p><?= $query !== '' ? ('عدد النتائج: ' . count($results) . ' نتيجة') : 'سيتم عرض النتائج هنا بعد إدخال عبارة البحث.' ?></p>
        </div>
        <div class="g-v4-search-recent" data-search-recent hidden>
            <span>عمليات البحث الأخيرة</span>
            <div class="g-v4-search-recent-list" data-search-recent-list></div>
        </div>
    </div>
</section>
<?php if ($query !== '' && !empty($results)): ?>
<section class="g-v4-wrap g-v4-search-results">
    <?php foreach ($results as $item): ?>
        <article class="g-v4-card g-v4-search-result-card">
            <a class="g-v4-search-result-title" href="<?= e(godyar_v4_url($locale ?? 'ar', 'news/' . ($item['slug'] ?? ''))) ?>"><?= $highlight((string)($item['title'] ?? '')) ?></a>
            <p><?= $highlight((string)($item['excerpt'] ?? '')) ?></p>
            <div class="g-v4-search-result-meta">
                <span><?= e((string)($item['category_name'] ?? 'عام')) ?></span>
                <span><?= e((string)($item['published_at'] ?? '')) ?></span>
            </div>
        </article>
    <?php endforeach; ?>
</section>
<?php elseif ($query !== ''): ?>
<section class="g-v4-wrap g-v4-card g-v4-empty-state">
    <h3>لا توجد نتائج مطابقة</h3>
    <p>جرّب كلمات أقل أو ابحث باسم خبر أو موضوع أوسع.</p>
    <div class="g-v4-search-suggestions g-v4-search-suggestions--page">
        <a href="<?= e(godyar_v4_url($locale ?? 'ar', 'search')) ?>?q=الاقتصاد">الاقتصاد</a>
        <a href="<?= e(godyar_v4_url($locale ?? 'ar', 'search')) ?>?q=السعودية">السعودية</a>
        <a href="<?= e(godyar_v4_url($locale ?? 'ar', 'search')) ?>?q=التقنية">التقنية</a>
    </div>
</section>
<?php endif; ?>
