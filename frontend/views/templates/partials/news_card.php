<?php

if (!function_exists('h')) {
    function h($v) {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

if (function_exists('base_url')) {
    $baseUrl = rtrim(base_url(), '/');
} else {
    $baseUrl = '';
}

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

    
    
    $rootBase = rtrim($baseUrl, '/');
    $rootBase = preg_replace('~/(ar|en|fr)$~i', '', $rootBase);

    if ($path[0] === '/') {
        return $rootBase . $path;
    }

    return $rootBase . '/' .ltrim($path, '/');
}

    
}

$id = isset($row['id']) ? (int)$row['id'] : 0;
$slug = isset($row['slug']) ? trim((string)$row['slug']) : '';
$title = (string)($row['title'] ?? '');
$excerpt = (string)($row['excerpt'] ?? '');
$dateRaw = $row['created_at'] ?? ($row['published_at'] ?? null);
$date = $dateRaw ? date('Y-m-d', strtotime((string)$dateRaw)) : '';
$views = isset($row['views']) ? (int)$row['views'] : null;

$lang = function_exists('gdy_request_lang') ? gdy_request_lang('ar') : 'ar';
$newsUrl = '#';
if (function_exists('gdy_route_news_url')) {
    $newsUrl = gdy_route_news_url(['id' => $id, 'slug' => $slug], $lang);
} elseif ($slug !== '') {
    $newsUrl = rtrim($baseUrl, '/') . '/' . $lang . '/news/' . rawurlencode($slug);
} elseif ($id > 0) {
    $newsUrl = rtrim($baseUrl, '/') . '/' . $lang . '/news/id/' . $id;
}

$imgRaw = $row['featured_image']
 ?? $row['image_path']
 ?? $row['main_image']
 ?? $row['image']
 ?? $row['thumbnail']
 ?? $row['photo']
 ?? $row['featured_image_orig']
 ?? $row['img']
 ?? null;

$img = null;
if (!empty($imgRaw)) {
    $imgRaw = trim((string)$imgRaw);
    if (preg_match('~^https?://~i', $imgRaw)) {
        
        $img = $imgRaw;
    } else {
        $img = build_image_url($baseUrl, $imgRaw);
    }
}

if ($excerpt === '' && $title !== '') {
    $excerpt = mb_substr($title, 0, 70, 'UTF-8');
    if (mb_strlen($title, 'UTF-8') > 70) {
        $excerpt .= '…';
    }
}

$extraClass = isset($cardExtraClass) ? (string)$cardExtraClass : '';
?>
<a href = "<?php echo h($newsUrl); ?>" class = "gdy-card <?php echo h($extraClass); ?>">
    <?php if (!empty($img)): ?>
        <!--
          Thumbnail wrapper intentionally constrains image height.
          CSS (assets/css/media-system.css) enforces 16:9 + object-fit cover.
          This prevents portrait images from stretching cards on category/archive pages.
        -->
        <div class="gdy-card-thumb" style="aspect-ratio:16/9;overflow:hidden;">
            <img loading="lazy" decoding="async" src="<?php echo h($img); ?>" alt="<?php echo h($title); ?>" style="width:100%;height:100%;object-fit:cover;display:block;">
        </div>
    <?php endif; ?>

    <h3 class = "gdy-card-title"><?php echo h($title); ?></h3>

    <?php if ($excerpt !== ''): ?>
        <p class = "gdy-card-excerpt"><?php echo h($excerpt); ?></p>
    <?php endif; ?>

    <div class = "gdy-card-meta">
        <?php if ($date): ?>
            <span>
                <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
                <?php echo h($date); ?>
            </span>
        <?php endif; ?>
        <?php if ($views !== null): ?>
            <span>
                <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
                <?php echo number_format($views); ?> مشاهدة
            </span>
        <?php endif; ?>
    </div>
</a>
