<?php declare(strict_types=1); $themeUi = godyar_v4_theme_settings(); ?>
<!doctype html>
<html lang="<?= e($locale ?? 'ar') ?>" dir="<?= (($locale ?? 'ar') === 'ar') ? 'rtl' : 'ltr' ?>">
<head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e($seo['title'] ?? 'Godyar') ?></title>
    <meta name="description" content="<?= e($seo['description'] ?? '') ?>">
    <meta name="robots" content="<?= e($seo['robots'] ?? 'index,follow') ?>">
    <?php if (!empty($seo['canonical'])): ?><link rel="canonical" href="<?= e($seo['canonical']) ?>"><?php endif; ?>
    <meta property="og:title" content="<?= e($seo['title'] ?? '') ?>">
    <meta property="og:description" content="<?= e($seo['description'] ?? '') ?>">
    <meta property="og:type" content="<?= e($seo['og_type'] ?? 'website') ?>">
    <?php if (!empty($seo['canonical'])): ?><meta property="og:url" content="<?= e($seo['canonical']) ?>"><?php endif; ?>
    <?php if (!empty($seo['og_image'])): ?><meta property="og:image" content="<?= e($seo['og_image']) ?>"><?php endif; ?>
    <meta name="twitter:card" content="<?= e($seo['twitter_card'] ?? 'summary_large_image') ?>">
    <link rel="stylesheet" href="<?= e(godyar_v4_assets_url('css/core.css')) ?>">
    <link rel="stylesheet" href="<?= e(godyar_v4_assets_url('css/utilities.css')) ?>">
    <link rel="stylesheet" href="<?= e(godyar_v4_assets_url('css/components.css')) ?>">
    <link rel="stylesheet" href="<?= e(godyar_v4_assets_url('css/dark.css')) ?>">
    <?php $themeCss = godyar_v4_active_theme_css(); ?>
    <link rel="stylesheet" href="<?= e(godyar_v4_assets_url('css/themes/' . $themeCss)) ?>">
    <?php $inlineVars = godyar_v4_theme_inline_vars(); if ($inlineVars !== ''): ?><style><?= $inlineVars ?></style><?php endif; ?>
    <?= godyar_v4_hook_render('theme.head', ['seo' => $seo ?? []]) ?>
    <?php if (!empty($seo['json_ld'])): ?>
    <script type="application/ld+json"><?= json_encode($seo['json_ld'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
    <?php endif; ?>
</head>
<body data-theme="<?= e((string)($themeUi['dark_mode_default'] ?? 'light')) ?>" class="<?= !empty($themeUi['compact_cards']) ? 'g-compact-cards' : '' ?> g-v4-ux-ready">
<?php if (!empty($themeUi['show_reading_progress'])): ?><div class="g-v4-progress" aria-hidden="true"></div><?php endif; ?>
<?php require __DIR__ . '/header.php'; ?>
<main class="g-v4-shell">
    <?php if (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/v4/admin')): ?>
    <nav class="g-v4-shortcuts"><a href="<?= e(godyar_v4_base_url('/v4/admin/redirects')) ?>">Redirects</a><a href="<?= e(godyar_v4_base_url('/v4/admin/seo-audit')) ?>">SEO Audit</a><a href="<?= e(godyar_v4_base_url('/v4/admin/plugins')) ?>">Plugins</a><a href="<?= e(godyar_v4_base_url('/v4/admin/themes')) ?>">Themes</a><a href="<?= e(godyar_v4_base_url('/v4/admin/theme-settings')) ?>">Theme Settings</a><a href="<?= e(godyar_v4_base_url('/v4/admin/theme-customizer')) ?>">Customizer</a><a href="<?= e(godyar_v4_base_url('/v4/admin/hooks')) ?>">Hooks</a><a href="<?= e(godyar_v4_base_url('/v4/admin/media-optimizer')) ?>">Media Optimizer</a><a href="<?= e(godyar_v4_base_url('/v4/admin/cache-center')) ?>">Cache</a></nav>
    <?php endif; ?>
    <?php require $content_view; ?>
</main>
<?php require __DIR__ . '/footer.php'; ?>
<script src="<?= e(godyar_v4_assets_url('js/app.js')) ?>" defer></script>
<?= godyar_v4_hook_render('theme.footer') ?>
</body>
</html>
