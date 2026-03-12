<?php $footerMenu = $menu->footer($locale ?? 'ar'); $ui = godyar_v4_theme_settings(); ?>
<footer class="g-v4-footer <?= e('layout-' . ($ui['footer_layout'] ?? 'default')) ?>">
    <div class="g-v4-wrap g-v4-footer-links">
        <div>
            <strong><?= e($settings->get('site_name', 'Godyar')) ?></strong>
            <div class="g-muted"><?= e(($ui['footer_note'] ?? '') !== '' ? $ui['footer_note'] : 'منصة أخبار عربية بهوية موحدة وقابلة للتوسع.') ?></div>
        </div>
        <nav class="g-v4-nav">
            <?php foreach ($footerMenu as $item): ?>
                <a href="<?= e($item['url']) ?>"><?= e($item['label']) ?></a>
            <?php endforeach; ?>
        </nav>
    </div>
</footer>
