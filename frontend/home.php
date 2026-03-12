<?php
if (!function_exists("gdy_pdo_safe")) {
    require_once __DIR__ . "/../includes/bootstrap.php";
}
if (!function_exists("__")) {
    function __() { return ; }
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    gdy_session_start();
}

if (class_exists('Godyar\\Support\\Security')) {
    try {
        \App\Support\Security::headers();
    } catch (Throwable $e) {
        
    }
}

$__gdy_nonce = '';
if (class_exists('\\App\\Support\\Security') && method_exists('\\App\\Support\\Security', 'nonce')) {
    try {
        $__gdy_nonce = (string) \App\Support\Security::nonce();
    } catch (Throwable $e) {
        $__gdy_nonce = '';
    }
}
$__gdy_nonce_attr = $__gdy_nonce ? ' nonce="' . htmlspecialchars($__gdy_nonce, ENT_QUOTES, 'UTF-8') . '"' : '';

$pdo = gdy_pdo_safe();

if (!function_exists('h')) {
    function h($v) {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

$siteSettings = [];
if (function_exists('settings_get')) {
    $siteSettings['layout.sidebar_mode'] = settings_get('layout.sidebar_mode', 'visible');
} elseif (isset($GLOBALS['site_settings']) && is_array($GLOBALS['site_settings'])) {
    $siteSettings = $GLOBALS['site_settings'];
}
$sidebarMode = $siteSettings['layout.sidebar_mode'] ?? 'visible';
$sidebarHidden = ($sidebarMode === 'hidden');

if (isset($baseUrl) && $baseUrl !== '') {
    $baseUrl = rtrim($baseUrl, '/');
} elseif (function_exists('base_url')) {
    $baseUrl = rtrim(base_url(), '/');
} else {
    $baseUrl = '';
}

$_gdy_baseUrl = $baseUrl;
$GLOBALS['_gdy_baseUrl'] = $_gdy_baseUrl;

$period = isset($_GET['period']) ? trim((string)$_GET['period']) : 'all';
if (!in_array($period, ['all', 'today', 'week', 'month'], true)) {
    $period = 'all';
}

$buildNewsUrl = function (array $row) use ($baseUrl): string {
    $lang = function_exists('gdy_request_lang') ? gdy_request_lang('ar') : 'ar';
    if (function_exists('gdy_route_news_url')) {
        return gdy_route_news_url($row, $lang);
    }
    $id = isset($row['id']) ? (int)$row['id'] : 0;
    $slug = isset($row['slug']) ? trim((string)$row['slug']) : '';
    $prefix = rtrim($baseUrl, '/');
    $prefix .= '/' . $lang;
    if ($id > 0) {
        return $prefix . '/news/id/' . $id;
    }
    if ($slug !== '') {
        return $prefix . '/news/' . rawurlencode($slug);
    }
    return $prefix . '/news';
};

$buildCategoryUrl = function (array $row) use ($baseUrl): string {
    $lang = function_exists('gdy_request_lang') ? gdy_request_lang('ar') : 'ar';
    if (function_exists('gdy_route_category_url')) {
        return gdy_route_category_url($row, $lang);
    }
    $id = isset($row['id']) ? (int)$row['id'] : 0;
    $slug = isset($row['slug']) ? trim((string)$row['slug']) : '';
    $prefix = rtrim($baseUrl, '/');
    $prefix .= '/' . $lang;
    if ($slug !== '') {
        return $prefix . '/category/' . rawurlencode($slug);
    }
    if ($id > 0) {
        return $prefix . '/category/id/' . $id;
    }
    return $prefix . '/category';
};

$buildImageUrl = function ($raw) use ($baseUrl): string {
    $img = trim((string)$raw);
    if ($img === '') {
        return rtrim($baseUrl, '/') . '/assets/images/placeholder-thumb.jpg';
    }
    if (function_exists('gdy_front_abs_url')) {
        return gdy_front_abs_url($baseUrl, $img);
    }
    if (preg_match('~^(https?:)?//~i', $img) || str_starts_with($img, 'data:')) {
        return $img;
    }
    return rtrim($baseUrl, '/') . '/' . ltrim($img, '/');
};

$buildOpinionAuthorUrl = function (array $row) use ($baseUrl): string {
    $id = isset($row['id']) ? (int)$row['id'] : 0;
    $slug = isset($row['slug']) ? trim((string)$row['slug']) : '';

    $prefix = rtrim($baseUrl, '/');
    if ($prefix !== '') {
        $prefix .= '/';
    }

    if ($slug !== '') {
        return $prefix . 'opinion_author.php?slug=' .rawurlencode($slug);
    }
    if ($id > 0) {
        return $prefix . 'opinion_author.php?id=' . $id;
    }
    return $prefix . 'opinion_author.php';
};

if (!function_exists('build_image_url')) {
    function build_image_url(string $baseUrl, ?string $path): ?string
    {
        $path = trim((string)$path);
        if ($path === '') {
            return null;
        }

        if (preg_match('~^https?://~i', $path)) {
            return $path;
        }

        if ($path[0] === '/') {
            return rtrim($baseUrl, '/') . $path;
        }

        return rtrim($baseUrl, '/') . '/' .ltrim($path, '/');
    }
}

$categories = [];
if ($pdo instanceof PDO) {
    try {
        if (function_exists('gdy_ensure_opinion_authors_table')) { gdy_ensure_opinion_authors_table($pdo); }
        $stmt = $pdo->query("SELECT id, name, slug FROM categories ORDER BY id ASC");
        $categories = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (\Throwable $e) {
        $categories = [];
    }
}

$blockConfig = [
    'general' => [
        'title' => 'أخبار عامة',
        'match_name' => ['أخبار', 'اخبار', 'عام'],
        'match_slug' => ['news', 'general'],
    ],
    'politics' => [
        'title' => 'سياسة',
        'match_name' => ['سياسة'],
        'match_slug' => ['politic'],
    ],
    'economy' => [
        'title' => 'اقتصاد',
        'match_name' => ['اقتصاد', 'إقتصاد'],
        'match_slug' => ['econom', 'business'],
    ],
    'variety' => [
        'title' => 'منوعات',
        'match_name' => ['منوعات'],
        'match_slug' => ['variety', 'life', 'life-style'],
    ],
    'sports' => [
        'title' => 'رياضة',
        'match_name' => ['رياضة', 'رياضه'],
        'match_slug' => ['sport'],
    ],
    'opinion' => [
        'title' => 'الرأي',
        'match_name' => ['الرأي', 'راي'],
        'match_slug' => ['opinion'],
    ],
    'video' => [
        'title' => 'فيديو',
        'match_name' => ['فيديو'],
        'match_slug' => ['video'],
    ],
    'infograph' => [
        'title' => 'إنفوجراف',
        'match_name' => ['إنفوجراف', 'انفوجراف'],
        'match_slug' => ['infograph', 'infographic'],
    ],
];

$blocks = [];
foreach ($blockConfig as $key => $cfg) {
    $blocks[$key] = [
        'title' => $cfg['title'],
        'category' => null,
        'news' => [],
    ];
}

foreach ($categories as $catRow) {
    $name = (string)($catRow['name'] ?? '');
    $slug = strtolower((string)($catRow['slug'] ?? ''));

    foreach ($blockConfig as $key => $cfg) {
        if ($blocks[$key]['category'] !== null) continue;

        $matched = false;
        foreach ($cfg['match_name'] as $pat) {
            $pat = (string)$pat;
            if ($pat === '') continue;
            if (mb_strpos($name, $pat, 0, 'UTF-8') !== false) {
                $matched = true;
                break;
            }
        }

        if (!$matched && $slug !== '') {
            foreach ($cfg['match_slug'] as $pat) {
                $pat = strtolower((string)$pat);
                if ($pat === '') continue;
                if (strpos($slug, $pat) !== false) {
                    $matched = true;
                    break;
                }
            }
        }

        if ($matched) {
            $blocks[$key]['category'] = $catRow;
        }
    }
}

$sliderSlides = [];
if ($pdo instanceof PDO) {
    try {
        $sliderTable = 'slider';
        if (function_exists('gdy_db_table_exists')) {
            if (!gdy_db_table_exists($pdo, 'slider') && gdy_db_table_exists($pdo, 'sliders')) {
                $sliderTable = 'sliders';
            }
        }
        $qtSlider = function_exists('gdy_db_quote_ident') ? gdy_db_quote_ident($sliderTable) : $sliderTable;
        $stmt = $pdo->query(
            "SELECT id, title, subtitle, image_path, link_url, is_active, sort_order, created_at\n".
            "FROM {$qtSlider}\n".
            "WHERE is_active = 1\n".
            "ORDER BY sort_order ASC, id DESC\n".
            "LIMIT 6"
        );
        $sliderSlides = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    } catch (\Throwable $e) {
        $sliderSlides = [];
    }
}

if (!function_exists('gdy_front_abs_url')) {
    function gdy_front_abs_url(string $base, string $path): string {
        $path = trim($path);
        if ($path === '') return '';
        if (preg_match('~^https?://~i', $path)) return $path;
        if (str_starts_with($path, '/')) return rtrim($base, '/') . $path;
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
}

$latestNews = [];
if ($pdo instanceof PDO) {
    try {
        if (function_exists('gdy_ensure_opinion_authors_table')) { gdy_ensure_opinion_authors_table($pdo); }
        
        $excludeOpinionFromLatest = false;
        try {
            $chk = gdy_db_stmt_column_like($pdo, 'news', 'opinion_author_id');
            if ($chk && $chk->fetchColumn() !== false) {
                $excludeOpinionFromLatest = true;
            }
        } catch (\Throwable $e) {
            $excludeOpinionFromLatest = false;
        }

        $dateWhereLatest = '';
        if ($period === 'today') {
            $dateWhereLatest = " AND DATE(created_at) = CURRENT_DATE";
        } elseif ($period === 'week') {
            $dateWhereLatest = " AND created_at >= (CURRENT_DATE-INTERVAL 7 DAY)";
        } elseif ($period === 'month') {
            $dateWhereLatest = " AND created_at >= (CURRENT_DATE-INTERVAL 30 DAY)";
        }

        $opinionWhereLatest = $excludeOpinionFromLatest
            ? " AND (opinion_author_id IS NULL OR opinion_author_id = 0)"
            : '';

        $sql = "
            SELECT *
            FROM news
            WHERE status = 'published'
              AND deleted_at IS NULL
              AND (publish_at IS NULL OR publish_at <= NOW())
              {$dateWhereLatest}
              {$opinionWhereLatest}
            ORDER BY COALESCE(publish_at, published_at, created_at) DESC
            LIMIT 12
        ";
        $stmt = $pdo->query($sql);
        $latestNews = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (\Throwable $e) {
        $latestNews = [];
    }
}

$breakingNews = [];
if ($pdo instanceof PDO) {
    try {
        if (function_exists('gdy_ensure_opinion_authors_table')) { gdy_ensure_opinion_authors_table($pdo); }
        
        $sql = "
            SELECT *
            FROM news
            WHERE status = 'published'
              AND deleted_at IS NULL
              AND (publish_at IS NULL OR publish_at <= NOW())
              AND is_breaking = 1
            ORDER BY COALESCE(publish_at, published_at, created_at) DESC
            LIMIT 10
        ";
        $stmt = $pdo->query($sql);
        $breakingNews = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        
        if (!$breakingNews && $latestNews) {
            $breakingNews = $latestNews;
        }
    } catch (\Throwable $e) {
        
        $breakingNews = $latestNews;
    }
}

$weatherSettings = [
    'api_key' => '',
    'city' => '',
    'country_code' => '',
    'units' => 'metric',
    'is_active' => 0,
    'refresh_minutes' => 30,
];
$weatherLocations = [];

if ($pdo instanceof PDO) {
    try {
        if (function_exists('gdy_ensure_opinion_authors_table')) { gdy_ensure_opinion_authors_table($pdo); }
        
        $hasWeatherSettings = false;
        $chk = gdy_db_stmt_table_exists($pdo, 'weather_settings');
        if ($chk && $chk->fetchColumn() !== false) {
            $hasWeatherSettings = true;
        }

        if ($hasWeatherSettings) {
            $stmt = $pdo->query("SELECT api_key, city, country_code, units, is_active, refresh_minutes FROM weather_settings ORDER BY id ASC LIMIT 1");
            $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
            if (is_array($row) && $row) {
                $weatherSettings = array_merge($weatherSettings, $row);
            }
        }

        
        $hasWeatherLocations = false;
        $chk2 = gdy_db_stmt_table_exists($pdo, 'weather_locations');
        if ($chk2 && $chk2->fetchColumn() !== false) {
            $hasWeatherLocations = true;
        }
        if ($hasWeatherLocations) {
            $stmt = $pdo->query("SELECT id, country_code, city_name FROM weather_locations WHERE is_active = 1 ORDER BY country_name ASC, city_name ASC");
            $weatherLocations = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
        }
    } catch (\Throwable $e) {
        error_log('[home] weather settings load error: ' . $e->getMessage());
        $weatherLocations = [];
    }
}

$featuredVideos = [];
if ($pdo instanceof PDO) {
    try {
        if (function_exists('gdy_ensure_opinion_authors_table')) { gdy_ensure_opinion_authors_table($pdo); }
        $sql = "
            SELECT id, title, video_url, description, is_active, created_at
            FROM featured_videos
            WHERE is_active = 1
            ORDER BY created_at DESC
            LIMIT 6
        ";
        $stmt = $pdo->query($sql);
        $featuredVideos = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (\Throwable $e) {
        $featuredVideos = [];
    }
}

$homeAds = [];
if ($pdo instanceof PDO) {
    try {
        if (function_exists('gdy_ensure_opinion_authors_table')) { gdy_ensure_opinion_authors_table($pdo); }
        
        $cols = [];
        try {
            $cst = gdy_db_stmt_columns($pdo, 'ads');
            $cols = $cst ? $cst->fetchAll(PDO::FETCH_COLUMN, 0) : [];
        } catch (\Throwable $e) {
            $cols = [];
        }

        $hasLocation = in_array('location', $cols, true);

        if ($hasLocation) {
            
            $sql = "SELECT id, title, image, url FROM ads WHERE (location IS NULL OR location = '' OR location IN ('home', 'home_main', 'home_ads')) AND location <> 'home_under_featured_video' ORDER BY id DESC LIMIT 4";
        } else {
            $sql = "SELECT id, title, image, url FROM ads ORDER BY id DESC LIMIT 4";
        }

        $stmt = $pdo->query($sql);
        $homeAds = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (\Throwable $e) {
        $homeAds = [];
    }
}

$opinionAuthors = [];
$opinionAuthorsMap = []; 
$opinionAuthorsLast = [];
$authorImageDefault = rtrim($baseUrl, '/') . '/assets/images/author-placeholder.png';

if ($pdo instanceof PDO) {
    try {
        if (function_exists('gdy_ensure_opinion_authors_table')) { gdy_ensure_opinion_authors_table($pdo); }
	        
	        $stmt = $pdo->query("
	            SELECT id, name, slug, avatar, specialization, page_title
	            FROM opinion_authors
	            WHERE is_active = 1
	              AND TRIM(name) <> 'هيئة التحرير'
	            ORDER BY display_order ASC, id DESC
	            LIMIT 50
	        ");
        $opinionAuthors = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        
        foreach ($opinionAuthors as $aRow) {
            $aid = isset($aRow['id']) ? (int)$aRow['id'] : 0;
            if ($aid > 0) {
                $opinionAuthorsMap[$aid] = $aRow;
            }
        }

        if ($opinionAuthors) {
            $ids = array_column($opinionAuthors, 'id');
            $ids = array_map('intval', $ids);
            $ids = array_values(array_unique($ids));

            if ($ids) {
                $in = implode(',', array_fill(0, count($ids), '?'));
                $sql = "
                    SELECT n . *
                    FROM news n
                    JOIN (
                        SELECT opinion_author_id, MAX(created_at) AS max_created
                        FROM news
                        WHERE opinion_author_id IN ($in)
                          AND status = 'published'
                          AND deleted_at IS NULL
                          AND (publish_at IS NULL OR publish_at <= NOW())
                        GROUP BY opinion_author_id
                    ) t ON n .opinion_author_id = t .opinion_author_id
                       AND n .created_at = t .max_created
                ";
                $stmt2 = $pdo->prepare($sql);
                $stmt2->execute($ids);
                foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $aid = (int)$row['opinion_author_id'];
                    $opinionAuthorsLast[$aid] = $row;
                }
            }
        }
    } catch (\Throwable $e) {
        $msg = $e->getMessage();
        if (stripos($msg, 'opinion_authors') === false) {
            error_log('[Home Opinion Authors] ' . $msg);
        }
        $opinionAuthors = [];
        $opinionAuthorsMap = [];
    }
}

if ($pdo instanceof PDO) {
    foreach ($blocks as $key => &$block) {
        if (empty($block['category'])) continue;

        $catId = (int)$block['category']['id'];
        if ($catId <= 0) continue;

        $limit = 18;

        try {
            $sql = "
                SELECT *
                FROM news
                WHERE status = 'published'
                  AND deleted_at IS NULL
                  AND (publish_at IS NULL OR publish_at <= NOW())
                  AND category_id = :cid
                ORDER BY COALESCE(publish_at, published_at, created_at) DESC
                LIMIT {$limit}
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['cid' => $catId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $block['news'] = $rows;
        } catch (\Throwable $e) {
            $block['news'] = [];
        }
    }
    unset($block);
}

$trendingNews = [];
if (class_exists('HomeController')) {
    try {
        $trendingNews = HomeController::getTrendingNews(10);
        if (!is_array($trendingNews)) {
            $trendingNews = [];
        }
    } catch (\Throwable $e) {
        $trendingNews = [];
    }
}

?>
<div class="as-hero">
  <div class="as-container">

    <?php
      $hero = $sliderSlides[0] ?? null;
      
      $miniItems = array_slice($latestNews, 0, 4);
    ?>

    <div class="as-grid as-grid-hero">
      <?php if(!empty($hero)): ?>
        <?php
          $heroTitle = (string)($hero['title'] ?? '');
          $heroSub   = (string)($hero['subtitle'] ?? '');
          $heroUrl   = trim((string)($hero['link_url'] ?? ''));
          if ($heroUrl === '') { $heroUrl = ($_gdy_baseUrl ?: '/'); }
          $heroImgRaw = (string)($hero['image_path'] ?? '');
          $heroImg = $buildImageUrl($heroImgRaw ?: 'assets/images/placeholder.webp');

	        
	        $slidesPayload = [];
	        foreach ((array)$sliderSlides as $it) {
	          $t = (string)($it['title'] ?? '');
	          $k = (string)($it['subtitle'] ?? '');
	          $u = trim((string)($it['link_url'] ?? ''));
	          if ($u === '') { $u = ($_gdy_baseUrl ?: '/'); }
	          $p = (string)($it['image_path'] ?? '');
	          $im = $buildImageUrl($p ?: 'assets/images/placeholder.webp');
	          $slidesPayload[] = ['url' => $u, 'img' => $im, 'title' => $t, 'kicker' => $k];
	        }
	        
	        
	        $slidesJsonRaw = json_encode($slidesPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	        $slidesB64 = h(base64_encode($slidesJsonRaw));
        ?>
	      <article class="as-feature as-feature--slider" data-gdy-hero-slider-b64="<?= $slidesB64 ?>" data-gdy-hero-idx="0" tabindex="0">
	        <a class="as-hero-link" href="<?= h($heroUrl) ?>" aria-label="<?= h($heroTitle) ?>">
	          <img class="as-hero-img" src="<?= h($heroImg) ?>" alt="<?= h($heroTitle) ?>" loading="lazy">
	        </a>
	        <div class="as-feature-body">
	          <?php if($heroSub !== ''): ?>
	            <span class="as-kicker as-hero-kicker"><?= h($heroSub) ?></span>
	          <?php else: ?>
	            <span class="as-kicker as-hero-kicker" style="display:none"></span>
	          <?php endif; ?>
	          <h2 class="as-title"><a class="gdy-link-plain as-hero-title" href="<?= h($heroUrl) ?>"><?= h($heroTitle) ?></a></h2>
	        </div>

	        <!-- Hero slider UI (arrows + dots + progress). Enhanced in v1.11 patch v10. -->
	        <button type="button" class="gdy-hero-nav gdy-hero-prev" aria-label="<?= h(__('Previous')) ?>" data-gdy-hero-prev>
	          <svg class="gdy-hero-nav-ic" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
	            <path d="M15 18l-6-6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"    />
	          </svg>
	        </button>
	        <button type="button" class="gdy-hero-nav gdy-hero-next" aria-label="<?= h(__('Next')) ?>" data-gdy-hero-next>
	          <svg class="gdy-hero-nav-ic" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
	            <path d="M9 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"    />
	          </svg>
	        </button>
	        <div class="gdy-hero-ui" role="region" role="region" role="region" role="region" role="region" role="region" aria-label="&#039; . htmlspecialchars($label, ENT_QUOTES, &#039;UTF-8&#039;) . &#039;">
	          <div class="gdy-hero-dots" role="tablist" data-gdy-hero-dots></div>
	          <div class="gdy-hero-progress" aria-hidden="true"><span></span></div>
	        </div>
        </article>
      <?php else: ?>
        <div class="as-feature as-feature--empty">
          <div class="as-feature__empty-text"><?= __('t_305bcf385b', 'لا توجد شرائح حالياً') ?></div>
        </div>
      <?php endif; ?>

      <div class="as-mini-grid">
        <?php foreach($miniItems as $item): ?>
          <?php
            $u = $buildNewsUrl($item);
            $img = $buildImageUrl($item['featured_image'] ?? $item['image_path'] ?? $item['image'] ?? 'assets/images/placeholder-thumb.jpg');
            $date = !empty($item['created_at']) ? date('Y-m-d', strtotime($item['created_at'])) : '';
          ?>
          <article class="as-mini">
            <a href="<?= h($u) ?>" aria-label="<?= h($item['title'] ?? '') ?>">
              <img src="<?= h($img) ?>" alt="<?= h($item['title'] ?? '') ?>" loading="lazy">
            </a>
            <div class="as-mini-body">
              <h3 class="as-mini-title"><a href="<?= h($u) ?>"><?= h($item['title'] ?? '') ?></a></h3>
              <?php if($date): ?><div class="as-mini-meta"><?= h($date) ?></div><?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if(!empty($breakingNews)): ?>
      <div class="as-breaking" role="region" role="region" role="region" role="region" role="region" role="region" aria-label="&#039; . htmlspecialchars($label, ENT_QUOTES, &#039;UTF-8&#039;) . &#039;">
        <div class="as-breaking-bar">
	          <div class="as-breaking-label"><?= __('breaking.label', 'عاجل') ?></div>
          <div class="as-breaking-track">
            <div class="as-breaking-items">
              <?php foreach($breakingNews as $bn): ?>
                <a href="<?= h($buildNewsUrl($bn)) ?>"><?= h($bn['title'] ?? '') ?></a>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

  </div>
</div>

	<?php if(!empty($sliderSlides) && count($sliderSlides) > 1): ?>
	<script<?= $__gdy_nonce_attr ?>>
	(function(){
	  try{
	    
	    var reduceMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
	    var el = document.querySelector('[data-gdy-hero-slider-b64],[data-gdy-hero-slider]');
	    if (!el) return;
	    var raw = '';
	    
	    var b64 = el.getAttribute('data-gdy-hero-slider-b64') || '';
	    if (b64){
	      try{
	        var bin = atob(b64);
	        if (window.TextDecoder){
	          var bytes = Uint8Array.from(bin, function(c){ return c.charCodeAt(0); });
	          raw = new TextDecoder('utf-8').decode(bytes);
	        } else {
	          
	          raw = decodeURIComponent(bin.split('').map(function(c){
	            return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
	          }).join(''));
	        }
	      }catch(e){ raw = ''; }
	    }
	    if (!raw){ raw = el.getAttribute('data-gdy-hero-slider') || '[]'; }
	    var slides = [];
	    try { slides = JSON.parse(raw) || []; } catch(e) { slides = []; }
	    if (!slides || slides.length < 2) return;

	    var link   = el.querySelector('.as-hero-link');
	    var img    = el.querySelector('.as-hero-img');
	    var titleA = el.querySelector('.as-hero-title');
	    var kicker = el.querySelector('.as-hero-kicker');
	    if (!link || !img || !titleA) return;

	    var idx = parseInt(el.getAttribute('data-gdy-hero-idx') || '0', 10);
	    if (isNaN(idx) || idx < 0) idx = 0;
	    if (idx >= slides.length) idx = 0;

	    var prevBtn   = el.querySelector('[data-gdy-hero-prev]');
	    var nextBtn   = el.querySelector('[data-gdy-hero-next]');
	    var dotsWrap  = el.querySelector('[data-gdy-hero-dots]');
	    var progSpan  = (el.querySelector('.gdy-hero-progress > span') || null);
	
	    var INTERVAL = 5500;
	    var FADE_MS  = 280;
	
	    function setSlide(i, opts){
	      opts = opts || {};
	      var s = slides[i];
	      if (!s) return;
	      
	      if (!reduceMotion && !opts.instant){
	        el.classList.add('gdy-hero-is-fading');
	      }
	      el.setAttribute('data-gdy-hero-idx', String(i));
	      link.href = s.url || '#';
	      link.setAttribute('aria-label', s.title || '');
	      titleA.href = s.url || '#';
	      titleA.textContent = s.title || '';
	      img.src = s.img || '';
	      img.alt = s.title || '';
	      if (kicker){
	        var k = (s.kicker || '').trim();
	        kicker.textContent = k;
	        kicker.style.display = k ? '' : 'none';
	      }
	      
	      if (dotsWrap){
	        try{
	          var btns = dotsWrap.querySelectorAll('button[data-gdy-hero-dot]');
	          for (var di=0; di<btns.length; di++){
	            var isOn = (di === i);
	            btns[di].classList.toggle('is-active', isOn);
	            btns[di].setAttribute('aria-selected', isOn ? 'true' : 'false');
	            btns[di].tabIndex = isOn ? 0 : -1;
	          }
	        }catch(e){}
	      }
	      if (progSpan && !reduceMotion){
	        try{
	          progSpan.style.animation = 'none';
	          
	          progSpan.offsetHeight;
	          progSpan.style.animation = 'gdyHeroProg ' + INTERVAL + 'ms linear forwards';
	        }catch(e){}
	      }
	      if (!reduceMotion && !opts.instant){
	        setTimeout(function(){
	          try{ el.classList.remove('gdy-hero-is-fading'); }catch(e){}
	        }, FADE_MS);
	      }
	    }

	    function clampIndex(i){
	      i = parseInt(i, 10);
	      if (isNaN(i)) i = 0;
	      if (i < 0) i = 0;
	      if (i >= slides.length) i = 0;
	      return i;
	    }

	    function goTo(i, opts){
	      idx = clampIndex(i);
	      setSlide(idx, opts);
	    }

	    function next(){ goTo((idx + 1) % slides.length); }
	    function prev(){ goTo((idx - 1 + slides.length) % slides.length); }

	    
	    if (dotsWrap){
	      try{
	        dotsWrap.innerHTML = '';
	        for (var d=0; d<slides.length; d++){
	          var b = document.createElement('button');
	          b.type = 'button';
	          b.className = 'gdy-hero-dot';
	          b.setAttribute('data-gdy-hero-dot', String(d));
	          b.setAttribute('role', 'tab');
	          b.setAttribute('aria-label', (d+1) + ' / ' + slides.length);
	          b.addEventListener('click', (function(n){
	            return function(){
	              stop();
	              goTo(n);
	              if (!reduceMotion) start();
	            };
	          })(d));
	          dotsWrap.appendChild(b);
	        }
	      }catch(e){}
	    }

	    var timer = null;
	    function start(){
	      if (reduceMotion) return;
	      stop();
	      
	      goTo(idx, { instant:true });
	      timer = setInterval(function(){ next(); }, INTERVAL);
	      
	      if (progSpan){
	        try{ progSpan.style.animation = 'none'; progSpan.offsetHeight; progSpan.style.animation = 'gdyHeroProg ' + INTERVAL + 'ms linear forwards'; }catch(e){}
	      }
	    }
	    function stop(){ if (timer){ clearInterval(timer); timer = null; } }

	    
	    for (var j=0;j<slides.length;j++){
	      try{ var im = new Image(); im.src = slides[j].img; }catch(e){}
	    }

	    
	    if (prevBtn){
	      prevBtn.addEventListener('click', function(){ stop(); prev(); if (!reduceMotion) start(); });
	    }
	    if (nextBtn){
	      nextBtn.addEventListener('click', function(){ stop(); next(); if (!reduceMotion) start(); });
	    }

	    
	    el.addEventListener('mouseenter', function(){ if (!reduceMotion) stop(); });
	    el.addEventListener('mouseleave', function(){ if (!reduceMotion) start(); });
	    el.addEventListener('focusin', function(){ if (!reduceMotion) stop(); });
	    el.addEventListener('focusout', function(){ if (!reduceMotion) start(); });

	    
	    el.addEventListener('keydown', function(ev){
	      try{
	        var k = ev.key || '';
	        if (k === 'ArrowLeft' || k === 'ArrowRight'){
	          ev.preventDefault();
	          stop();
	          
	          var rtl = (document.documentElement.getAttribute('dir') || '').toLowerCase() === 'rtl';
	          var goNext = (k === 'ArrowRight' && !rtl) || (k === 'ArrowLeft' && rtl);
	          if (goNext) next(); else prev();
	          if (!reduceMotion) start();
	        }
	      }catch(e){}
	    });

	    
	    (function(){
	      var sx=0, sy=0, tracking=false;
	      el.addEventListener('touchstart', function(e){
	        try{
	          if (!e.touches || !e.touches[0]) return;
	          tracking=true;
	          sx = e.touches[0].clientX;
	          sy = e.touches[0].clientY;
	          if (!reduceMotion) stop();
	        }catch(_e){}
	      }, {passive:true});
	      el.addEventListener('touchend', function(e){
	        try{
	          if (!tracking) return;
	          tracking=false;
	          var t = (e.changedTouches && e.changedTouches[0]) ? e.changedTouches[0] : null;
	          if (!t) return;
	          var dx = t.clientX - sx;
	          var dy = t.clientY - sy;
	          if (Math.abs(dx) < 42 || Math.abs(dx) < Math.abs(dy)){
	            if (!reduceMotion) start();
	            return;
	          }
	          var rtl = (document.documentElement.getAttribute('dir') || '').toLowerCase() === 'rtl';
	          
	          var swipeLeft = dx < 0;
	          var goNext = rtl ? !swipeLeft : swipeLeft;
	          if (goNext) next(); else prev();
	          if (!reduceMotion) start();
	        }catch(_e){}
	      }, {passive:true});
	    })();

	    
	    document.addEventListener('visibilitychange', function(){
	      try{ if (document.hidden) stop(); else start(); }catch(e){}
	    });

	    
	    goTo(idx, { instant:true });
	    if (!reduceMotion) start();
	  }catch(e){}
	})();
	</script>
	<?php endif; ?>

<div class="as-main">
  <div class="as-container">
    <div class="as-grid as-grid-main">

      <section class="as-block">
        <div class="as-block-hd">
          <h2 class="as-block-title"><?= __('Latest news') ?></h2>
          <a class="as-block-more" href="<?= h($_gdy_baseUrl.'/news') ?>"><?= __('More') ?></a>
        </div>

        <?php if(!empty($latestNews)): ?>
          <div class="as-list">
            <?php foreach($latestNews as $n): ?>
              <?php
                $u = $buildNewsUrl($n);
                $img = $buildImageUrl($n['featured_image'] ?? $n['image_path'] ?? $n['image'] ?? 'assets/images/placeholder-thumb.jpg');
                $excerpt = $n['excerpt'] ?? $n['summary'] ?? '';
                if($excerpt && mb_strlen($excerpt,'UTF-8') > 170){
                  $excerpt = mb_substr($excerpt,0,170,'UTF-8').'…';
                }
              ?>
              <article class="as-item">
                <a class="as-item-img" href="<?= h($u) ?>" aria-label="<?= h($n['title'] ?? '') ?>">
                  <img src="<?= h($img) ?>" alt="<?= h($n['title'] ?? '') ?>" loading="lazy">
                </a>
                <div>
                  <h3 class="as-item-title"><a href="<?= h($u) ?>"><?= h($n['title'] ?? '') ?></a></h3>
                  <?php if($excerpt): ?><div class="as-item-excerpt"><?= h($excerpt) ?></div><?php endif; ?>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="p-3">
            <div class="alert alert-info rounded-4 mb-0"><?= __('No news to show yet') ?></div>
          </div>
        <?php endif; ?>
      </section>

      <aside class="as-side">

        <?php if(!empty($trendingNews)): ?>
        <section class="as-block mb-3">
          <div class="as-block-hd">
            <h2 class="as-block-title"><?= __('Trending') ?></h2>
          </div>
          <div class="p-3 gdy-flex-wrap-gap">
            <?php foreach(array_slice($trendingNews,0,10) as $t): ?>
              <a class="as-pill" href="<?= h($buildNewsUrl($t)) ?>"><?= h($t['title'] ?? '') ?></a>
            <?php endforeach; ?>
          </div>
        </section>
        <?php endif; ?>

        <?php if(!empty($categories)): ?>
        <section class="as-block">
          <div class="as-block-hd">
            <h2 class="as-block-title"><?= __('Categories') ?></h2>
          </div>
          <div class="p-3 gdy-flex-wrap-gap">
            <?php foreach(array_slice($categories,0,18) as $c): ?>
              <?php
                $name = $c['name'] ?? '';
                $slug = $c['slug'] ?? '';
                $url = $slug ? ($_gdy_baseUrl.'/category/'.rawurlencode($slug)) : ($_gdy_baseUrl.'/categories');
              ?>
              <a class="as-pill" href="<?= h($url) ?>"><?= h($name) ?></a>
            <?php endforeach; ?>
          </div>
        </section>
        <?php endif; ?>

      </aside>

    </div>
  </div>
</div>
