<?php
require_once dirname(__DIR__) . '/_admin_guard.php';
require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (!function_exists('h')) {
    function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

$pdo = function_exists('gdy_pdo_safe') ? gdy_pdo_safe() : (isset($pdo) ? $pdo : null);
$currentPage = 'editorial';
$pageTitle = 'غرفة الأخبار';
$quickStats = array('posts'=>0,'users'=>0,'comments'=>0);

$stats = array(
    'published' => 0,
    'drafts' => 0,
    'scheduled' => 0,
    'breaking' => 0,
    'views_7d' => 0,
    'comments_pending' => 0,
);
$mostRead = array();
$scheduled = array();
$categoryStats = array();
$recent = array();

$hasPublishAt = false;
$hasViews = false;
$hasBreaking = false;
$hasComments = false;

if ($pdo instanceof PDO) {
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM news")->fetchAll(PDO::FETCH_COLUMN);
        $hasPublishAt = in_array('publish_at', $cols, true);
        $hasViews = in_array('views', $cols, true) || in_array('view_count', $cols, true);
        $hasBreaking = in_array('is_breaking', $cols, true);
        $viewCol = in_array('views', $cols, true) ? 'views' : (in_array('view_count', $cols, true) ? 'view_count' : null);

        $stats['published'] = (int)($pdo->query("SELECT COUNT(*) FROM news WHERE status='published'")->fetchColumn() ?: 0);
        $stats['drafts'] = (int)($pdo->query("SELECT COUNT(*) FROM news WHERE status='draft'")->fetchColumn() ?: 0);
        if ($hasPublishAt) {
            $stats['scheduled'] = (int)($pdo->query("SELECT COUNT(*) FROM news WHERE status IN ('published','scheduled','draft') AND publish_at IS NOT NULL AND publish_at > NOW()")->fetchColumn() ?: 0);
        }
        if ($hasBreaking) {
            $stats['breaking'] = (int)($pdo->query("SELECT COUNT(*) FROM news WHERE status='published' AND is_breaking=1")->fetchColumn() ?: 0);
        }
        if ($viewCol) {
            $stats['views_7d'] = (int)($pdo->query("SELECT COALESCE(SUM(".$viewCol."),0) FROM news WHERE status='published' AND COALESCE(publish_at,published_at,created_at) >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn() ?: 0);
        }
        try {
            $hasComments = (bool)($pdo->query("SHOW TABLES LIKE 'news_comments'")->fetchColumn());
            if ($hasComments) {
                $stats['comments_pending'] = (int)($pdo->query("SELECT COUNT(*) FROM news_comments WHERE status IN ('pending','0')")->fetchColumn() ?: 0);
            }
        } catch (Throwable $e) {
            $hasComments = false;
        }

        $orderCol = $viewCol ?: ($hasPublishAt ? 'publish_at' : 'id');
        $sqlMost = "SELECT id, title, slug, status, COALESCE(publish_at,published_at,created_at) AS published_on" . ($viewCol ? ", COALESCE(".$viewCol.",0) AS views" : ", 0 AS views") . " FROM news WHERE status='published' ORDER BY " . $orderCol . " DESC, id DESC LIMIT 10";
        $mostRead = $pdo->query($sqlMost)->fetchAll(PDO::FETCH_ASSOC);

        if ($hasPublishAt) {
            $sqlSched = "SELECT id, title, slug, status, publish_at FROM news WHERE publish_at IS NOT NULL AND publish_at > NOW() ORDER BY publish_at ASC LIMIT 10";
            $scheduled = $pdo->query($sqlSched)->fetchAll(PDO::FETCH_ASSOC);
        }

        $categoryStats = $pdo->query("SELECT c.id, c.name, c.slug, COUNT(n.id) AS total_news FROM categories c LEFT JOIN news n ON n.category_id = c.id AND n.status='published' GROUP BY c.id, c.name, c.slug ORDER BY total_news DESC, c.id ASC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);

        $recentSql = "SELECT id, title, slug, status, COALESCE(publish_at,published_at,created_at) AS dt FROM news ORDER BY COALESCE(publish_at,published_at,created_at) DESC, id DESC LIMIT 8";
        $recent = $pdo->query($recentSql)->fetchAll(PDO::FETCH_ASSOC);

        try {
            $quickStats['posts'] = (int)($pdo->query("SELECT COUNT(*) FROM news")->fetchColumn() ?: 0);
            $quickStats['users'] = (int)($pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() ?: 0);
            if ($hasComments) {
                $quickStats['comments'] = (int)($pdo->query("SELECT COUNT(*) FROM news_comments")->fetchColumn() ?: 0);
            }
        } catch (Throwable $e) {}
    } catch (Throwable $e) {
        $pageError = $e->getMessage();
    }
}

require_once dirname(__DIR__) . '/layout/header.php';
?>
<div class="container-fluid py-4" dir="rtl">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
      <h1 class="h3 mb-1">غرفة الأخبار</h1>
      <div class="text-muted">لوحة تحريرية سريعة لقياس النشر، الجدولة، والأخبار الأكثر قراءة.</div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-primary" href="<?php echo h(base_url()); ?>/admin/news/create.php">إضافة خبر</a>
      <a class="btn btn-outline-secondary" href="<?php echo h(base_url()); ?>/admin/news/index.php">إدارة الأخبار</a>
    </div>
  </div>

  <?php if (!empty($pageError)): ?>
    <div class="alert alert-warning">تعذر تحميل بعض المؤشرات: <?php echo h($pageError); ?></div>
  <?php endif; ?>

  <div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-2"><div class="card shadow-sm border-0"><div class="card-body"><div class="text-muted small">منشور</div><div class="display-6 fw-bold"><?php echo (int)$stats['published']; ?></div></div></div></div>
    <div class="col-md-6 col-xl-2"><div class="card shadow-sm border-0"><div class="card-body"><div class="text-muted small">مسودات</div><div class="display-6 fw-bold"><?php echo (int)$stats['drafts']; ?></div></div></div></div>
    <div class="col-md-6 col-xl-2"><div class="card shadow-sm border-0"><div class="card-body"><div class="text-muted small">مجدول</div><div class="display-6 fw-bold"><?php echo (int)$stats['scheduled']; ?></div></div></div></div>
    <div class="col-md-6 col-xl-2"><div class="card shadow-sm border-0"><div class="card-body"><div class="text-muted small">عاجل</div><div class="display-6 fw-bold"><?php echo (int)$stats['breaking']; ?></div></div></div></div>
    <div class="col-md-6 col-xl-2"><div class="card shadow-sm border-0"><div class="card-body"><div class="text-muted small">مشاهدات 7 أيام</div><div class="display-6 fw-bold"><?php echo (int)$stats['views_7d']; ?></div></div></div></div>
    <div class="col-md-6 col-xl-2"><div class="card shadow-sm border-0"><div class="card-body"><div class="text-muted small">تعليقات معلّقة</div><div class="display-6 fw-bold"><?php echo (int)$stats['comments_pending']; ?></div></div></div></div>
  </div>

  <div class="row g-4">
    <div class="col-lg-7">
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white"><strong>الأكثر قراءة</strong></div>
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead><tr><th>                                                                     
            <tbody>
              <?php if (empty($mostRead)): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">لا توجد بيانات بعد.</td></tr>
              <?php else: ?>
                <?php foreach ($mostRead as $i => $row): ?>
                  <tr>
                    <td><?php echo (int)($i + 1); ?></td>
                    <td><a href="<?php echo h(base_url()); ?>/admin/news/edit.php?id=<?php echo (int)$row['id']; ?>"><?php echo h($row['title']); ?></a></td>
                    <td><?php echo (int)($row['views'] ?? 0); ?></td>
                    <td><?php echo h((string)($row['published_on'] ?? '')); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card shadow-sm border-0">
        <div class="card-header bg-white"><strong>آخر النشرات والتحرير</strong></div>
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead><tr><th>العنوان</th><th>الحالة</th><th>التاريخ</th></tr></thead>
            <tbody>
            <?php if (empty($recent)): ?>
              <tr><td colspan="3" class="text-center text-muted py-4">لا توجد أخبار حديثة.</td></tr>
            <?php else: ?>
              <?php foreach ($recent as $row): ?>
                <tr>
                  <td><a href="<?php echo h(base_url()); ?>/admin/news/edit.php?id=<?php echo (int)$row['id']; ?>"><?php echo h($row['title']); ?></a></td>
                  <td><span class="badge bg-light text-dark"><?php echo h($row['status']); ?></span></td>
                  <td><?php echo h((string)($row['dt'] ?? '')); ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white"><strong>جدولة النشر القادمة</strong></div>
        <div class="list-group list-group-flush">
          <?php if (empty($scheduled)): ?>
            <div class="list-group-item text-muted py-4">لا توجد مواد مجدولة حاليًا.</div>
          <?php else: ?>
            <?php foreach ($scheduled as $row): ?>
              <a class="list-group-item list-group-item-action" href="<?php echo h(base_url()); ?>/admin/news/edit.php?id=<?php echo (int)$row['id']; ?>">
                <div class="fw-semibold"><?php echo h($row['title']); ?></div>
                <div class="small text-muted">ينشر في: <?php echo h((string)($row['publish_at'] ?? '')); ?></div>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <div class="card shadow-sm border-0">
        <div class="card-header bg-white"><strong>أقوى الأقسام</strong></div>
        <div class="list-group list-group-flush">
          <?php if (empty($categoryStats)): ?>
            <div class="list-group-item text-muted py-4">لا توجد أقسام متاحة.</div>
          <?php else: ?>
            <?php foreach ($categoryStats as $row): ?>
              <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                  <div class="fw-semibold"><?php echo h($row['name']); ?></div>
                  <div class="small text-muted">/<?php echo h($row['slug']); ?></div>
                </div>
                <span class="badge bg-success-subtle text-success rounded-pill"><?php echo (int)($row['total_news'] ?? 0); ?></span>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require_once dirname(__DIR__) . '/layout/footer.php'; ?>
