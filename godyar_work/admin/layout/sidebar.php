<?php

require_once __DIR__ . '/../_admin_guard.php';
require_once __DIR__ . '/../../includes/lang.php';

if (!function_exists('__')) {
    function __($key, $fallback = null)
    {
        return $fallback !== null ? $fallback : (string)$key;
    }
}
if (!function_exists('gdy_lang')) {
    function gdy_lang()
    {
        return 'ar';
    }
}
if (!function_exists('gdy_lang_url')) {
    function gdy_lang_url($lang)
    {
        $lang = is_string($lang) ? trim($lang) : 'ar';
        return '?lang=' . rawurlencode($lang);
    }
}

try {
    $vfile = __DIR__ . '/../../includes/version.php';
    if (is_file($vfile)) {
        require_once $vfile;
    }
} catch (\Throwable $e) {
}

if (!function_exists('h')) {
    function h($v): string
    {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

$siteBase  = function_exists('base_url') ? rtrim(base_url(), '/') : '';
$adminBase = $siteBase . '/admin';

$currentPage = $currentPage ?? 'dashboard';

$quickStats = $quickStats ?? [
    'posts'    => 0,
    'users'    => 0,
    'comments' => 0,
];

$userName = $_SESSION['user']['name'] ?? ($_SESSION['user']['display_name'] ?? ($_SESSION['user']['email'] ?? 'مشرف النظام'));
$userRole = $_SESSION['user']['role'] ?? null;
$userId   = $_SESSION['user']['id'] ?? null;

$pdo = gdy_pdo_safe();

if ((!is_string($userRole) || $userRole === '') && $userId && ($pdo instanceof \PDO)) {
    try {
        $st = $pdo->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
        $st->execute([$userId]);
        $userRole = $st->fetchColumn() ?: null;
    } catch (\Throwable $e) {
    }
}

$userRoleNorm = is_string($userRole) ? strtolower(trim($userRole)) : '';
$isAdmin = (
    $userRoleNorm === 'admin'
    || $userRoleNorm === 'administrator'
    || (is_string($userRoleNorm) && strpos($userRoleNorm, 'admin') !== false)
);

if (!$isAdmin && $userId && ($pdo instanceof \PDO)) {
    try {
        $st = $pdo->prepare("SELECT r.slug FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = ?");
        $st->execute([$userId]);
        $slugs = $st->fetchAll(PDO::FETCH_COLUMN);
        foreach ($slugs as $s) {
            if (is_string($s) && strtolower($s) === 'admin') {
                $isAdmin = true;
                break;
            }
        }
    } catch (\Throwable $e) {
    }
}

$userAvatar = $_SESSION['user']['avatar'] ?? null;

if (!class_exists(\Godyar\Auth::class)) {
    $authFile = __DIR__ . '/../../includes/auth.php';
    if (is_file($authFile)) {
        require_once $authFile;
    }
}

$isWriter = class_exists(\Godyar\Auth::class) && \Godyar\Auth::isWriter();

$__notifUnread = 0;
if ($pdo instanceof \PDO) {
    try {
        $uid = (int)($_SESSION['user']['id'] ?? 0);
        $chk = gdy_db_stmt_table_exists($pdo, 'admin_notifications');
        $has = $chk && $chk->fetchColumn();
        if ($has) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_notifications WHERE is_read=0 AND (user_id IS NULL OR user_id=:uid)");
            $stmt->execute([':uid' => $uid]);
            $__notifUnread = (int)($stmt->fetchColumn() ?? 0);
        }
    } catch (\Throwable $e) {
        $__notifUnread = 0;
    }
}

$can = function (?string $perm) use ($isWriter): bool {
    $perm = $perm ? trim((string)$perm) : '';
    if ($perm === '') {
        return true;
    }

    if (class_exists(\Godyar\Auth::class) && method_exists(\Godyar\Auth::class, 'hasPermission')) {
        try {
            return \Godyar\Auth::hasPermission($perm);
        } catch (\Throwable $e) {
            return false;
        }
    }

    return !$isWriter;
};

$dbMenu = [];
if ($pdo instanceof \PDO) {
    try {
        $chk = gdy_db_stmt_table_exists($pdo, 'admin_menu');
        $has = $chk && $chk->fetchColumn();
        if ($has) {
            $stmt = $pdo->query("SELECT section,label,sub_label,href,icon,perm,sort_order,is_active
                                 FROM admin_menu
                                 WHERE is_active = 1
                                 ORDER BY section ASC, sort_order ASC, id ASC");
            $rows = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
            foreach ($rows as $r) {
                $sec = (string)($r['section'] ?? 'عام');
                $dbMenu[$sec][] = [
                    'label' => (string)($r['label'] ?? ''),
                    'sub'   => (string)($r['sub_label'] ?? ''),
                    'href'  => (string)($r['href'] ?? '#'),
                    'icon'  => (string)($r['icon'] ?? 'circle'),
                    'perm'  => (string)($r['perm'] ?? ''),
                ];
            }
        }
    } catch (\Throwable $e) {
        $dbMenu = [];
    }
}

$dbMenuItemCount = 0;
foreach ($dbMenu as $sec => $items) {
    $dbMenuItemCount += is_array($items) ? count($items) : 0;
}
if ($dbMenuItemCount < 5) {
    $dbMenu = [];
}

$cspNonceSafe = $cspNonce ?? '';

?>

<aside class="admin-sidebar" id="adminSidebar" role="navigation" aria-label="<?php echo h(__('admin_sidebar', 'القائمة الجانبية')); ?>">
    <div class="admin-sidebar__card">

        <header class="admin-sidebar__header">
            <div class="admin-sidebar__brand">
                <div class="admin-sidebar__logo" aria-hidden="true">
                    <svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#news"></use></svg>
                </div>
                <div class="admin-sidebar__brand-text">
                    <div class="admin-sidebar__title">Godyar News</div>
                    <div class="admin-sidebar__subtitle"><?php echo h(__('admin_panel', 'لوحة التحكم')); ?></div>
                </div>
            </div>
            <div class="admin-sidebar__header-actions">
                <button class="admin-sidebar__desktop-toggle" type="button" id="sidebarDesktopToggle" aria-label="<?php echo h(__('toggle_sidebar', 'تبديل القائمة')); ?>" title="<?php echo h(__('toggle_sidebar', 'تبديل القائمة')); ?>">
                    <svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#panel-left"></use></svg>
                </button>
                <button class="admin-sidebar__toggle" type="button" id="sidebarToggle" aria-label="<?php echo h(__('toggle_sidebar', 'تبديل القائمة')); ?>">
                    <svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#menu"></use></svg>
                </button>
            </div>
        </header>

        <div class="admin-sidebar__search-wrapper">
            <div class="admin-sidebar__search">
                <label for="sidebarSearch" class="visually-hidden">Search</label>
                <input id="sidebarSearch" class="admin-sidebar__search-input" type="search" placeholder="<?php echo h(__('search_menus', 'ابحث في القوائم')); ?>" autocomplete="off" />
                <svg class="gdy-icon admin-sidebar__search-icon" aria-hidden="true" focusable="false"><use href="#search"></use></svg>
                <div id="sidebarSearchResults" class="admin-sidebar__search-results" role="listbox" role="region" role="region" role="region" role="region" role="region" aria-label="&#039; . htmlspecialchars($label, ENT_QUOTES, &#039;UTF-8&#039;) . &#039;"></div>
            </div>
        </div>

        <?php if (!$isWriter): ?>
            <div class="admin-sidebar__quick" role="region" role="region" role="region" role="region" role="region" role="region" aria-label="&#039; . htmlspecialchars($label, ENT_QUOTES, &#039;UTF-8&#039;) . &#039;">
                <div class="admin-sidebar__quick-item" title="<?php echo h(__('comments', 'التعليقات')); ?>">
                    <div class="admin-sidebar__quick-icon"><svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#comment"></use></svg></div>
                    <div class="admin-sidebar__quick-value"><?php echo (int)($quickStats['comments'] ?? 0); ?></div>
                </div>
                <div class="admin-sidebar__quick-item" title="<?php echo h(__('users', 'المستخدمون')); ?>">
                    <div class="admin-sidebar__quick-icon"><svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#users"></use></svg></div>
                    <div class="admin-sidebar__quick-value"><?php echo (int)($quickStats['users'] ?? 0); ?></div>
                </div>
                <div class="admin-sidebar__quick-item" title="<?php echo h(__('news', 'الأخبار')); ?>">
                    <div class="admin-sidebar__quick-icon"><svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#news"></use></svg></div>
                    <div class="admin-sidebar__quick-value"><?php echo (int)($quickStats['posts'] ?? 0); ?></div>
                </div>
            </div>
        <?php endif; ?>

        <nav class="admin-sidebar__nav" role="list">

            <?php if (!empty($dbMenu)): ?>
                <?php foreach ($dbMenu as $secLabel => $items): ?>
                    <div class="admin-sidebar__section" role="region" role="region" role="region" role="region" role="region" role="region" aria-label="&#039; . htmlspecialchars($label, ENT_QUOTES, &#039;UTF-8&#039;) . &#039;">
                        <div class="admin-sidebar__section-title"><?php echo h($secLabel); ?></div>
                        <?php foreach ($items as $it): ?>
                            <?php
                            $perm = $it['perm'] ?? '';
                            if (!$can($perm)) {
                                continue;
                            }
                            $href = $it['href'] ?? '#';
                            if ($href !== '' && $href[0] !== '/' && strpos($href, 'http') !== 0) {
                                $href = $adminBase . '/' . ltrim($href, '/');
                            }
                            $icon  = $it['icon'] ?? 'circle';
                            $label = $it['label'] ?? '';
                            $sub   = $it['sub'] ?? '';
                            ?>
                            <div class="admin-sidebar__link-card" data-search="<?php echo h($label); ?>">
                                <a class="admin-sidebar__link" href="<?php echo h($href); ?>">
                                    <div class="admin-sidebar__link-main">
                                        <div class="admin-sidebar__link-icon">
                                            <svg class="gdy-icon" aria-hidden="true" focusable="false">
                                                <use href="<?php echo h(asset_url('assets/icons/gdy-icons.svg') . '#' . $icon); ?>"></use>
                                            </svg>
                                        </div>
                                        <div class="admin-sidebar__link-text">
                                            <div class="admin-sidebar__link-label"><?php echo h($label); ?></div>
                                            <?php if ($sub !== ''): ?>
                                                <div class="admin-sidebar__link-sub"><?php echo h($sub); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <svg class="gdy-icon admin-sidebar__link-arrow" aria-hidden="true" focusable="false"><use href="#chevron-left"></use></svg>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

            <?php else: ?>

                <div class="admin-sidebar__section" role="region" role="region" role="region" role="region" role="region" role="region" aria-label="&#039; . htmlspecialchars($label, ENT_QUOTES, &#039;UTF-8&#039;) . &#039;">
                    <div class="admin-sidebar__section-title"><?php echo h(__('نظرة عامة', 'نظرة عامة')); ?></div>

                    <div class="admin-sidebar__link-card <?php echo $currentPage === 'dashboard' ? 'is-active' : ''; ?>" data-search="الرئيسية لوحة التحكم dashboard">
                        <a class="admin-sidebar__link" href="<?php echo h($adminBase); ?>/index.php">
                            <div class="admin-sidebar__link-main">
                                <div class="admin-sidebar__link-icon"><svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#home"></use></svg></div>
                                <div class="admin-sidebar__link-text">
                                    <div class="admin-sidebar__link-label"><?php echo h(__('الرئيسية', 'الرئيسية')); ?></div>
                                    <div class="admin-sidebar__link-sub"><?php echo h(__('نظرة عامة على أداء النظام', 'نظرة عامة على أداء النظام')); ?></div>
                                </div>
                            </div>
                            <svg class="gdy-icon admin-sidebar__link-arrow" aria-hidden="true" focusable="false"><use href="#chevron-left"></use></svg>
                        </a>
                    </div>

                    <?php if (!$isWriter): ?>

                        <div class="admin-sidebar__link-card <?php echo ($currentPage === 'search') ? 'is-active' : ''; ?>" data-search="بحث search global">
                            <a class="admin-sidebar__link" href="<?php echo h($adminBase); ?>/search/index.php">
                                <div class="admin-sidebar__link-main">
                                    <div class="admin-sidebar__link-icon"><svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#search"></use></svg></div>
                                    <div class="admin-sidebar__link-text">
                                        <div class="admin-sidebar__link-label"><?php echo h(__('بحث شامل', 'بحث شامل')); ?></div>
                                        <div class="admin-sidebar__link-sub"><?php echo h(__('ابحث داخل اللوحة', 'ابحث داخل اللوحة')); ?></div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <div class="admin-sidebar__link-card <?php echo ($currentPage === 'notifications') ? 'is-active' : ''; ?>" data-search="إشعارات notifications">
                            <a class="admin-sidebar__link" href="<?php echo h($adminBase); ?>/notifications/index.php">
                                <div class="admin-sidebar__link-main">
                                    <div class="admin-sidebar__link-icon"><svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#bell"></use></svg></div>
                                    <div class="admin-sidebar__link-text">
                                        <div class="admin-sidebar__link-label"><?php echo h(__('الإشعارات', 'الإشعارات')); ?></div>
                                        <div class="admin-sidebar__link-sub"><?php echo h(__('مركز الإشعارات', 'مركز الإشعارات')); ?></div>
                                    </div>
                                    <?php if (!empty($__notifUnread)): ?>
                                        <span class="badge bg-danger rounded-pill" style="margin-inline-start:auto;align-self:center;">
                                            <?php echo (int)$__notifUnread; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </div>

                        <div class="admin-sidebar__link-card <?php echo ($currentPage === 'editorial') ? 'is-active' : ''; ?>" data-search="غرفة الأخبار editorial analytics newsroom">
                            <a class="admin-sidebar__link" href="<?php echo h($adminBase); ?>/editorial/index.php">
                                <div class="admin-sidebar__link-main">
                                    <div class="admin-sidebar__link-icon"><svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#chart"></use></svg></div>
                                    <div class="admin-sidebar__link-text">
                                        <div class="admin-sidebar__link-label"><?php echo h(__('غرفة الأخبار', 'غرفة الأخبار')); ?></div>
                                        <div class="admin-sidebar__link-sub"><?php echo h(__('مؤشرات التحرير والجدولة', 'مؤشرات التحرير والجدولة')); ?></div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <div class="admin-sidebar__link-card <?php echo ($currentPage === 'analytics') ? 'is-active' : ''; ?>" data-search="تحليلات analytics heatmap">
                            <a class="admin-sidebar__link" href="<?php echo h($adminBase); ?>/analytics/heatmap.php">
                                <div class="admin-sidebar__link-main">
                                    <div class="admin-sidebar__link-icon"><svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#map"></use></svg></div>
                                    <div class="admin-sidebar__link-text">
                                        <div class="admin-sidebar__link-label"><?php echo h(__('خريطة النشاط', 'خريطة النشاط')); ?></div>
                                        <div class="admin-sidebar__link-sub"><?php echo h(__('اليوم / الساعة', 'اليوم / الساعة')); ?></div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <div class="admin-sidebar__link-card" data-search="تصدير export csv excel">
                            <a class="admin-sidebar__link" href="<?php echo h($adminBase); ?>/export.php?entity=news">
                                <div class="admin-sidebar__link-main">
                                    <div class="admin-sidebar__link-icon"><svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#file-csv"></use></svg></div>
                                    <div class="admin-sidebar__link-text">
                                        <div class="admin-sidebar__link-label"><?php echo h(__('تصدير CSV', 'تصدير CSV')); ?></div>
                                        <div class="admin-sidebar__link-sub"><?php echo h(__('الأخبار (مثال)', 'الأخبار (مثال)')); ?></div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <div class="admin-sidebar__link-card <?php echo $currentPage === 'reports' ? 'is-active' : ''; ?>" data-search="التقارير analytics احصائيات">
                            <a class="admin-sidebar__link" href="<?php echo h($adminBase); ?>/reports/index.php">
                                <div class="admin-sidebar__link-main">
                                    <div class="admin-sidebar__link-icon"><svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#chart"></use></svg></div>
                                    <div class="admin-sidebar__link-text">
                                        <div class="admin-sidebar__link-label"><?php echo h(__('التقارير', 'التقارير')); ?></div>
                                        <div class="admin-sidebar__link-sub"><?php echo h(__('لوحة مؤشرات أداء', 'لوحة مؤشرات أداء')); ?></div>
                                    </div>
                                </div>
                                <svg class="gdy-icon admin-sidebar__link-arrow" aria-hidden="true" focusable="false"><use href="#chevron-left"></use></svg>
                            </a>
                        </div>

                    <?php endif; ?>
                </div>

                <div class="admin-sidebar__section" role="region" role="region" role="region" role="region" role="region" role="region" aria-label="&#039; . htmlspecialchars($label, ENT_QUOTES, &#039;UTF-8&#039;) . &#039;">
                    <div class="admin-sidebar__section-title"><?php echo h(__('المحتوى', 'المحتوى')); ?></div>

                    <?php
                    $newsMenuOpen = in_array($currentPage, ['news', 'posts', 'posts_review', 'news_review', 'feeds'], true);

                    $pendingReviewCount = 0;
                    if (!$isWriter && ($pdo instanceof \PDO)) {
                        try {
                            $hasStatus = false;
                            $chkCol = gdy_db_stmt_column_like($pdo, 'news', 'status');
                            if ($chkCol && $chkCol->fetch(\PDO::FETCH_ASSOC)) {
                                $hasStatus = true;
                            }
                            if ($hasStatus) {
                                $st = $pdo->query("SELECT COUNT(*) FROM news WHERE status = 'pending'");
                                $pendingReviewCount = $st ? (int)$st->fetchColumn() : 0;
                            }
                        } catch (\Throwable $e) {
                            $pendingReviewCount = 0;
                        }
                    }
                    ?>

                    <div class="admin-sidebar__link-card <?php echo $newsMenuOpen ? 'is-active' : ''; ?>" data-search="الأخبار المقالات المحتوى news articles rss feeds">
                        <button type="button"
                                class="admin-sidebar__link admin-sidebar__link--toggle"
                                data-bs-toggle="collapse"
                                data-bs-target="#gdyNewsMenu"
                                aria-expanded="<?php echo $newsMenuOpen ? 'true' : 'false'; ?>"
                                aria-controls="gdyNewsMenu">
                            <div class="admin-sidebar__link-main">
                                <div class="admin-sidebar__link-icon"><svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#news"></use></svg></div>
                                <div class="admin-sidebar__link-text">
                                    <div class="admin-sidebar__link-label">
                                        <?php echo h(__('الأخبار', 'الأخبار')); ?>
                                        <?php if (!$isWriter && $pendingReviewCount > 0): ?>
                                            <span class="badge bg-danger ms-2" title="<?php echo h(__('بانتظار المراجعة', 'بانتظار المراجعة')); ?>">
                                                <?php echo (int)$pendingReviewCount; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="admin-sidebar__link-sub"><?php echo h(__('إدارة الأخبار والمقالات', 'إدارة الأخبار والمقالات')); ?></div>
                                </div>
                            </div>
                            <svg class="gdy-icon admin-sidebar__link-arrow" aria-hidden="true" focusable="false"><use href="#chevron-left"></use></svg>
                        </button>
                    </div>

                    <div class="collapse <?php echo $newsMenuOpen ? 'show' : ''; ?>" id="gdyNewsMenu">
                        <div class="admin-sidebar__subnav">

                            <div class="admin-sidebar__link-card admin-sidebar__link-card--sub <?php echo in_array($currentPage, ['news', 'posts'], true) ? 'is-active' : ''; ?>" data-search="إدارة الأخبار posts news">
                                <a class="admin-sidebar__link" href="<?php echo h($adminBase); ?>/news/index.php">
                                    <div class="admin-sidebar__link-main">
                                        <div class="admin-sidebar__link-icon"><svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#news"></use></svg></div>
                                        <div class="admin-sidebar__link-text">
                                            <div class="admin-sidebar__link-label"><?php echo h(__('إدارة الأخبار', 'إدارة الأخبار')); ?></div>
                                            <div class="admin-sidebar__link-sub"><?php echo h(__('قائمة الأخبار والمسودات', 'قائمة الأخبار والمسودات')); ?></div>
                                        </div>
                                    </div>
                                    <svg class="gdy-icon admin-sidebar__link-arrow" aria-hidden="true" focusable="false"><use href="#chevron-left"></use></svg>
                                </a>
                            </div>

                            <?php if ($can('posts.view')): ?>
                                <div class="admin-sidebar__link-card admin-sidebar__link-card--sub <?php echo ($currentPage === 'translations') ? 'is-active' : ''; ?>" data-search="ترجمة ترجميات translations language">
                                    <a class="admin-sidebar__link" href="<?php echo h($adminBase); ?>/news/translations.php">
                                        <div class="admin-sidebar__link-main">
                                            <div class="admin-sidebar__link-icon"><svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#globe"></use></svg></div>
                                            <div class="admin-sidebar__link-text">
                                                <div class="admin-sidebar__link-label"><?php echo h(__('ترجمات الأخبار', 'ترجمات الأخبار')); ?></div>
                                                <div class="admin-sidebar__link-sub"><?php echo h(__('إدارة نسخ اللغات للمقالات', 'إدارة نسخ اللغات للمقالات')); ?></div>
                                            </div>
                                        </div>
                                        <svg class="gdy-icon admin-sidebar__link-arrow" aria-hidden="true" focusable="false"><use href="#chevron-left"></use></svg>
                                    </a>
                                </div>

                                <div class="admin-sidebar__link-card admin-sidebar__link-card--sub <?php echo ($currentPage === 'polls') ? 'is-active' : ''; ?>" data-search="استطلاع استطلاعات polls vote">
                                    <a class="admin-sidebar__link" href="<?php echo h($adminBase); ?>/news/polls.php">
                                        <div class="admin-sidebar__link-main">
                                            <div class="admin-sidebar__link-icon"><svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#poll"></use></svg></div>
                                            <div class="admin-sidebar__link-text">
                                                <div class="admin-sidebar__link-label"><?php echo h(__('استطلاعات الأخبار', 'استطلاعات الأخبار')); ?></div>
                                                <div class="admin-sidebar__link-sub"><?php echo h(__('إنشاء وإدارة استطلاعات داخل المقال', 'إنشاء وإدارة استطلاعات داخل المقال')); ?></div>
                                            </div>
                                        </div>
                                        <svg class="gdy-icon admin-sidebar__link-arrow" aria-hidden="true" focusable="false"><use href="#chevron-left"></use></svg>
                                    </a>
                                </div>

                                <?php
                                $adminMenu = [];
                                if (function_exists('g_do_hook')) {
                                    g_do_hook('admin.menu', $adminMenu);
                                }

                                $staticPluginLinks = [
                                    [
                                        'title' => 'أسئلة القرّاء',
                                        'url'   => '/admin/plugins/reader_questions/index.php',
                                        'icon'  => 'puzzle',
                                    ],
                                ];

                                foreach ($staticPluginLinks as $it) {
                                    $exists = false;
                                    foreach ($adminMenu as $mIt) {
                                        if (is_array($mIt) && (($mIt['url'] ?? '') === ($it['url'] ?? ''))) {
                                            $exists = true;
                                            break;
                                        }
                                    }
                                    if (!$exists) {
                                        $adminMenu[] = $it;
                                    }
                                }
                                ?>

                                <?php if (!empty($adminMenu) && is_array($adminMenu)): ?>
                                    <div class="admin-sidebar__section-title"><?php echo h(__('الإضافات', 'الإضافات')); ?></div>
                                    <?php foreach ($adminMenu as $item): ?>
                                        <?php
                                        if (!is_array($item)) {
                                            continue;
                                        }
                                        $title = (string)($item['title'] ?? '');
                                        $url   = (string)($item['url'] ?? '#');
                                        $icon  = (string)($item['icon'] ?? 'puzzle');
                                        $key   = (string)($item['key'] ?? ('plugin_' . preg_replace('~[^a-z0-9_]+~i', '_', strtolower($title))));
                                        $href  = (preg_match('~^https?://~i', $url)) ? $url : (rtrim((string)$siteBase, '/') . '/' . ltrim($url, '/'));
                                        $active = ($currentPage === $key);
                                        ?>
                                        <div class="admin-sidebar__link-card <?php echo $active ? 'is-active' : ''; ?>" data-search="<?php echo h($title); ?>">
                                            <a class="admin-sidebar__link" href="<?php echo h($href); ?>">
                                                <div class="admin-sidebar__link-main">
                                                    <div class="admin-sidebar__link-icon"><svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#<?php echo h($icon); ?>"></use></svg></div>
                                                    <div class="admin-sidebar__link-text">
                                                        <div class="admin-sidebar__link-label"><?php echo h($title); ?></div>
                                                        <?php if (!empty($item['sub'])): ?>
                                                            <div class="admin-sidebar__link-sub"><?php echo h((string)$item['sub']); ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <svg class="gdy-icon admin-sidebar__link-arrow" aria-hidden="true" focusable="false"><use href="#chevron-left"></use></svg>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if (!$isWriter): ?>
                                <div class="admin-sidebar__link-card admin-sidebar__link-card--sub <?php echo in_array($currentPage, ['posts_review', 'news_review'], true) ? 'is-active' : ''; ?>" data-search="مراجعة الأخبار pending review queue">
                                    <a class="admin-sidebar__link" href="<?php echo h($adminBase); ?>/news/review.php">
                                        <div class="admin-sidebar__link-main">
                                            <div class="admin-sidebar__link-icon"><svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#check"></use></svg></div>
                                            <div class="admin-sidebar__link-text">
                                                <div class="admin-sidebar__link-label">
                                                    <?php echo h(__('مراجعة الأخبار', 'مراجعة الأخبار')); ?>
                                                    <?php if ($pendingReviewCount > 0): ?>
                                                        <span class="badge bg-danger ms-2"><?php echo (int)$pendingReviewCount; ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="admin-sidebar__link-sub"><?php echo h(__('طابور المراجعة والاعتماد', 'طابور المراجعة والاعتماد')); ?></div>
                                            </div>
                                        </div>
                                        <svg class="gdy-icon admin-sidebar__link-arrow" aria-hidden="true" focusable="false"><use href="#chevron-left"></use></svg>
                                    </a>
                                </div>

                                <div class="admin-sidebar__link-card admin-sidebar__link-card--sub <?php echo $currentPage === 'feeds' ? 'is-active' : ''; ?>" data-search="مصادر rss خلاصات feeds import">
                                    <a class="admin-sidebar__link" href="<?php echo h($adminBase); ?>/feeds/index.php">
                                        <div class="admin-sidebar__link-main">
                                            <div class="admin-sidebar__link-icon"><svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#rss"></use></svg></div>
                                            <div class="admin-sidebar__link-text">
                                                <div class="admin-sidebar__link-label"><?php echo h(__('مصادر RSS', 'مصادر RSS')); ?></div>
                                                <div class="admin-sidebar__link-sub"><?php echo h(__('استيراد أخبار كمَسودّات', 'استيراد أخبار كمَسودّات')); ?></div>
                                            </div>
                                        </div>
                                        <svg class="gdy-icon admin-sidebar__link-arrow" aria-hidden="true" focusable="false"><use href="#chevron-left"></use></svg>
                                    </a>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>

                    <?php if (!$isWriter): ?>
                        <div class="admin-sidebar__link-card <?php echo ($currentPage === 'slider') ? 'is-active' : ''; ?>" data-search="سلايدر السلايدر slider">
                            <a class="admin-sidebar__link" href="<?php echo h($adminBase); ?>/slider/index.php">
                                <div class="admin-sidebar__link-main">
                                    <div class="admin-sidebar__link-icon"><svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#image"></use></svg></div>
                                    <div class="admin-sidebar__link-text">
                                        <div class="admin-sidebar__link-label"><?php echo h(__('السلايدر', 'السلايدر')); ?></div>
                                        <div class="admin-sidebar__link-sub"><?php echo h(__('إدارة شرائح الصفحة الرئيسية', 'إدارة شرائح الصفحة الرئيسية')); ?></div>
                                    </div>
                                </div>
                                <svg class="gdy-icon admin-sidebar__link-arrow" aria-hidden="true" focusable="false"><use href="#chevron-left"></use></svg>
                            </a>
                        </div>

                        <div class="admin-sidebar__link-card <?php echo $currentPage === 'categories' ? 'is-active' : ''; ?>" data-search="التصنيفات الأقسام categories sections">
                            <a class="admin-sidebar__link" href="<?php echo h($adminBase); ?>/categories/index.php">
                                <div class="admin-sidebar__link-main">
                                    <div class="admin-sidebar__link-icon"><svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#category"></use></svg></div>
                                    <div class="admin-sidebar__link-text">
                                        <div class="admin-sidebar__link-label"><?php echo h(__('التصنيفات', 'التصنيفات')); ?></div>
                                        <div class="admin-sidebar__link-sub"><?php echo h(__('إدارة التصنيفات والأقسام', 'إدارة التصنيفات والأقسام')); ?></div>
                                    </div>
                                </div>
                                <svg class="gdy-icon admin-sidebar__link-arrow" aria-hidden="true" focusable="false"><use href="#chevron-left"></use></svg>
                            </a>
                        </div>

                        <div class="admin-sidebar__link-card <?php echo $currentPage === 'tags' ? 'is-active' : ''; ?>" data-search="الوسوم tags">
                            <a class="admin-sidebar__link" href="<?php echo h($adminBase); ?>/tags/index.php">
                                <div class="admin-sidebar__link-main">
                                    <div class="admin-sidebar__link-icon"><svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#tag"></use></svg></div>
                                    <div class="admin-sidebar__link-text">
                                        <div class="admin-sidebar__link-label"><?php echo h(__('الوسوم', 'الوسوم')); ?></div>
                                        <div class="admin-sidebar__link-sub"><?php echo h(__('إدارة وسوم الأخبار', 'إدارة وسوم الأخبار')); ?></div>
                                    </div>
                                </div>
                                <svg class="gdy-icon admin-sidebar__link-arrow" aria-hidden="true" focusable="false"><use href="#chevron-left"></use></svg>
                            </a>
                        </div>

                        <div class="admin-sidebar__link-card <?php echo $currentPage === 'media' ? 'is-active' : ''; ?>" data-search="مكتبة الوسائط media رفع صور">
                            <a class="admin-sidebar__link" href="<?php echo h($adminBase); ?>/media/index.php">
                                <div class="admin-sidebar__link-main">
                                    <div class="admin-sidebar__link-icon"><svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#image"></use></svg></div>
                                    <div class="admin-sidebar__link-text">
                                        <div class="admin-sidebar__link-label"><?php echo h(__('مكتبة الوسائط', 'مكتبة الوسائط')); ?></div>
                                        <div class="admin-sidebar__link-sub"><?php echo h(__('رفع وإدارة الصور والملفات', 'رفع وإدارة الصور والملفات')); ?></div>
                                    </div>
                                </div>
                                <svg class="gdy-icon admin-sidebar__link-arrow" aria-hidden="true" focusable="false"><use href="#chevron-left"></use></svg>
                            </a>
                        </div>

                        <div class="admin-sidebar__link-card <?php echo $currentPage === 'videos' ? 'is-active' : ''; ?>" data-search="الفيديوهات المميزة فيديو featured videos">
                            <a class="admin-sidebar__link" href="<?php echo h($adminBase); ?>/manage_videos.php">
                                <div class="admin-sidebar__link-main">
                                    <div class="admin-sidebar__link-icon"><svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#video"></use></svg></div>
                                    <div class="admin-sidebar__link-text">
                                        <div class="admin-sidebar__link-label"><?php echo h(__('الفيديوهات المميزة', 'الفيديوهات المميزة')); ?></div>
                                        <div class="admin-sidebar__link-sub"><?php echo h(__('إدارة فيديوهات الصفحة الرئيسية', 'إدارة فيديوهات الصفحة الرئيسية')); ?></div>
                                    </div>
                                </div>
                                <svg class="gdy-icon admin-sidebar__link-arrow" aria-hidden="true" focusable="false"><use href="#chevron-left"></use></svg>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!$isWriter): ?>
                    <div class="admin-sidebar__section" role="region" role="region" role="region" role="region" role="region" role="region" aria-label="&#039; . htmlspecialchars($label, ENT_QUOTES, &#039;UTF-8&#039;) . &#039;">
                        <div class="admin-sidebar__section-title"><?php echo h(__('الإدارة', 'الإدارة')); ?></div>

                        <div class="admin-sidebar__link-card <?php echo $currentPage === 'comments' ? 'is-active' : ''; ?>" data-search="التعليقات comments moderation">
                            <a class="admin-sidebar__link" href="<?php echo h($adminBase); ?>/comments/index.php">
                                <div class="admin-sidebar__link-main">
                                    <div class="admin-sidebar__link-icon"><svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#comment"></use></svg></div>
                                    <div class="admin-sidebar__link-text">
                                        <div class="admin-sidebar__link-label"><?php echo h(__('إدارة التعليقات', 'إدارة التعليقات')); ?></div>
                                        <div class="admin-sidebar__link-sub"><?php echo h(__('مراجعة وقبول وحذف التعليقات', 'مراجعة وقبول وحذف التعليقات')); ?></div>
                                    </div>
                                </div>
                                <svg class="gdy-icon admin-sidebar__link-arrow" aria-hidden="true" focusable="false"><use href="#chevron-left"></use></svg>
                            </a>
                        </div>

                        <div class="admin-sidebar__link-card <?php echo $currentPage === 'system_health' ? 'is-active' : ''; ?>" data-search="فحص النظام health system diagnostics">
                            <a class="admin-sidebar__link" href="<?php echo h($adminBase); ?>/system/health.php">
                                <div class="admin-sidebar__link-main">
                                    <div class="admin-sidebar__link-icon"><svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#settings"></use></svg></div>
                                    <div class="admin-sidebar__link-text">
                                        <div class="admin-sidebar__link-label"><?php echo h(__('فحص النظام', 'فحص النظام')); ?></div>
                                        <div class="admin-sidebar__link-sub"><?php echo h(__('تشخيص الملفات والصلاحيات والخدمات', 'تشخيص الملفات والصلاحيات والخدمات')); ?></div>
                                    </div>
                                </div>
                                <svg class="gdy-icon admin-sidebar__link-arrow" aria-hidden="true" focusable="false"><use href="#chevron-left"></use></svg>
                            </a>
                        </div>

                        <?php if ($can('manage_users')): ?>
                            <div class="admin-sidebar__link-card <?php echo $currentPage === 'users' ? 'is-active' : ''; ?>" data-search="المستخدمون users">
                                <a class="admin-sidebar__link" href="<?php echo h($adminBase); ?>/users/index.php">
                                    <div class="admin-sidebar__link-main">
                                        <div class="admin-sidebar__link-icon"><svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#users"></use></svg></div>
                                        <div class="admin-sidebar__link-text">
                                            <div class="admin-sidebar__link-label"><?php echo h(__('المستخدمون', 'المستخدمون')); ?></div>
                                            <div class="admin-sidebar__link-sub"><?php echo h(__('إدارة الحسابات والصلاحيات', 'إدارة الحسابات والصلاحيات')); ?></div>
                                        </div>
                                    </div>
                                    <svg class="gdy-icon admin-sidebar__link-arrow" aria-hidden="true" focusable="false"><use href="#chevron-left"></use></svg>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if ($can('manage_roles')): ?>
                            <div class="admin-sidebar__link-card <?php echo $currentPage === 'roles' ? 'is-active' : ''; ?>" data-search="الأدوار roles">
                                <a class="admin-sidebar__link" href="<?php echo h($adminBase); ?>/roles/index.php">
                                    <div class="admin-sidebar__link-main">
                                        <div class="admin-sidebar__link-icon"><svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#user"></use></svg></div>
                                        <div class="admin-sidebar__link-text">
                                            <div class="admin-sidebar__link-label"><?php echo h(__('الأدوار', 'الأدوار')); ?></div>
                                            <div class="admin-sidebar__link-sub"><?php echo h(__('صلاحيات النظام', 'صلاحيات النظام')); ?></div>
                                        </div>
                                    </div>
                                    <svg class="gdy-icon admin-sidebar__link-arrow" aria-hidden="true" focusable="false"><use href="#chevron-left"></use></svg>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if ($can('opinion_authors.manage')): ?>
                            <div class="admin-sidebar__link-card <?php echo $currentPage === 'opinion_authors' ? 'is-active' : ''; ?>" data-search="كتاب الرأي opinion authors">
                                <a class="admin-sidebar__link" href="<?php echo h($adminBase); ?>/opinion_authors/index.php">
                                    <div class="admin-sidebar__link-main">
                                        <div class="admin-sidebar__link-icon"><svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#pen"></use></svg></div>
                                        <div class="admin-sidebar__link-text">
                                            <div class="admin-sidebar__link-label"><?php echo h(__('كتاب الرأي', 'كتاب الرأي')); ?></div>
                                            <div class="admin-sidebar__link-sub"><?php echo h(__('إدارة كتّاب الرأي', 'إدارة كتّاب الرأي')); ?></div>
                                        </div>
                                    </div>
                                    <svg class="gdy-icon admin-sidebar__link-arrow" aria-hidden="true" focusable="false"><use href="#chevron-left"></use></svg>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if ($can('team.manage')): ?>
                            <div class="admin-sidebar__link-card <?php echo $currentPage === 'team' ? 'is-active' : ''; ?>" data-search="فريق العمل team">
                                <a class="admin-sidebar__link" href="<?php echo h($adminBase); ?>/team/index.php">
                                    <div class="admin-sidebar__link-main">
                                        <div class="admin-sidebar__link-icon"><svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#team"></use></svg></div>
                                        <div class="admin-sidebar__link-text">
                                            <div class="admin-sidebar__link-label"><?php echo h(__('فريق العمل', 'فريق العمل')); ?></div>
                                            <div class="admin-sidebar__link-sub"><?php echo h(__('إدارة صفحة فريق العمل', 'إدارة صفحة فريق العمل')); ?></div>
                                        </div>
                                    </div>
                                    <svg class="gdy-icon admin-sidebar__link-arrow" aria-hidden="true" focusable="false"><use href="#chevron-left"></use></svg>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if ($can('contact.manage')): ?>
                            <div class="admin-sidebar__link-card <?php echo $currentPage === 'contact' ? 'is-active' : ''; ?>" data-search="رسائل التواصل contact">
                                <a class="admin-sidebar__link" href="<?php echo h($adminBase); ?>/contact/index.php">
                                    <div class="admin-sidebar__link-main">
                                        <div class="admin-sidebar__link-icon"><svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#mail"></use></svg></div>
                                        <div class="admin-sidebar__link-text">
                                            <div class="admin-sidebar__link-label"><?php echo h(__('رسائل التواصل', 'رسائل التواصل')); ?></div>
                                            <div class="admin-sidebar__link-sub"><?php echo h(__('قراءة وإدارة رسائل الموقع', 'قراءة وإدارة رسائل الموقع')); ?></div>
                                        </div>
                                    </div>
                                    <svg class="gdy-icon admin-sidebar__link-arrow" aria-hidden="true" focusable="false"><use href="#chevron-left"></use></svg>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if ($can('ads.manage')): ?>
                            <div class="admin-sidebar__link-card <?php echo $currentPage === 'ads' ? 'is-active' : ''; ?>" data-search="الإعلانات ads">
                                <a class="admin-sidebar__link" href="<?php echo h($adminBase); ?>/ads/index.php">
                                    <div class="admin-sidebar__link-main">
                                        <div class="admin-sidebar__link-icon"><svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#ads"></use></svg></div>
                                        <div class="admin-sidebar__link-text">
                                            <div class="admin-sidebar__link-label"><?php echo h(__('الإعلانات', 'الإعلانات')); ?></div>
                                            <div class="admin-sidebar__link-sub"><?php echo h(__('إدارة أماكن الإعلانات', 'إدارة أماكن الإعلانات')); ?></div>
                                        </div>
                                    </div>
                                    <svg class="gdy-icon admin-sidebar__link-arrow" aria-hidden="true" focusable="false"><use href="#chevron-left"></use></svg>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if ($can('glossary.manage')): ?>
                            <div class="admin-sidebar__link-card <?php echo $currentPage === 'glossary' ? 'is-active' : ''; ?>" data-search="القاموس glossary">
                                <a class="admin-sidebar__link" href="<?php echo h($adminBase); ?>/glossary/index.php">
                                    <div class="admin-sidebar__link-main">
                                        <div class="admin-sidebar__link-icon"><svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#book"></use></svg></div>
                                        <div class="admin-sidebar__link-text">
                                            <div class="admin-sidebar__link-label"><?php echo h(__('القاموس', 'القاموس')); ?></div>
                                            <div class="admin-sidebar__link-sub"><?php echo h(__('إدارة المصطلحات', 'إدارة المصطلحات')); ?></div>
                                        </div>
                                    </div>
                                    <svg class="gdy-icon admin-sidebar__link-arrow" aria-hidden="true" focusable="false"><use href="#chevron-left"></use></svg>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if ($can('manage_plugins')): ?>
                            <div class="admin-sidebar__link-card <?php echo $currentPage === 'plugins' ? 'is-active' : ''; ?>" data-search="الإضافات plugins">
                                <a class="admin-sidebar__link" href="<?php echo h($adminBase); ?>/plugins/index.php">
                                    <div class="admin-sidebar__link-main">
                                        <div class="admin-sidebar__link-icon"><svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#plugin"></use></svg></div>
                                        <div class="admin-sidebar__link-text">
                                            <div class="admin-sidebar__link-label"><?php echo h(__('الإضافات', 'الإضافات')); ?></div>
                                            <div class="admin-sidebar__link-sub"><?php echo h(__('تفعيل/تعطيل مكونات النظام', 'تفعيل/تعطيل مكونات النظام')); ?></div>
                                        </div>
                                    </div>
                                    <svg class="gdy-icon admin-sidebar__link-arrow" aria-hidden="true" focusable="false"><use href="#chevron-left"></use></svg>
                                </a>
                            </div>
                        <?php endif; ?>

                        <div class="admin-sidebar__link-card <?php echo $currentPage === 'settings' ? 'is-active' : ''; ?>" data-search="الإعدادات settings">
                            <a class="admin-sidebar__link" href="<?php echo h($adminBase); ?>/settings/index.php">
                                <div class="admin-sidebar__link-main">
                                    <div class="admin-sidebar__link-icon"><svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#settings"></use></svg></div>
                                    <div class="admin-sidebar__link-text">
                                        <div class="admin-sidebar__link-label"><?php echo h(__('الإعدادات', 'الإعدادات')); ?></div>
                                        <div class="admin-sidebar__link-sub"><?php echo h(__('إعدادات الموقع العامة', 'إعدادات الموقع العامة')); ?></div>
                                    </div>
                                </div>
                                <svg class="gdy-icon admin-sidebar__link-arrow" aria-hidden="true" focusable="false"><use href="#chevron-left"></use></svg>
                            </a>
                        </div>

                        <div class="admin-sidebar__link-card <?php echo $currentPage === 'translations' ? 'is-active' : ''; ?>" data-search="الترجمة translations i18n">
                            <a class="admin-sidebar__link" href="<?php echo h($adminBase); ?>/translations.php">
                                <div class="admin-sidebar__link-main">
                                    <div class="admin-sidebar__link-icon"><svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#language"></use></svg></div>
                                    <div class="admin-sidebar__link-text">
                                        <div class="admin-sidebar__link-label"><?php echo h(__('الترجمة', 'الترجمة')); ?></div>
                                        <div class="admin-sidebar__link-sub"><?php echo h(__('إدارة ترجمات الواجهة والمحتوى', 'إدارة ترجمات الواجهة والمحتوى')); ?></div>
                                    </div>
                                </div>
                                <svg class="gdy-icon admin-sidebar__link-arrow" aria-hidden="true" focusable="false"><use href="#chevron-left"></use></svg>
                            </a>
                        </div>

                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </nav>

        <footer class="admin-sidebar__footer">
            <div class="admin-sidebar__user">
                <div class="admin-sidebar__user-avatar">
                    <?php if ($userAvatar): ?>
                        <img src="<?php echo h($userAvatar); ?>" alt="صورة المستخدم" />
                    <?php else: ?>
                        <svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#user"></use></svg>
                    <?php endif; ?>
                </div>
                <div class="admin-sidebar__user-info">
                    <div class="admin-sidebar__user-name"><?php echo h($userName); ?></div>
                    <div class="admin-sidebar__user-role"><?php echo h((string)$userRole); ?></div>
                </div>
            </div>

            <div class="admin-sidebar__footer-actions">
                <a href="<?php echo h($siteBase); ?>/" class="admin-sidebar__action-btn" title="الموقع الرئيسي" aria-label="الانتقال للموقع الرئيسي">
                    <svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#home"></use></svg>
                </a>
                <button class="admin-sidebar__action-btn" id="darkModeToggle" type="button" title="الوضع الليلي" aria-label="تبديل الوضع الليلي">
                    <svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#moon"></use></svg>
                </button>
                <a href="<?php echo h($adminBase); ?>/logout.php" class="admin-sidebar__action-btn admin-sidebar__action-btn--danger" title="تسجيل الخروج" aria-label="تسجيل الخروج">
                    <svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="#logout"></use></svg>
                </a>
            </div>

            <div class="admin-sidebar__footer-version" style="margin-top:10px;color:rgba(255,255,255,.70);font-size:.78rem;display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap;">
                <span>© <?php echo (int)date('Y'); ?> <?php echo h(defined('GODYAR_CMS_COPYRIGHT') ? (string)GODYAR_CMS_COPYRIGHT : 'Godyar News Platform'); ?></span>
                <span style="font-weight:800;"><?php echo h(function_exists('gdy_cms_badge') ? gdy_cms_badge() : 'Godyar News Platform v3.5.0-stable'); ?></span>
            </div>
        </footer>

        <div class="admin-sidebar__section" role="region" role="region" role="region" role="region" role="region" role="region" aria-label="&#039; . htmlspecialchars($label, ENT_QUOTES, &#039;UTF-8&#039;) . &#039;">
            <div class="admin-sidebar__section-title"><?php echo h(__('language', 'اللغة')); ?></div>
            <div style="display:flex;gap:8px;padding:10px 6px;flex-wrap:wrap;">
                <a class="admin-sidebar__pill <?php echo gdy_lang() === 'ar' ? 'is-active' : ''; ?>" href="<?php echo h(gdy_lang_url('ar')); ?>">AR</a>
                <a class="admin-sidebar__pill <?php echo gdy_lang() === 'en' ? 'is-active' : ''; ?>" href="<?php echo h(gdy_lang_url('en')); ?>">EN</a>
                <a class="admin-sidebar__pill <?php echo gdy_lang() === 'fr' ? 'is-active' : ''; ?>" href="<?php echo h(gdy_lang_url('fr')); ?>">FR</a>
            </div>
        </div>

    </div>
</aside>

<style nonce="<?= h($cspNonceSafe) ?>">
:root {
  --gdy-admin-sidebar-width: 320px;
  --gdy-admin-sidebar-collapsed-width: 88px;
  --gdy-admin-sidebar-gap: 2px;
  --gdy-sidebar-bg: radial-gradient(circle at top, #020617 0, #020617 55%, #000 100%);
  --gdy-sidebar-border: rgba(148,163,184,0.18);
  --gdy-sidebar-card-bg: rgba(15,23,42,0.98);
  --gdy-sidebar-text: #e5e7eb;
  --gdy-sidebar-muted: #9ca3af;
  --gdy-sidebar-accent: var(--gdy-accent, #0ea5e9);
  --gdy-sidebar-accent-soft: color-mix(in srgb, var(--gdy-sidebar-accent) 55%, #ffffff 45%);
  --gdy-sidebar-danger: var(--gdy-danger, #ef4444);
}

.admin-sidebar{
  position: fixed;
  top: 0;
  bottom: 0;
  width: var(--gdy-admin-sidebar-width);
  min-width: var(--gdy-admin-sidebar-width);
  max-width: var(--gdy-admin-sidebar-width);
  right: 0;
  left: auto;
  background: var(--gdy-sidebar-bg);
  color: var(--gdy-sidebar-text);
  z-index: 1040;
  transition: width .18s ease, min-width .18s ease, max-width .18s ease, transform .18s ease;
}
html[dir="ltr"] .admin-sidebar{ left:0; right:auto; }

.admin-sidebar__header-actions{ display:flex; align-items:center; gap:.45rem; }
.admin-sidebar__desktop-toggle,
.admin-sidebar__toggle{
  width:34px; height:34px; border-radius:12px; border:1px solid var(--gdy-sidebar-border);
  background:#020617; color:var(--gdy-sidebar-text); display:inline-flex; align-items:center; justify-content:center;
}
.admin-sidebar__toggle{ display:none; }

@media (min-width: 992px) {
  html[dir="rtl"] .admin-content,
  html[dir="rtl"] .gdy-admin-page{
    margin-right: calc(var(--gdy-admin-sidebar-width) + var(--gdy-admin-sidebar-gap)) !important;
    margin-left: 0 !important;
    width: calc(100% - (var(--gdy-admin-sidebar-width) + var(--gdy-admin-sidebar-gap))) !important;
    max-width: calc(100% - (var(--gdy-admin-sidebar-width) + var(--gdy-admin-sidebar-gap))) !important;
    box-sizing: border-box;
  }
  html[dir="ltr"] .admin-content,
  html[dir="ltr"] .gdy-admin-page{
    margin-left: calc(var(--gdy-admin-sidebar-width) + var(--gdy-admin-sidebar-gap)) !important;
    margin-right: 0 !important;
    width: calc(100% - (var(--gdy-admin-sidebar-width) + var(--gdy-admin-sidebar-gap))) !important;
    max-width: calc(100% - (var(--gdy-admin-sidebar-width) + var(--gdy-admin-sidebar-gap))) !important;
    box-sizing: border-box;
  }
  .admin-content,
  .gdy-admin-page,
  .gdy-dashboard-wrapper,
  .gdy-dashboard-wrapper > .container-fluid{ overflow-x:hidden; }

  body.admin-sidebar-collapsed{
    --gdy-admin-sidebar-width: var(--gdy-admin-sidebar-collapsed-width);
  }
  body.admin-sidebar-collapsed .admin-sidebar__quick,
  body.admin-sidebar-collapsed .admin-sidebar__subtitle,
  body.admin-sidebar-collapsed .admin-sidebar__search-wrapper,
  body.admin-sidebar-collapsed .admin-sidebar__footer-version,
  body.admin-sidebar-collapsed .admin-sidebar__section-title,
  body.admin-sidebar-collapsed .admin-sidebar__user-info,
  body.admin-sidebar-collapsed .admin-sidebar__link-sub,
  body.admin-sidebar-collapsed .admin-sidebar__brand-text,
  body.admin-sidebar-collapsed .admin-sidebar__section[aria-label="اللغة"]{ display:none !important; }
  body.admin-sidebar-collapsed .admin-sidebar__link-text{ display:none !important; }
  body.admin-sidebar-collapsed .admin-sidebar__link,
  body.admin-sidebar-collapsed .admin-sidebar__link-main{ justify-content:center; }
  body.admin-sidebar-collapsed .admin-sidebar__link-arrow{ display:none !important; }
  body.admin-sidebar-collapsed .admin-sidebar__footer-actions{ justify-content:center; flex-wrap:wrap; }
  body.admin-sidebar-collapsed .admin-sidebar__user{ justify-content:center; }
  body.admin-sidebar-collapsed .admin-sidebar__header{ justify-content:center; }
  body.admin-sidebar-collapsed .admin-sidebar__header-actions{ position:absolute; inset-inline-start:50%; transform:translateX(-50%); top:.85rem; }
}

@media (max-width: 991.98px){
  .admin-sidebar{ transform: translateX(100%); }
  html[dir="ltr"] .admin-sidebar{ transform: translateX(-100%); }
  body.admin-sidebar-open .admin-sidebar{ transform: translateX(0); }
  .admin-sidebar__toggle{ display:inline-flex; }
  .admin-sidebar__desktop-toggle{ display:none; }
  .admin-content,
  .gdy-admin-page{ margin:0 !important; width:100% !important; max-width:100% !important; }
}

.admin-sidebar__card{
  height: 100%; display:flex; flex-direction:column; background:var(--gdy-sidebar-card-bg);
  border-inline-start:1px solid var(--gdy-sidebar-border); box-shadow:0 0 25px rgba(15,23,42,.75);
}
</style>

<script nonce="<?= h($cspNonceSafe) ?>">
document.addEventListener('DOMContentLoaded', function () {
  var openBtn  = document.getElementById('gdyAdminMenuBtn');
  var closeBtn = document.getElementById('sidebarToggle');
  var desktopToggle = document.getElementById('sidebarDesktopToggle');
  var backdrop = document.getElementById('gdyAdminBackdrop');
  var searchInput = document.getElementById('sidebarSearch');
  var searchResults = document.getElementById('sidebarSearchResults');
  var darkToggle = document.getElementById('darkModeToggle');
  var cards = Array.prototype.slice.call(document.querySelectorAll('.admin-sidebar__link-card'));
  var desktopMq = window.matchMedia ? window.matchMedia('(min-width: 992px)') : null;
  var collapseKey = 'gdy_admin_sidebar_collapsed';

  function isDesktop(){ return desktopMq ? desktopMq.matches : window.innerWidth >= 992; }
  function setBackdrop(open){ if (backdrop) backdrop.hidden = !open; }
  function openSidebar(){ document.body.classList.add('admin-sidebar-open'); setBackdrop(true); }
  function closeSidebar(){ document.body.classList.remove('admin-sidebar-open'); setBackdrop(false); }
  function applyCollapsed(state){
    if (!isDesktop()) { document.body.classList.remove('admin-sidebar-collapsed'); return; }
    document.body.classList.toggle('admin-sidebar-collapsed', !!state);
    try { localStorage.setItem(collapseKey, state ? '1' : '0'); } catch (e) {}
  }
  function loadCollapsed(){
    var state = false;
    try { state = localStorage.getItem(collapseKey) === '1'; } catch (e) {}
    applyCollapsed(state);
  }

  if (openBtn) openBtn.addEventListener('click', openSidebar);
  if (closeBtn) closeBtn.addEventListener('click', function(){ if (isDesktop()) return; closeSidebar(); });
  if (desktopToggle) desktopToggle.addEventListener('click', function(){ applyCollapsed(!document.body.classList.contains('admin-sidebar-collapsed')); });
  if (backdrop) backdrop.addEventListener('click', closeSidebar);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeSidebar(); });
  window.addEventListener('resize', function(){ if (isDesktop()) { closeSidebar(); loadCollapsed(); } else { document.body.classList.remove('admin-sidebar-collapsed'); } });
  loadCollapsed();

  document.querySelectorAll('.admin-sidebar a').forEach(function (a) {
    a.addEventListener('click', function () { if (!isDesktop()) closeSidebar(); });
  });

  if (searchInput && searchResults) {
    searchInput.addEventListener('input', function () {
      var q = (searchInput.value || '').trim().toLowerCase();
      searchResults.innerHTML = '';
      if (!q) { searchResults.style.display = 'none'; return; }
      var matches = cards.filter(function (card) {
        var hay = (card.getAttribute('data-search') || '') + ' ' + (card.textContent || '');
        return hay.toLowerCase().indexOf(q) !== -1;
      }).slice(0, 12);
      searchResults.style.display = 'block';
      if (!matches.length) {
        var div = document.createElement('div');
        div.className = 'admin-sidebar__search-result-item';
        div.textContent = 'لا توجد نتائج مطابقة';
        searchResults.appendChild(div);
        return;
      }
      matches.forEach(function (card) {
        var link = card.querySelector('a');
        if (!link) return;
        var labelEl = card.querySelector('.admin-sidebar__link-label');
        var label = labelEl ? labelEl.textContent.trim() : link.textContent.trim();
        var a = document.createElement('a');
        a.href = link.getAttribute('href');
        a.className = 'admin-sidebar__search-result-item';
        a.textContent = label;
        searchResults.appendChild(a);
      });
    });
    document.addEventListener('click', function (e) {
      if (!searchResults.contains(e.target) && e.target !== searchInput) searchResults.style.display = 'none';
    });
  }

  if (darkToggle) darkToggle.addEventListener('click', function () { document.body.classList.toggle('godyar-dark'); });
});
</script>