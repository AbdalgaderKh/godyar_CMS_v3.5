<?php

if (!function_exists('h')) {
    
    function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

$__basePath = '';
if (function_exists('base_url')) {
    $u = (string) base_url();
    $p = parse_url($u, PHP_URL_PATH);
    $__basePath = is_string($p) ? rtrim($p, '/') : '';
}
if ($__basePath === '' && isset($_SERVER['SCRIPT_NAME'])) {
    
    $sn = (string)$_SERVER['SCRIPT_NAME'];
    $sn = preg_replace('~^(.*/)(admin/.*)$~i', '$1', $sn);
    $__basePath = rtrim((string)$sn, '/');
}

if (!defined('SITE_BASE')) {
    define('SITE_BASE', ($__basePath !== '' ? $__basePath : ''));
}
if (!defined('ADMIN_BASE')) {
    define('ADMIN_BASE', (SITE_BASE !== '' ? SITE_BASE : '') . '/admin');
}

if (!function_exists('gdy_admin_render')) {
    
    function gdy_admin_render(string $title, string $active, callable $contentFn): void {
        $nonce = isset($_SESSION['csp_nonce']) ? (string)$_SESSION['csp_nonce'] : '';
        $v = defined('GDY_ASSETS_VER') ? GDY_ASSETS_VER : (string)time();

        $adminBase = ADMIN_BASE;
        $siteBase  = SITE_BASE;

        $menu = [
            'dashboard' => ['label' => __('t_admin_dash', 'لوحة التحكم'), 'href' => $adminBase . '/', 'icon' => 'home'],
            'news'      => ['label' => __('t_admin_news', 'الأخبار'),       'href' => $adminBase . '/news/', 'icon' => 'news'],
            'categories'=> ['label' => __('t_admin_cats', 'الفئات'),        'href' => $adminBase . '/categories/', 'icon' => 'tag'],
            'users'     => ['label' => __('t_admin_users','المستخدمون'),    'href' => $adminBase . '/users/', 'icon' => 'user'],
            'settings'  => ['label' => __('t_admin_settings','الإعدادات'),  'href' => $adminBase . '/settings/', 'icon' => 'settings'],
        ];

        ?><!doctype html>
        <html lang="ar" dir="rtl" data-theme="light">
        <head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?= h($title) ?></title>

            <link rel="stylesheet" href="<?= h($adminBase) ?>/assets/css/admin-shell.css?v=<?= h($v) ?>">
            <link rel="stylesheet" href="<?= h($adminBase) ?>/assets/css/admin-ui.css?v=<?= h($v) ?>">
            <link rel="stylesheet" href="<?= h($basePrefix) ?>/assets/admin/editor/gdy-editor.css?v=<?= h($v) ?>">

            <?php if ($nonce !== ''): ?>
                <!-- CSP is sent via HTTP headers (.htaccess). Avoid meta CSP here to prevent conflicts. -->
            <?php endif; ?>
        </head>
        <body class="gdy-admin">

        <div class="admin-shell" data-admin-shell>
            <aside class="admin-sidebar" data-admin-sidebar>
                <div class="admin-brand">
                    <a class="admin-brand__link" href="<?= h($siteBase ?: '/') ?>">
                        <span class="admin-brand__logo" aria-hidden="true">G</span>
                        <span class="admin-brand__txt">Godyar Admin</span>
                    </a>
                    <button type="button" class="admin-sidebar__toggle" data-admin-toggle aria-label="القائمة">
                        <svg class="gdy-icon" aria-hidden="true"><use href="<?= h($siteBase) ?>/assets/icons/godyar-icons.svg#menu"></use></svg>
                    </button>
                </div>

                <nav class="admin-nav" aria-label="القائمة">
                    <?php foreach ($menu as $key => $it):
                        $isActive = ($key === $active);
                    ?>
                        <a class="admin-nav__link <?= $isActive ? 'is-active' : '' ?>" href="<?= h($it['href']) ?>">
                            <svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="<?= h($siteBase) ?>/assets/icons/godyar-icons.svg#<?= h($it['icon']) ?>"></use></svg>
                            <span><?= h($it['label']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <div class="admin-sidebar__foot">
                    <a class="admin-nav__link" href="<?= h($adminBase) ?>/logout.php">
                        <svg class="gdy-icon" aria-hidden="true"><use href="<?= h($siteBase) ?>/assets/icons/godyar-icons.svg#logout"></use></svg>
                        <span><?= h(__('t_logout','تسجيل الخروج')) ?></span>
                    </a>
                </div>
            </aside>

            <main class="admin-main">
                <header class="admin-topbar">
                    <div class="admin-topbar__left">
                        <button type="button" class="admin-topbar__btn" data-admin-toggle aria-label="فتح القائمة">
                            <svg class="gdy-icon" aria-hidden="true"><use href="<?= h($siteBase) ?>/assets/icons/godyar-icons.svg#menu"></use></svg>
                        </button>
                        <div class="admin-topbar__title"><?= h($title) ?></div>
                    </div>
                    <div class="admin-topbar__right">
                        <a class="admin-topbar__btn" href="<?= h($siteBase ?: '/') ?>" title="الموقع">
                            <svg class="gdy-icon" aria-hidden="true"><use href="<?= h($siteBase) ?>/assets/icons/godyar-icons.svg#home"></use></svg>
                        </a>
                    </div>
                </header>

                <div class="admin-content">
                    <?php $contentFn(); ?>
                </div>
            </main>
        </div>

        <script<?php if ($nonce !== '') echo ' nonce="'.h($nonce).'"'; ?> src="<?= h($basePrefix) ?>/assets/admin/js/admin-sidebar.js?v=<?= h($v) ?>" defer></script>
        <script<?php if ($nonce !== '') echo ' nonce="'.h($nonce).'"'; ?> src="<?= h($basePrefix) ?>/assets/admin/js/admin-csp.js?v=<?= h($v) ?>" defer></script>
        </body>
        </html>
        <?php
    }
}
