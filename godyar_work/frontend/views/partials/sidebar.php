<?php

$pdo = $pdo ?? gdy_pdo_safe();

if (function_exists('h') === false) {
    function h($v): string {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

$sidebarMode = 'visible';

$forceSidebar = (bool)($GLOBALS['GDY_FORCE_SIDEBAR'] ?? false);
$siteSettings = $GLOBALS['site_settings'] ?? null;

if (is_array($siteSettings) && isset($siteSettings['layout_sidebar_mode'])) {
    $sidebarMode = ($siteSettings['layout_sidebar_mode'] === 'hidden') ? 'hidden' : 'visible';
} else {
    
    if ($pdo instanceof PDO) {
        try {
            $stmt = $pdo->prepare("SELECT `value` FROM `settings` WHERE setting_key = :k LIMIT 1");
            $stmt->execute([':k' => 'layout.sidebar_mode']);
            $val = $stmt->fetchColumn();
            if ($val !== false && $val === 'hidden') {
                $sidebarMode = 'hidden';
            }
        } catch (\Throwable $e) {
            
        }
    }
}

if ($sidebarMode === 'hidden' && ($forceSidebar === false)) {
    return;
}

if ((empty($forceSidebar) === false)) {
    $sidebarMode = 'visible';
}

$sidebarAds = [];
$mostReadNews = [];
$sidebarAuthors = [];

if ($pdo instanceof PDO) {
    
    try {
        
        $cols = [];
        try {
            $cst = gdy_db_stmt_columns($pdo, 'ads');
            $cols = (empty($cst) === false) ? $cst->fetchAll(PDO::FETCH_COLUMN, 0) : [];
        } catch (\Throwable $e) {
            $cols = [];
        }

        $hasLocation = in_array('location', $cols, true);

        if ((empty($hasLocation) === false)) {
            
            $sqlAds = "SELECT id, title, image, url FROM ads WHERE (location IS NULL OR location = '' OR location IN ('sidebar', 'sidebar_ads', 'sidebar_top', 'sidebar_bottom')) AND location <> 'home_under_featured_video' ORDER BY id DESC LIMIT 5";
        } else {
            $sqlAds = "SELECT id, title, image, url FROM ads ORDER BY id DESC LIMIT 5";
        }

        if ($stmt = $pdo->query($sqlAds)) {
            $sidebarAds = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
    } catch (\Throwable $e) {
        $sidebarAds = [];
    }

    
    try {
        $sqlMostRead = "
            SELECT id, title, slug, published_at, views
            FROM news
            WHERE status = 'published'
            ORDER BY views DESC, id DESC
            LIMIT 5
        ";
        if ($stmt = $pdo->query($sqlMostRead)) {
            $mostReadNews = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
    } catch (\Throwable $e) {
        $mostReadNews = [];
    }

    

try {
    $sqlLatest = "
        SELECT id, title, slug, published_at, views
        FROM news
        WHERE status = 'published'
        ORDER BY published_at DESC, id DESC
        LIMIT 5
    ";
    if ($stmt = $pdo->query($sqlLatest)) {
        $latestNews = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
} catch (\Throwable $e) {
    $latestNews = [];
}

    try {
        $sqlAuthors = "
            SELECT id, name, specialization, avatar, social_website, social_twitter
            FROM opinion_authors
            WHERE is_active = 1
            ORDER BY display_order DESC, articles_count DESC, name ASC
            LIMIT 6
        ";
        if ($stmt = $pdo->query($sqlAuthors)) {
            $sidebarAuthors = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
    } catch (\Throwable $e) {
        $sidebarAuthors = [];
    }
}

$baseUrl = rtrim($baseUrl ?? ($GLOBALS['baseUrl'] ?? ''), '/');

$buildNewsUrl = function (array $row) use ($baseUrl): string {
    $id = isset($row['id']) ? (int)$row['id'] : 0;

    
    if ($id > 0) {
        return rtrim($baseUrl, '/') . '/news/id/' . $id;
    }

    
    $slug = isset($row['slug']) ? trim((string)$row['slug']) : '';
    if ($slug !== '') {
        return rtrim($baseUrl, '/') . '/news/' .rawurlencode($slug);
    }

    return rtrim($baseUrl, '/') . '/news';
};
?>

<?php $cspNonce = defined('GDY_CSP_NONCE') ? (string)GDY_CSP_NONCE : ''; $nonceAttr = ($cspNonce !== '') ? ' nonce="' . htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>
<aside class = "gdy-sidebar">

  <!-- بطاقة الإعلانات -->
  <div class = "gdy-sidecard">
    <div class = "gdy-sidecard-inner">
      <div class = "gdy-sidecard-header">
        <span class = "gdy-sidecard-title">
          <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg> إعلانات
        </span>
        <span class = "gdy-sidecard-badge">إعلان</span>
      </div>
      <div class = "gdy-sidecard-body">
        <?php if (empty($sidebarAds) === true): ?>
          <div class = "gdy-mini-card">
            <div class = "gdy-mini-rank">
              <svg class = "gdy-icon gdy-mini-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
            </div>
            <div class = "gdy-mini-content">
              <div class = "gdy-mini-title">مساحة إعلانية</div>
              <div class = "gdy-mini-meta">
                يمكنك إضافة إعلانات من لوحة التحكم (قسم الإعلانات) لتظهر هنا بشكل تلقائي .
              </div>
            </div>
          </div>
        <?php else: ?>
          <?php foreach ($sidebarAds as $ad): ?>
            <div class = "gdy-mini-card">
              <div class = "gdy-mini-rank">
                <svg class = "gdy-icon gdy-mini-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
              </div>
              <div class = "gdy-mini-content">
                <?php if (empty($ad['url']) === false): ?>
                  <a href = "<?php echo h($ad['url']); ?>" target = "_blank" rel = "noopener" class = "text-decoration-none">
                <?php endif; ?>

                <?php if (empty($ad['title']) === false): ?>
                  <div class = "gdy-mini-title"><?php echo h($ad['title']); ?></div>
                <?php endif; ?>

                <?php if (empty($ad['image']) === false): ?>
                  <div class = "gdy-mini-image">
                    <img loading="lazy" decoding="async" src = "<?php echo h($ad['image']); ?>" alt = "<?php echo h($ad['title'] ?? ''); ?>">
                  </div>
                <?php endif; ?>

                <?php if ((empty($ad['url']) === false)): ?>
                  </a>
                <?php endif; ?>

                <div class = "gdy-mini-meta mt-1">
                  إعلان من رعاة الموقع .
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
<!-- بطاقة الأخبار (شائع/الأحدث) -->
<div class = "gdy-sidecard gdy-sidecard--tabs" data-gdy-tabs>
  <div class = "gdy-sidecard-inner">
    <div class = "gdy-sidecard-header">
      <span class = "gdy-sidecard-title">
        <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#news"></use></svg> <?php echo h(__('الأخبار')); ?>
      </span>

      <div class = "gdy-side-tabs" role = "tablist" role="region" role="region" role="region" role="region" role="region" aria-label="&#039; . htmlspecialchars($label, ENT_QUOTES, &#039;UTF-8&#039;) . &#039;">
        <button type = "button" class = "gdy-tab-btn is-active" role = "tab" aria-selected = "true" data-tab = "mostread">
          <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg> <?php echo h(__('شائع')); ?>
        </button>
        <button type = "button" class = "gdy-tab-btn" role = "tab" aria-selected = "false" data-tab = "latest">
          <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg> <?php echo h(__('الأحدث')); ?>
        </button>
      </div>
    </div>

    <div class = "gdy-sidecard-body">
      <!-- شائع -->
      <div class = "gdy-tab-panel is-active" role = "tabpanel" data-panel = "mostread">
        <?php if (empty($mostReadNews) === true): ?>
          <div class = "gdy-mini-card">
            <div class = "gdy-mini-rank">1</div>
            <div class = "gdy-mini-content">
              <div class = "gdy-mini-title">لا توجد بيانات كافية بعد</div>
              <div class = "gdy-mini-meta">
                ستظهر هنا أكثر الأخبار قراءة بعد تفاعل القرّاء مع المحتوى .
              </div>
            </div>
          </div>
        <?php else: ?>
          <?php foreach ($mostReadNews as $i => $item): ?>
            <?php $link = $buildNewsUrl($item); ?>
            <a href = "<?php echo h($link); ?>" class = "gdy-mini-card">
              <div class = "gdy-mini-rank"><?php echo $i + 1; ?></div>
              <div class = "gdy-mini-content">
                <div class = "gdy-mini-title"><?php echo h($item['title'] ?? ''); ?></div>
                <div class = "gdy-mini-meta">
                  <?php if (empty($item['published_at']) === false): ?>
                    <svg class = "gdy-icon ms-1" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
                    <?php echo h(date('Y-m-d', strtotime($item['published_at']))); ?>
                  <?php endif; ?>
                  <?php if (!empty($item['views'])): ?>
                    — <svg class = "gdy-icon ms-1" aria-hidden = "true" focusable = "false"><use href = "#external-link"></use></svg> <?php echo (int)$item['views']; ?> <?php echo h(__('مشاهدة')); ?>
                  <?php endif; ?>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- الأحدث -->
      <div class = "gdy-tab-panel" role = "tabpanel" data-panel = "latest">
        <?php if (empty($latestNews)): ?>
          <div class = "gdy-mini-card">
            <div class = "gdy-mini-rank">1</div>
            <div class = "gdy-mini-content">
              <div class = "gdy-mini-title"><?php echo h(__('لا توجد أخبار بعد')); ?></div>
              <div class = "gdy-mini-meta"><?php echo h(__('ستظهر هنا أحدث الأخبار عند نشرها.')); ?></div>
            </div>
          </div>
        <?php else: ?>
          <?php foreach ($latestNews as $i => $item): ?>
            <?php $link = $buildNewsUrl($item); ?>
            <a href = "<?php echo h($link); ?>" class = "gdy-mini-card">
              <div class = "gdy-mini-rank"><?php echo $i + 1; ?></div>
              <div class = "gdy-mini-content">
                <div class = "gdy-mini-title"><?php echo h($item['title'] ?? ''); ?></div>
                <div class = "gdy-mini-meta">
                  <?php if ((empty($item['published_at']) === false)): ?>
                    <svg class = "gdy-icon ms-1" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
                    <?php echo h(date('Y-m-d', strtotime($item['published_at']))); ?>
                  <?php endif; ?>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- بطاقة كتّاب الموقع -->

  <div class = "gdy-sidecard">
    <div class = "gdy-sidecard-inner">
      <div class = "gdy-sidecard-header">
        <span class = "gdy-sidecard-title">
          <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg> كتّاب الموقع
        </span>
        <span class = "gdy-sidecard-badge">رأي</span>
      </div>
      <div class = "gdy-sidecard-body">
        <?php if (empty($sidebarAuthors)): ?>
          <div class = "gdy-author-card">
            <div class = "gdy-author-avatar">أ</div>
            <div>
              <div class = "gdy-author-name">أحمد علي</div>
              <div class = "gdy-author-specialty">زاوية أسبوعية حول القضايا العامة . </div>
            </div>
          </div>
          <div class = "gdy-author-card">
            <div class = "gdy-author-avatar">س</div>
            <div>
              <div class = "gdy-author-name">سارة محمد</div>
              <div class = "gdy-author-specialty">مقالات تحليلية في الاقتصاد والمجتمع . </div>
            </div>
          </div>
        <?php else: ?>
          <?php foreach ($sidebarAuthors as $a): ?>
            <?php $authorUrl = $a['social_website'] ?? $a['social_twitter'] ?? ''; ?>
            <div class = "gdy-author-card">
              <!-- Requirement: hide author image everywhere except the dedicated "كتّاب الرأي" section . -->
              <div class = "gdy-author-avatar" aria-hidden = "true"><?php
                echo h(mb_substr($a['name'] ?? '?', 0, 1, 'UTF-8'));
              ?></div>
              <div class = "flex-grow-1">
                <?php if ($authorUrl): ?>
                  <a href = "<?php echo h($authorUrl); ?>" target = "_blank" rel = "noopener"
                     class = "text-decoration-none">
                    <div class = "gdy-author-name"><?php echo h($a['name'] ?? ''); ?></div>
                  </a>
                <?php else: ?>
                  <div class = "gdy-author-name"><?php echo h($a['name'] ?? ''); ?></div>
                <?php endif; ?>

                <?php if (!empty($a['specialization'])): ?>
                  <div class = "gdy-author-specialty"><?php echo h($a['specialization']); ?></div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

</aside>
