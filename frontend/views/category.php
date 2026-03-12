<?php

if (!function_exists('h')) {
    function h($v)
    {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('__')) {
    function __($key, $fallback = null)
    {
        return $fallback !== null ? $fallback : (string)$key;
    }
}

if (!function_exists('gdy_safe_category_name')) {
    function gdy_safe_category_name($category)
    {
        $fallback = '';
        $categoryId = 0;

        if (is_array($category)) {
            if (isset($category['name'])) {
                $fallback = (string)$category['name'];
            }
            if (isset($category['id'])) {
                $categoryId = (int)$category['id'];
            }
        }

        if (function_exists('gdy_tr')) {
            $translated = gdy_tr('category', $categoryId, 'name', $fallback);
            return $translated !== null ? (string)$translated : $fallback;
        }

        return $fallback;
    }
}

$category    = (isset($category) && is_array($category)) ? $category : array();
$newsItems   = (isset($newsItems) && is_array($newsItems)) ? $newsItems : ((isset($items) && is_array($items)) ? $items : ((isset($news) && is_array($news)) ? $news : ((isset($posts) && is_array($posts)) ? $posts : array())));
$baseUrl     = isset($baseUrl) ? rtrim((string)$baseUrl, '/') : '';
$themeClass  = isset($themeClass) ? (string)$themeClass : '';
$page        = isset($page) ? max(1, (int)$page) : (isset($currentPage) ? max(1, (int)$currentPage) : 1);
$totalPages  = isset($totalPages) ? max(1, (int)$totalPages) : (isset($pages) ? max(1, (int)$pages) : 1);

$categoryName = gdy_safe_category_name($category);
$categorySlug = isset($category['slug']) ? (string)$category['slug'] : '';
$categoryDesc = isset($category['description']) ? (string)$category['description'] : '';

$pageTitle = $categoryName !== '' ? $categoryName : __('category.title', 'القسم');
$metaTitle = isset($metaTitle) ? (string)$metaTitle : $pageTitle;
$metaDescription = isset($metaDescription) ? (string)$metaDescription : ($categoryDesc !== '' ? $categoryDesc : __('category.description', 'تصفح أخبار هذا القسم'));

if (!function_exists('gdy_category_news_url')) {
    function gdy_category_news_url($item, $baseUrl = '')
    {
        $baseUrl = rtrim((string)$baseUrl, '/');

        if (is_array($item)) {
            if (!empty($item['id'])) {
                return $baseUrl . '/news/id/' . rawurlencode((string)$item['id']);
            }
            if (!empty($item['slug'])) {
                return $baseUrl . '/news/' . rawurlencode((string)$item['slug']);
            }
            if (!empty($item['url'])) {
                return (string)$item['url'];
            }
        }

        return '#';
    }
}

if (!function_exists('gdy_category_news_image')) {
    function gdy_category_news_image($item, $baseUrl = '')
    {
        $fields = array('featured_image', 'image', 'image_path', 'thumbnail');
        foreach ($fields as $f) {
            if (!empty($item[$f])) {
                $img = (string)$item[$f];
                if (preg_match('~^https?://~i', $img)) {
                    return $img;
                }
                $baseUrl = rtrim((string)$baseUrl, '/');
                return $baseUrl . '/' . ltrim($img, '/');
            }
        }
        return '';
    }
}

if (!function_exists('gdy_category_news_excerpt')) {
    function gdy_category_news_excerpt($item)
    {
        $text = '';
        if (!empty($item['excerpt'])) {
            $text = (string)$item['excerpt'];
        } elseif (!empty($item['summary'])) {
            $text = (string)$item['summary'];
        } elseif (!empty($item['content'])) {
            $text = strip_tags((string)$item['content']);
        }

        $text = trim(preg_replace('/\s+/u', ' ', $text));
        if ($text === '') {
            return '';
        }

        if (function_exists('mb_substr')) {
            return mb_substr($text, 0, 140, 'UTF-8') . (mb_strlen($text, 'UTF-8') > 140 ? '…' : '');
        }

        return substr($text, 0, 140) . (strlen($text) > 140 ? '…' : '');
    }
}

if (!function_exists('gdy_category_news_date')) {
    function gdy_category_news_date($item)
    {
        foreach (array('publish_at', 'published_at', 'created_at', 'date') as $f) {
            if (!empty($item[$f])) {
                $ts = strtotime((string)$item[$f]);
                if ($ts) {
                    return date('Y-m-d', $ts);
                }
            }
        }
        return '';
    }
}

?>
<!DOCTYPE html>
<html lang="<?php echo h(function_exists('gdy_lang') ? gdy_lang() : 'ar'); ?>" dir="<?php echo h(function_exists('gdy_dir') ? gdy_dir() : 'rtl'); ?>">
<head><meta charset="utf-8">
<title><?php echo h($metaTitle); ?></title>
    <meta name="description" content="<?php echo h($metaDescription); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body{margin:0;font-family:Arial,sans-serif;background:#f8fafc;color:#111827}
        .container{max-width:1200px;margin:0 auto;padding:24px}
        .category-head{margin-bottom:24px;padding:20px;border-radius:16px;background:#fff;box-shadow:0 8px 24px rgba(0,0,0,.05)}
        .category-title{margin:0 0 8px;font-size:28px;font-weight:700}
        .category-desc{margin:0;color:#6b7280}
        .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:18px}
        .card{background:#fff;border:1px solid 
        .card img{width:100%;height:190px;object-fit:cover;display:block}
        .card-body{padding:16px}
        .meta{font-size:12px;color:#6b7280;margin-bottom:8px}
        .title{font-size:18px;font-weight:700;line-height:1.5;margin:0 0 10px}
        .title a{text-decoration:none;color:inherit}
        .excerpt{font-size:14px;line-height:1.8;color:#4b5563}
        .empty{padding:24px;border-radius:16px;background:#fff;border:1px dashed 
        .pagination{display:flex;gap:8px;flex-wrap:wrap;margin-top:24px}
        .pagination a,.pagination span{padding:10px 14px;border-radius:10px;background:#fff;border:1px solid 
        .pagination .current{background:#111827;color:#fff;border-color:#111827}
    </style>
</head>
<body class="<?php echo h($themeClass); ?>">

<div class="container">
    <section class="category-head">
        <h1 class="category-title"><?php echo h($pageTitle); ?></h1>
        <?php if ($categoryDesc !== ''): ?>
            <p class="category-desc"><?php echo h($categoryDesc); ?></p>
        <?php endif; ?>
    </section>

    <?php if (!empty($newsItems)): ?>
        <section class="grid">
            <?php foreach ($newsItems as $item): ?>
                <?php
                $url     = gdy_category_news_url(is_array($item) ? $item : array(), $baseUrl);
                $image   = gdy_category_news_image(is_array($item) ? $item : array(), $baseUrl);
                $title   = isset($item['title']) ? (string)$item['title'] : __('news.untitled', 'خبر بدون عنوان');
                $excerpt = gdy_category_news_excerpt(is_array($item) ? $item : array());
                $date    = gdy_category_news_date(is_array($item) ? $item : array());
                ?>
                <article class="card">
                    <?php if ($image !== ''): ?>
                        <a href="<?php echo h($url); ?>">
                            <img src="<?php echo h($image); ?>" alt="<?php echo h($title); ?>" loading="lazy">
                        </a>
                    <?php endif; ?>
                    <div class="card-body">
                        <?php if ($date !== ''): ?>
                            <div class="meta"><?php echo h($date); ?></div>
                        <?php endif; ?>
                        <h2 class="title">
                            <a href="<?php echo h($url); ?>"><?php echo h($title); ?></a>
                        </h2>
                        <?php if ($excerpt !== ''): ?>
                            <div class="excerpt"><?php echo h($excerpt); ?></div>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>

        <?php if ($totalPages > 1): ?>
            <nav class="pagination" aria-label="Pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php
                    $pageUrl = $baseUrl . '/category/' . rawurlencode($categorySlug);
                    $pageUrl .= ($i > 1 ? '?page=' . $i : '');
                    ?>
                    <?php if ($i === $page): ?>
                        <span class="current"><?php echo (int)$i; ?></span>
                    <?php else: ?>
                        <a href="<?php echo h($pageUrl); ?>"><?php echo (int)$i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>

    <?php else: ?>
        <div class="empty"><?php echo h(__('category.empty', 'لا توجد أخبار داخل هذا القسم حالياً.')); ?></div>
    <?php endif; ?>
</div>

</body>
</html>