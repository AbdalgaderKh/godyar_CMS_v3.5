<?php
echo "\n<!-- VIEW_FILE: frontend/category.php v11 -->\n";

if (session_status() !== PHP_SESSION_ACTIVE) {
    gdy_session_start();
}

$controllerPath = __DIR__ . '/controllers/HomeController.php';
if (is_file($controllerPath)) {
    require_once $controllerPath;
} else {
    require_once __DIR__ . '/includes/bootstrap.php';
}

if (!function_exists('h')) {
    function h($v): string
    {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

$slug = isset($_GET['slug']) ? trim((string)$_GET['slug']) : '';

$category = null;
$newsList = [];
$pageTitle = 'تصنيف الأخبار';
$notFound = false;

if (isset($baseUrl) && $baseUrl !== '') {
    $baseUrl = rtrim($baseUrl, '/');
} elseif (function_exists('base_url')) {
    $baseUrl = rtrim(base_url(), '/');
} else {
    $baseUrl = '';
}

$sidebarHidden = $GLOBALS['gdy_sidebar_hidden'] ?? false;

$sidebarHidden = false;
$GLOBALS['gdy_sidebar_hidden'] = false;
$gdySiteSettings = $GLOBALS['site_settings'] ?? [];
if (!is_array($gdySiteSettings)) { $gdySiteSettings = []; }
$gdySiteSettings['layout_sidebar_mode'] = 'visible';
$GLOBALS['site_settings'] = $gdySiteSettings;

$buildNewsUrl = function (array $row) use ($baseUrl): string {
    $id = isset($row['id']) ? (int)$row['id'] : 0;
    $slug = isset($row['slug']) ? trim((string)$row['slug']) : '';

    $prefix = rtrim($baseUrl, '/');
    if ($prefix !== '') {
        $prefix .= '/';
    }

    
    
    if ($id > 0) {
        return $prefix . 'news/id/' . $id;
    }
    
    if ($slug !== '') {
        return $prefix . 'news/' .rawurlencode($slug);
    }
    return $prefix . 'news';
};

if (class_exists('HomeController')) {
    if ($slug !== '') {
        $category = HomeController::getCategoryBySlug($slug);

        
        if (!$category && $slug === 'policy') {
            $category = HomeController::getCategoryBySlug('politics');
        }
    }

    if ($category) {
        $catName = $category['name'] ?? $slug;
        $pageTitle = HomeController::makePageTitle('تصنيف: ' . $catName);
        $newsList = HomeController::getNewsByCategory((int)$category['id'], 30);

        
        if (!$newsList) {
            try {
                $pdo = HomeController::db();
                if ($pdo) {
                    $dbName = (string)$pdo->query("SELECT DATABASE()")->fetchColumn();
                    $cnt = (int)$pdo->query("SELECT COUNT(*) FROM news WHERE category_id=".(int)$category['id'])->fetchColumn();
                    error_log("[Category] empty list for slug={$slug} cat_id=".(int)$category['id']." db={$dbName} cnt={$cnt}");
                    $GLOBALS['__gdy_category_debug'] = "db={$dbName}; cat_id=".(int)$category['id']."; cnt={$cnt}";
                }
            } catch (\Throwable $e) {}
        }
    } else {
        http_response_code(404);
        $notFound = true;
        $pageTitle = HomeController::makePageTitle('تصنيف غير موجود');
    }

    [$headerFile, $footerFile] = HomeController::resolveLayout();
} else {
    $notFound = true;
    $pageTitle = 'تصنيف الأخبار';
    $headerFile = $footerFile = null;
}

if (!empty($headerFile) && is_file($headerFile)) {
    require $headerFile;
} else {
    ?>
    <!doctype html>
    <html lang = "ar" dir = "rtl">
    <head><meta charset = "utf-8">
<?php require ROOT_PATH . '/frontend/views/partials/theme_head.php'; ?>

        <title><?php echo h($pageTitle); ?></title>
        <meta name = "viewport" content = "width=device-width, initial-scale=1">
        <link href="<?= h(asset_url('assets/vendor/bootstrap/css/bootstrap.rtl.min.css')) ?>" rel = "stylesheet">
    </head>
    <body class = "bg-dark text-light">
    <header class = "py-3 mb-4 border-bottom border-secondary">
        <div class = "container d-flex flex-wrap justify-content-between align-items-center">
            <h1 class = "h4 mb-0">Godyar News</h1>
            <span class = "text-muted small">تصنيف الأخبار</span>
        </div>
    </header>
    <?php
}
?>

<main class = "container my-4">
    <div class = "row gy-3">
        <!-- عمود المحتوى الرئيسي -->
        <div class = "<?php echo $sidebarHidden ? 'col-12' : 'col-lg-8'; ?>">
            <?php if ($notFound): ?>
                <div class = "alert alert-warning rounded-3">
                    <h2 class = "h5.mb-2">التصنيف غير موجود</h2>
                    <p class = "mb-0">التصنيف المطلوب غير متوفر حالياً، الرجاء التحقق من الرابط أو اختيار تصنيف آخر من الأقسام . </p>
                </div>
            <?php else: ?>
                <section class = "mb-4">
                    <h2 class = "h4 mb-1">
                        تصنيف الأخبار:
                        <span class = "text-info">
                            <?php echo h($category['name'] ?? $slug); ?>
                        </span>
                    </h2>

                    <?php if (!empty($category['description'])): ?>
                        <p class = "text-muted small mb-3">
                            <?php echo h($category['description']); ?>
                        </p>
                    <?php else: ?>
                        <p class = "text-muted small mb-3">
                            يتم عرض آخر الأخبار المنشورة ضمن هذا التصنيف كما تم إضافتها من لوحة التحكم .
                        </p>
                    <?php endif; ?>
                </section>

                <?php if (!empty($newsList)): ?>
                    <div class = "list-group">
                        <?php foreach ($newsList as $row): ?>
                            <?php
                                $title = (string)($row['title'] ?? '');
                                $date = !empty($row['created_at'])
                                    ? date('Y-m-d', strtotime((string)$row['created_at']))
                                    : '';
                                $views = isset($row['views']) ? (int)$row['views'] : null;
                                $newsUrl = $buildNewsUrl($row);
                            ?>
                            <a href = "<?php echo h($newsUrl); ?>"
                               class = "list-group-item list-group-item-action border-0 mb-2 rounded-3 shadow-sm">
                                <div class = "d-flex w-100 justify-content-between">
                                    <h3 class = "h6 mb-1"><?php echo h($title); ?></h3>
                                    <?php if ($date): ?>
                                        <small class = "text-muted ms-2">
                                            <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
                                            <?php echo h($date); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                <?php if ($views !== null): ?>
                                    <small class = "text-muted">
                                        <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
                                        <?php echo number_format($views); ?> مشاهدة
                                    </small>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <?php if (!empty($GLOBALS['__gdy_category_debug'])): ?><!-- <?= h($GLOBALS['__gdy_category_debug']) ?> --><?php endif; ?>
                    <div class = "alert alert-info rounded-3">
                        لا توجد أخبار ضمن هذا التصنيف حالياً .
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- عمود السايدبار (فقط إذا لم يتم إخفاؤه من الإعدادات) -->
        <?php if (!$sidebarHidden): ?>
            <div class = "col-lg-4">
                <?php
                $sidebarPath = __DIR__ . '/views/partials/sidebar.php';
                if (is_file($sidebarPath)) {
                    require $sidebarPath;
                }
                ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php

if (!empty($footerFile) && is_file($footerFile)) {
    require $footerFile;
} else {
    ?>
    <footer class = "border-top border-secondary mt-4 py-3 text-center small text-muted">
        &copy; <?php echo date('Y'); ?> Godyar News
    </footer>
    </body>
    </html>
    <?php
}
