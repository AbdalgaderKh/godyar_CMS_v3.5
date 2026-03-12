<?php $headerMenu = $menu->header($locale ?? 'ar'); $ui = godyar_v4_theme_settings(); $currentLocale = $locale ?? 'ar'; ?>
<header class="g-v4-header <?= !empty($ui['show_sticky_header']) ? 'is-sticky' : '' ?> <?= e('layout-' . ($ui['header_layout'] ?? 'default')) ?>">
    <?php if (!empty($ui['show_breaking_bar'])): ?>
    <div class="g-v4-breaking">
        <div class="g-v4-wrap g-v4-breaking-row">
            <strong>عاجل</strong>
            <span><?= e($ui['breaking_text'] ?? 'واجهة الثيمات المستقرة مفعلة الآن.') ?></span>
        </div>
    </div>
    <?php endif; ?>
    <div class="g-v4-wrap g-v4-header-row">
        <a class="g-v4-brand" href="<?= e(godyar_v4_url($currentLocale)) ?>"><?= e(($ui['logo_text'] ?? '') !== '' ? $ui['logo_text'] : $settings->get('site_name', 'Godyar')) ?></a>
        <nav class="g-v4-nav" data-mobile-nav>
            <?php foreach ($headerMenu as $item): ?>
                <a href="<?= e($item['url']) ?>"><?= e($item['label']) ?></a>
            <?php endforeach; ?>
            <a class="g-v4-nav-search-link" href="<?= e(godyar_v4_url($currentLocale, 'search')) ?>">بحث</a>
        </nav>
        <div class="g-v4-header-actions">
            <button class="g-v4-search-toggle" type="button" data-search-open aria-label="فتح البحث">⌕</button>
            <button class="g-v4-theme-toggle" type="button" data-theme-toggle aria-label="تبديل المظهر">🌙</button>
            <button class="g-v4-menu-toggle" type="button" data-mobile-toggle aria-label="القائمة">☰</button>
        </div>
    </div>
</header>
<div class="g-v4-search-overlay" data-search-overlay hidden>
    <div class="g-v4-search-dialog g-v4-wrap">
        <div class="g-v4-search-head">
            <strong>بحث سريع</strong>
            <button type="button" class="g-v4-search-close" data-search-close aria-label="إغلاق">×</button>
        </div>
        <form method="get" action="<?= e(godyar_v4_url($currentLocale, 'search')) ?>" class="g-v4-search-form">
            <input type="search" name="q" placeholder="ابحث في الأخبار والصفحات" autocomplete="off">
            <button type="submit">بحث</button>
        </form>
        <div class="g-v4-search-suggestions">
            <a href="<?= e(godyar_v4_url($currentLocale, 'search')) ?>?q=الاقتصاد">الاقتصاد</a>
            <a href="<?= e(godyar_v4_url($currentLocale, 'search')) ?>?q=التقنية">التقنية</a>
            <a href="<?= e(godyar_v4_url($currentLocale, 'search')) ?>?q=السعودية">السعودية</a>
        </div>
    </div>
</div>
