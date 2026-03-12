<?php
require_once __DIR__ . '/../_admin_guard.php';
require_once __DIR__ . '/../../includes/bootstrap.php';
$currentPage = 'analytics';
$pageTitle = 'لوحة التحليلات';
if (!function_exists('h')) { function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } }
$__base = defined('GODYAR_BASE_URL') ? rtrim((string)GODYAR_BASE_URL, '/') : '';
$pageHead = '<link rel="stylesheet" href="' . $__base . '/admin/assets/css/admin-analytics.css?v=' . (string)@filemtime(__DIR__ . '/../assets/css/admin-analytics.css') . '">';
$pdo = gdy_pdo_safe();
$kpis = ['news'=>0,'published'=>0,'categories'=>0,'today_views'=>0];
$topNews = [];
$topSearches = [];
$zeroResultSearches = [];
$latestSearches = [];
$cards = [
  ['title' => 'الإحصائيات', 'desc' => 'نظرة عامة على التفاعل', 'href' => 'statistics.php'],
  ['title' => 'التقارير', 'desc' => 'تقارير أداء المحتوى', 'href' => 'reports.php'],
  ['title' => 'خريطة النشاط', 'desc' => 'حركة الزيارة حسب الوقت', 'href' => 'heatmap.php'],
];
if ($pdo instanceof PDO) {
  try { $kpis['news'] = (int)$pdo->query("SELECT COUNT(*) FROM news")->fetchColumn(); } catch (Throwable $e) {}
  try { $kpis['published'] = (int)$pdo->query("SELECT COUNT(*) FROM news WHERE status='published'")->fetchColumn(); } catch (Throwable $e) {}
  try { $kpis['categories'] = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn(); } catch (Throwable $e) {}
  try { $kpis['today_views'] = (int)$pdo->query("SELECT COUNT(*) FROM visits WHERE DATE(created_at)=CURRENT_DATE")->fetchColumn(); } catch (Throwable $e) {}
  try {
    $sql = "SELECT n.id, n.title, COUNT(v.id) AS views
            FROM visits v
            JOIN news n ON n.id = v.news_id
            GROUP BY n.id, n.title
            ORDER BY views DESC
            LIMIT 5";
    $topNews = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
  } catch (Throwable $e) {}
}
$analyticsFile = __DIR__ . '/../../storage/v4/search_analytics.json';
if (is_file($analyticsFile)) {
  try {
    $raw = json_decode((string)file_get_contents($analyticsFile), true);
    if (is_array($raw)) {
      $counts = [];
      $zeroCounts = [];
      $latestSearches = array_reverse(array_slice($raw, -8));
      foreach ($raw as $row) {
        $q = trim((string)($row['query'] ?? ''));
        if ($q === '') continue;
        $counts[$q] = ($counts[$q] ?? 0) + 1;
        $results = (int)($row['results'] ?? 0);
        if ($results <= 0) $zeroCounts[$q] = ($zeroCounts[$q] ?? 0) + 1;
      }
      arsort($counts);
      foreach (array_slice($counts, 0, 5, true) as $q => $c) {
        $topSearches[] = ['query' => $q, 'count' => $c];
      }
      arsort($zeroCounts);
      foreach (array_slice($zeroCounts, 0, 5, true) as $q => $c) {
        $zeroResultSearches[] = ['query' => $q, 'count' => $c];
      }
    }
  } catch (Throwable $e) {}
}
include __DIR__ . '/../layout/header.php';
include __DIR__ . '/../layout/sidebar.php';
?>
<div class="admin-content gdy-analytics-page">
  <div class="container-fluid py-4">
    <div class="gdy-hero">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h1 class="h4 text-white mb-1">لوحة التحليلات</h1>
          <div class="text-secondary small">ملخص سريع للأخبار والزيارات وما يبحث عنه المستخدمون.</div>
        </div>
        <div class="text-secondary small">آخر تحديث: <?php echo h(date('Y-m-d H:i')); ?></div>
      </div>
    </div>

    <div class="gdy-kpis">
      <div class="gdy-kpi"><div class="gdy-kpi-label">إجمالي الأخبار</div><div class="gdy-kpi-value"><?php echo number_format($kpis['news']); ?></div></div>
      <div class="gdy-kpi"><div class="gdy-kpi-label">الأخبار المنشورة</div><div class="gdy-kpi-value"><?php echo number_format($kpis['published']); ?></div></div>
      <div class="gdy-kpi"><div class="gdy-kpi-label">التصنيفات</div><div class="gdy-kpi-value"><?php echo number_format($kpis['categories']); ?></div></div>
      <div class="gdy-kpi"><div class="gdy-kpi-label">زيارات اليوم</div><div class="gdy-kpi-value"><?php echo number_format($kpis['today_views']); ?></div></div>
    </div>

    <div class="gdy-grid gdy-grid-3">
      <div class="gdy-panel">
        <div class="gdy-panel-hd"><h3 class="h6 m-0 text-white">أكثر الأخبار مشاهدة</h3><a class="small text-info text-decoration-none" href="../news/index.php">إدارة المقالات</a></div>
        <div class="gdy-panel-bd">
          <div class="gdy-mini-list">
            <?php if (!$topNews): ?>
              <div class="text-secondary small">لا توجد بيانات زيارات كافية بعد.</div>
            <?php else: foreach ($topNews as $row): ?>
              <div class="gdy-mini-item"><strong><?php echo h($row['title'] ?? ''); ?></strong><span><?php echo number_format((int)($row['views'] ?? 0)); ?> مشاهدة</span></div>
            <?php endforeach; endif; ?>
          </div>
        </div>
      </div>
      <div class="gdy-panel">
        <div class="gdy-panel-hd"><h3 class="h6 m-0 text-white">أكثر عمليات البحث</h3><span class="small text-secondary">آخر السجلات</span></div>
        <div class="gdy-panel-bd">
          <div class="gdy-mini-list">
            <?php if (!$topSearches): ?>
              <div class="text-secondary small">لا توجد سجلات بحث متاحة بعد.</div>
            <?php else: foreach ($topSearches as $row): ?>
              <div class="gdy-mini-item"><strong><?php echo h($row['query'] ?? ''); ?></strong><span><?php echo number_format((int)($row['count'] ?? 0)); ?> مرة</span></div>
            <?php endforeach; endif; ?>
          </div>
        </div>
      </div>
      <div class="gdy-panel">
        <div class="gdy-panel-hd"><h3 class="h6 m-0 text-white">بحث بلا نتائج</h3><span class="small text-secondary">فرص تحسين المحتوى</span></div>
        <div class="gdy-panel-bd">
          <div class="gdy-mini-list">
            <?php if (!$zeroResultSearches): ?>
              <div class="text-secondary small">كل عمليات البحث الحالية تعود بنتائج أو لا توجد بيانات كافية.</div>
            <?php else: foreach ($zeroResultSearches as $row): ?>
              <div class="gdy-mini-item"><strong><?php echo h($row['query'] ?? ''); ?></strong><span><?php echo number_format((int)($row['count'] ?? 0)); ?> مرة</span></div>
            <?php endforeach; endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="gdy-grid mt-3">
      <div class="gdy-panel">
        <div class="gdy-panel-hd"><h3 class="h6 m-0 text-white">آخر عمليات البحث</h3><span class="small text-secondary">أحدث سلوك فعلي للمستخدمين</span></div>
        <div class="gdy-panel-bd">
          <div class="gdy-mini-list">
            <?php if (!$latestSearches): ?>
              <div class="text-secondary small">لا توجد عمليات بحث حديثة بعد.</div>
            <?php else: foreach ($latestSearches as $row): ?>
              <div class="gdy-mini-item"><strong><?php echo h((string)($row['query'] ?? '')); ?></strong><span><?php echo h((string)($row['created_at'] ?? ($row['ts'] ?? 'الآن'))); ?><?php if (isset($row['results'])): ?> • <?php echo number_format((int)$row['results']); ?> نتيجة<?php endif; ?></span></div>
            <?php endforeach; endif; ?>
          </div>
        </div>
      </div>
      <div class="gdy-panel">
        <div class="gdy-panel-hd"><h3 class="h6 m-0 text-white">اختصارات التحليلات</h3></div>
        <div class="gdy-panel-bd">
          <div class="gdy-quick-links">
            <?php foreach ($cards as $card): ?>
              <a class="gdy-quick-link" href="<?php echo h($card['href']); ?>"><strong class="d-block mb-1"><?php echo h($card['title']); ?></strong><span><?php echo h($card['desc']); ?></span></a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
