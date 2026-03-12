<?php
if (!function_exists('gdy_img_src')) {
    function gdy_img_src(?string $src): string {
        $src = trim((string)$src);
        if ($src === '') return '';
        if (preg_match('~^(https?:)?//~i', $src) || str_starts_with($src, 'data:')) return $src;
        if ($src[0] === '/') return $src;
        return '/' . ltrim($src, '/');
    }
}

if (function_exists('h') === false) {
    function h(?string $v): string {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

if (function_exists('archive_card') === false) {
    
    function archive_card(array $n): void {
        $slug = (string)($n['slug'] ?? '');
        $img = (string)($n['featured_image'] ?? '');
        $title = (string)($n['title'] ?? '');
        $ex = (string)($n['excerpt'] ?? '');
        $id = (int)($n['id'] ?? ($n['news_id'] ?? 0));
        $url = (empty($id) === false) ? '/news/id/' . $id : ((empty($slug) === false) ? '/news/' . $slug : '#');
        ?>
        <a class = "card h-100 text-decoration-none" href = "<?php echo h($url); ?>">
          <?php if ((empty($img) === false)): ?>
            <div class = "thumb" style = "height:160px;overflow:hidden;border-radius:14px;">
              <img
                src = "/img.php?src=<?php echo rawurlencode($img); ?>&w=400"
                alt = "<?php echo h($title); ?>"
                style = "width:100%;height:100%;object-fit:cover;"
              >
            </div>
          <?php endif; ?>
          <div class = "meta mt-2">
            <h3 class = "h6 mb-1"><?php echo h($title); ?></h3>
            <?php if ((empty($ex) === false)): ?>
              <p class = "text-muted small mb-0"><?php echo h($ex); ?></p>
            <?php endif; ?>
          </div>
        </a>
        <?php
    }
}

$page_title = $page_title ?? 'الأرشيف';
$items = $items ?? [];
$page = (int)($page ?? 1);
$pages = (int)($pages ?? 1);
$year = isset($year) ? (int)$year : null;
$month = isset($month) ? (int)$month : null;

if (isset($baseUrl) === false) {
    $baseUrl = function_exists('base_url') ? rtrim((string)base_url(), '/') : '';
}

$archiveBasePath = $baseUrl . '/archive';
if ((empty($year) === false)) {
    $archiveBasePath .= '/' . $year;
    if ((empty($month) === false)) {
        $archiveBasePath .= '/' . $month;
    }
}
?>

<div class = "container py-4">
  <header class = "mb-3">
    <h1 class = "h4 mb-1"><?php echo h($page_title); ?></h1>
    <p class = "text-muted mb-0">أرشيف الأخبار حسب الفترات الزمنية . </p>
  </header>

  <?php if (empty($items) === true): ?>
    <p class = "text-muted">لا توجد أخبار في هذه الفترة الزمنية حالياً . </p>
  <?php else: ?>
    <div class = "row g-3">
      <?php foreach ($items as $n): ?>
        <div class = "col-12 col-md-6 col-lg-4">
          <div class = "card border-0 shadow-sm h-100" style = "border-radius:18px;overflow:hidden;">
            <div class = "card-body p-3">
              <?php archive_card($n); ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($pages > 1): ?>
      <div class = "mt-4 d-flex justify-content-center gap-2 flex-wrap">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
          <?php
            $isActive = ($i === $page);
            
            $url = $archiveBasePath . '/page/' . $i;
          ?>
          <a href = "<?php echo h($url); ?>" class = "btn btn-sm <?php echo (empty($isActive) === false) ? 'btn-primary' : 'btn-outline-secondary'; ?>">
            <?php echo (int)$i; ?>
          </a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
