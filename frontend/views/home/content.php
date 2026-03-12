<?php

$pdo = $pdo ?? (function_exists("gdy_pdo_safe") ? gdy_pdo_safe() : null);
if (!function_exists("nf")) {
  function nf(array $n, string $field): string {
    global $pdo;
    if ($pdo instanceof PDO && function_exists("gdy_news_field")) {
      return (string)gdy_news_field($pdo, $n, $field);
    }
    return (string)($n[$field] ?? "");
  }
}

if (!function_exists('gdy_img_src')) {
    function gdy_img_src(?string $src): string {
        $src = trim((string)$src);
        if ($src === '') return '';

        
        if (preg_match('~^(https?:)?//~i', $src)) return $src;
        if (str_starts_with($src, 'data:')) return $src;
        if ($src[0] === '/') return $src;

        return '/' .ltrim($src, '/');
    }
}

$mainNews = $latestNews[0] ?? null;

if (!function_exists('gdy_youtube_embed_url')) {
    function gdy_youtube_embed_url(string $url): ?string {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        $lower = strtolower($url);

        
        if (strpos($lower, 'instagram.com') !== false ||             strpos($lower, 'snapchat.com') !== false) {
            return null;
        }

        
        if (preg_match('~tiktok\.com/.*/video/([0-9]+)~i', $url, $m)) {
            return 'https://www.tiktok.com/embed/v2/' . $m[1];
        }

        
        if (preg_match('~(facebook\.com|fb\.watch)~i', $url)) {
            $encoded = rawurlencode($url);
            return 'https://www.facebook.com/plugins/video.php?href=' . $encoded . '&show_text=0&width=700';
        }

        
        if (preg_match('~vimeo\.com/([0-9]+)~i', $url, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }

        
        if (preg_match('~dailymotion\.com/video/([a-zA-Z0-9]+)~i', $url, $m)) {
            return 'https://www.dailymotion.com/embed/video/' . $m[1];
        }

        
        
        if (preg_match('~youtube\.com/embed/([a-zA-Z0-9_-]{6,})~i', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }

        
        if (preg_match('~youtube\.com/watch\?v=([a-zA-Z0-9_-]{6,})~i', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }

        
        if (preg_match('~youtu\.be/([a-zA-Z0-9_-]{6,})~i', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }

        
        return null;
    }
}
?>

<!-- إعلان أعلى الصفحة -->
<?php if (!empty($headerAd) && strpos($headerAd, 'No active ad') === false): ?>
<div class = "header-ad-container" style = "margin-bottom: 2rem; text-align: center;">
    <?php echo $headerAd; ?>
</div>
<?php endif; ?>
<?php
$archiveUrl = $archiveUrl ?? '';
$homeLatestTitle = $homeLatestTitle ?? '';
if (!isset($newsUrl) || !is_callable($newsUrl)) {
    $newsUrl = function ($row) {
        return '';
    };
}
?>
<!-- شبكة آخر الأخبار -->
<section aria-label = "آخر الأخبار">
    <div class = "section-header">

<!-- الميزات الجديدة المتميزة -->

<!-- 1 . شريط الإشعارات الذكية -->
<?php if (!empty($smartNotifications)): ?>
<div class = "smart-notifications" style = "margin-bottom: 2rem;">
    <div class = "notifications-container">
        <div style = "display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <?php foreach ($smartNotifications as $notification): ?>
            <div class = "notification-item" style = "
                display: flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.5rem 1rem;
                background: rgba(255,255,255,0.15);
                border-radius: 10px;
	                -webkit-backdrop-filter: blur(10px);
	                backdrop-filter: blur(10px);
            ">
                <i class = "fa <?php echo $notification['icon']; ?>" style = "font-size: 1.1rem;"></i>
                <div>
                    <div style = "font-weight: 600; font-size: 0.9rem;"><?php echo nf($notification,'title'); ?></div>
                    <div style = "font-size: 0.8rem; opacity: 0.9;"><?php echo $notification['message']; ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- 2 . بطاقة الإحصائيات الذكية -->
<div class = "smart-stats" style = "margin-bottom: 2rem;">
    <div class = "stats-grid" style = "
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    ">
        <div class = "stat-card" style = "
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-left: 4px solid 
        ">
            <div style = "font-size: 2rem; color: 
            <div style = "font-size: 1.5rem; font-weight: 700; color: 
            <div style = "color: 
        </div>

        <div class = "stat-card" style = "
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-left: 4px solid 
        ">
            <div style = "font-size: 2rem; color: 
            <div style = "font-size: 1.5rem; font-weight: 700; color: 
            <div style = "color: 
        </div>

        <div class = "stat-card" style = "
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-left: 4px solid 
        ">
            <div style = "font-size: 2rem; color: 
            <div style = "font-size: 1.5rem; font-weight: 700; color: 
            <div style = "color: 
        </div>

        <div class = "stat-card" style = "
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-left: 4px solid 
        ">
            <div style = "font-size: 2rem; color: 
            <div style = "font-size: 1.5rem; font-weight: 700; color: 
            <div style = "color: 
        </div>
    </div>
</div>

<!-- 3 . الأخبار العاجلة -->
<?php if (!empty($breakingNews)): ?>
<div class="breaking-news">
    <div class="breaking-header">
        <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
        <span>أخبار عاجلة</span>
        <div class = "live-indicator" style = "
            background: white;
            color: 
            padding: 0.2rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-right: auto;
        ">
    <div class = "breaking-content" style = "
        background: white;
        border-radius: 0 0 10px 10px;
            🔴 بث مباشر
        </div>
    </div>
<?php

$newsUrl = $newsUrl ?? function($news) { return '#'; };
$archiveUrl = $archiveUrl ?? '#';
$siteName = $siteName ?? '';
$homeFeaturedTitle = $homeFeaturedTitle ?? '';
?>
    <div style = "
        background: white;
        border-radius: 0 0 10px 10px;
    <div style = "
        background: white;
        border-radius: 0 0 10px 10px;
        padding: 1.5rem;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    " class="breaking-content">
        <?php foreach ($breakingNews as $news): ?>
        <div class = "breaking-item" style = "
            padding: 1rem;
            border-bottom: 1px solid 
            display: flex;
            align-items: center;
            gap: 1rem;
        ">
            <div class = "breaking-badge" style = "
                background: 
                color: 
                padding: 0.3rem 0.8rem;
                border-radius: 20px;
                font-size: 0.8rem;
                font-weight: 600;
                white-space: nowrap;
            ">
                عاجل
            </div>
            <a href = "<?php echo h($newsUrl($news)); ?>" style = "
                color: 
                text-decoration: none;
                font-weight: 600;
                flex: 1;
            ">
                <?php echo h(nf($news,'title')); ?>
            </a>
            <div style = "color: 
                <?php echo date('H:i', strtotime($news['created_at'])); ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class = "layout-main">
    <!-- بلوك الهيرو / الخبر الرئيسي -->
    <section class = "hero-card fade-in" aria-label = "خبر رئيسي">
        <?php if ($mainNews): ?>
            <div class = "hero-badge">
                <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
                <span>خبر مميز</span>
            </div>
            <a href = "<?php echo h($newsUrl($mainNews)); ?>">
                <h1 class = "hero-title"><?php echo h(nf($mainNews,'title')); ?></h1>
            </a>
            <p class = "hero-subtitle">
                <?php
                    $sub = (string)(nf($mainNews,'excerpt') ?? '');
                    if ($sub === '') {
                        $sub = 'تابع تفاصيل الخبر من خلال المنصة.';
                    }
                    $cut = mb_substr($sub, 0, 200, 'UTF-8');
                    echo h($cut) . (mb_strlen($sub, 'UTF-8') > 200 ? '…' : '');
                ?>
            </p>
            <div class = "hero-meta">
                <span>
                    <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
                    <?php echo !empty($mainNews['published_at']) ? h(date('Y-m-d', strtotime($mainNews['published_at']))) : ''; ?>
                </span>
                <span><svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg> محدث من لوحة التحكم</span>
            </div>
            <div class = "hero-actions">
                <a href = "<?php echo h($newsUrl($mainNews)); ?>" class = "btn-primary">
                    <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
                    <span>اقرأ التفاصيل</span>
                </a>
                <a href = "<?php echo h($archiveUrl); ?>" class = "btn-ghost">
                    <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#news"></use></svg>
                    <span>جميع الأخبار</span>
                </a>
            </div>
        <?php else: ?>
            <div class = "hero-badge">
                <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
                <span>لا توجد أخبار بعد</span>
            </div>
            <h1 class = "hero-title">مرحباً بك في <?php echo h($siteName); ?></h1>
            <p class = "hero-subtitle">
                قم بإضافة أول خبر من لوحة التحكم، وسيتم عرضه هنا تلقائياً بخلفية تركوازية خفيفة مع هيدر وفوتر داكنين .
            </p>
        <?php endif; ?>
    </section>

    <!-- صندوق جانبي: أهم الأخبار / الأكثر قراءة -->
    <aside class = "side-widget fade-in" aria-label = "أهم الأخبار">
        <!-- إعلان أعلى الشريط الجانبي -->
        <?php if (!empty($sidebarTopAd) && strpos($sidebarTopAd, 'No active ad') === false): ?>
        <div class = "sidebar-ad-container" style = "margin-bottom: 1.5rem;">
            <?php echo $sidebarTopAd; ?>
        </div>
        <?php endif; ?>

        <div class = "side-widget-title">
            <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
            <span><?php echo h($homeFeaturedTitle); ?></span>
        </div>

        <?php if (!empty($featuredNews)): ?>
            <ul style = "list-style:none;margin:0;padding:0;position:relative;z-index:1;">
                <?php foreach ($featuredNews as $row): ?>
                    <li style = "margin-bottom:6px;">
                        <a href = "<?php echo h($newsUrl($row)); ?>" style = "display:flex;gap:8px;align-items:flex-start;">
                            <?php if (!empty($row['featured_image'])): ?>
                                <div style = "width:52px;height:52px;border-radius:12px;overflow:hidden;flex-shrink:0;background:#e2e8f0;">
								<img loading="lazy" decoding="async" src = "<?php echo h(gdy_img_src($row['featured_image'] ?? '')); ?>" alt = "<?php echo h(nf($row,'title')); ?>" style = "width:100%;height:100%;object-fit:cover;">
                                </div>
                            <?php endif; ?>
                            <div style = "flex:1;">
                                <div style = "font-size:.8rem;font-weight:600;color:#0f172a;">
                                    <?php
                                        $t = (string)nf($row,'title');
                                        $cut = mb_substr($t, 0, 70, 'UTF-8');
                                        echo h($cut) . (mb_strlen($t, 'UTF-8') > 70 ? '…' : '');
                                    ?>
                                </div>
                                <div style = "font-size:.7rem;color:#6b7280;">
                                    <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
                                    <?php echo !empty($row['published_at']) ? h(date('Y-m-d', strtotime($row['published_at']))) : ''; ?>
                                </div>
                            </div>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class = "side-widget-empty">
                لا توجد أخبار مميزة بالوقت الحالي . سيتم عرض الأخبار ذات المشاهدات الأعلى هنا تلقائياً .
            </p>
        <?php endif; ?>

        <!-- 4 . قسم الفيديوهات المميزة -->
        <?php if (!empty($featuredVideos)): ?>
        <div class = "video-section" style = "margin-top: 2rem;">
            <div class = "side-widget-title">
                <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
                <span>فيديوهات مميزة</span>
            </div>

            <div class = "videos-grid" style = "display: grid; gap: 1rem;">
                <?php foreach ($featuredVideos as $video): ?>
                    <?php
                        
                        $rawUrl = $video['raw_url'] ?? '';
                        $videoUrl = $video['video_url'] ?? '';

                        $embedUrl = '';
                        if ($videoUrl && $rawUrl) {
                            $embedUrl = $videoUrl;
                        } elseif ($videoUrl) {
                            $embedUrl = gdy_youtube_embed_url($videoUrl) ?: $videoUrl;
                            if ($rawUrl === '') {
                                $rawUrl = $videoUrl;
                            }
                        } elseif ($rawUrl) {
                            $embedUrl = gdy_youtube_embed_url($rawUrl) ?: $rawUrl;
                        }

                        if ($platform === '' && $rawUrl !== '') {
                            $lower = strtolower($rawUrl);
                            if (strpos($lower, 'tiktok.com') !== false) {
                                $platform = 'TikTok';
                            } elseif (strpos($lower, 'facebook.com') !== false || strpos($lower, 'fb.watch') !== false) {
                                $platform = 'Facebook';
                            } elseif (strpos($lower, 'instagram.com') !== false) {
                                $platform = 'Instagram';
                            } elseif (strpos($lower, 'snapchat.com') !== false) {
                                $platform = 'Snapchat';
                            } elseif (strpos($lower, 'vimeo.com') !== false) {
                                $platform = 'Vimeo';
                            } elseif (strpos($lower, 'dailymotion.com') !== false) {
                                $platform = 'Dailymotion';
                            } elseif (strpos($lower, 'youtube.com') !== false || strpos($lower, 'youtu.be') !== false) {
                                $platform = 'YouTube';
                            } else {
                                $platform = 'المصدر الأصلي';
                            }
                        } elseif ($platform === '') {
                            $platform = 'المصدر الأصلي';
                        }

                        $thumb = !empty($video['thumbnail'])
                            ? $video['thumbnail']
                            : $baseUrl . '/assets/images/video-placeholder.jpg';
                        $title = nf($video,'title') ?? '';
                        $duration = $video['duration'] ?? '';
                        $views = (int)($video['views'] ?? 0);
                    ?>
                    <div class = "video-card"
                         <?php echo $embedUrl ? 'data-embed="' .h($embedUrl) . '"' : ''; ?>
                         style = "
                            background: white;
                            border-radius: 10px;
                            overflow: hidden;
                            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                         ">
                        <!-- الصورة -->
                        <div class = "video-thumbnail"
                             style = "position: relative; display: block; cursor: pointer;">
                            <img loading="lazy" decoding="async" src = "<?php echo h($thumb); ?>"
                                 alt = "<?php echo h($title); ?>"
                                 style = "width: 100%; height: 120px; object-fit: cover;"
                                 data-gdy-fallback-src = "<?php echo h($baseUrl); ?>/assets/images/video-placeholder.jpg">

                            <div class = "video-overlay" style = "
                                position: absolute;
                                top: 0;
                                left: 0;
                                right: 0;
                                bottom: 0;
                                background: rgba(0,0,0,0.3);
                                display: flex;
                                align-items: center;
                                justify-content: center;
                            ">
                                <div class = "play-button" style = "
                                    background: rgba(255,255,255,0.9);
                                    width: 40px;
                                    height: 40px;
                                    border-radius: 50%;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                ">
                                    <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
                                </div>
                            </div>

                            <?php if ($duration): ?>
                                <div class = "video-duration" style = "
                                    position: absolute;
                                    bottom: 8px;
                                    left: 8px;
                                    background: rgba(0,0,0,0.7);
                                    color: white;
                                    padding: 0.2rem 0.5rem;
                                    border-radius: 4px;
                                    font-size: 0.7rem;
                                ">
                                    <?php echo h($duration); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class = "video-info" style = "padding: 1rem;">
                            <h4 style = "
                                margin: 0 0 0.5rem 0;
                                font-size: 0.9rem;
                                color: 
                                line-height: 1.4;
                            ">
                                <?php echo h($title); ?>
                            </h4>

                            <div style = "
                                display: flex;
                                justify-content: space-between;
                                align-items: center;
                                font-size: 0.8rem;
                                color: 
                                gap: . 5rem;
                            ">
                                <span>
                                    <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
                                    <?php echo number_format($views); ?> مشاهدة
                                </span>

                                <div style = "display:flex; flex-direction:column; align-items:flex-end; gap:4px;">
                                    <?php if (!empty($rawUrl)): ?>
                                        <a href = "<?php echo h($rawUrl); ?>"
                                           target = "_blank"
                                           rel = "noopener noreferrer"
                                           style = "
                                               background: 
                                               color: 
                                               padding: 0.2rem 0.6rem;
                                               border-radius: 6px;
                                               font-size: 0.75rem;
                                               text-decoration: none;
                                           ">
                                            مشاهدة على <?php echo h($platform); ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($embedUrl): ?>
                                        <!-- زر شاهد يفتح النافذة المنبثقة داخل الموقع -->
                                        <button type = "button"
                                                class = "watch-btn"
                                                style = "
                                                    background: 
                                                    color: white;
                                                    border: none;
                                                    padding: 0.3rem 0.8rem;
                                                    border-radius: 6px;
                                                    font-size: 0.8rem;
                                                    cursor: pointer;
                                                ">
                                            شاهد هنا
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- إعلان أسفل الشريط الجانبي -->
        <?php if (!empty($sidebarBottomAd) && strpos($sidebarBottomAd, 'No active ad') === false): ?>
        <div class = "sidebar-ad-container" style = "margin-top: 1.5rem;">
            <?php echo $sidebarBottomAd; ?>
        </div>
        <?php endif; ?>
    </aside>
</div>

<!-- إعلان أعلى المحتوى -->
<?php if (!empty($contentTopAd) && strpos($contentTopAd, 'No active ad') === false): ?>
<div class = "content-ad-container" style = "margin: 2rem 0; text-align: center;">
    <?php echo $contentTopAd; ?>
</div>
<?php endif; ?>

<!-- شبكة آخر الأخبار -->
<section aria-label = "آخر الأخبار">
    <div class = "section-header">
        <div>
            <div class = "section-title"><?php echo h($homeLatestTitle); ?></div>
            <div class = "section-sub">كل ما يُنشر من لوحة التحكم يظهر في هذه الشبكة تلقائياً . </div>
        </div>
        <a href = "<?php echo h($archiveUrl); ?>" class = "section-sub">
            عرض الأرشيف بالكامل
        </a>
    </div>

    <?php if (!empty($latestNews)): ?>
        <div class = "news-grid">
            <?php foreach ($latestNews as $idx => $row): ?>
                <?php if ($idx === 0) continue; ?>
                <article class = "news-card fade-in">
                    <?php if (!empty($row['featured_image'])): ?>
                        <a href = "<?php echo h($newsUrl($row)); ?>" class = "news-thumb">
						<img loading="lazy" decoding="async" src = "<?php echo h(gdy_img_src($row['featured_image'] ?? '')); ?>" alt = "<?php echo h(nf($row,'title')); ?>">
                        </a>
                    <?php endif; ?>
                    <div class = "news-body">
                        <a href = "<?php echo h($newsUrl($row)); ?>">
                            <h2 class = "news-title">
                                <?php
                                    $t = (string)nf($row,'title');
                                    $cut = mb_substr($t, 0, 90, 'UTF-8');
                                    echo h($cut) . (mb_strlen($t, 'UTF-8') > 90 ? '…' : '');
                                ?>
                            </h2>
                        </a>
                        <?php if (!empty(nf($row,'excerpt'))): ?>
                            <p class = "news-excerpt">
                                <?php
                                    $ex = (string)nf($row,'excerpt');
                                    $cut = mb_substr($ex, 0, 120, 'UTF-8');
                                    echo h($cut) . (mb_strlen($ex, 'UTF-8') > 120 ? '…' : '');
                                ?>
                            </p>
                        <?php endif; ?>
                        <div class = "news-meta">
                            <span>
                                <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
                                <?php echo !empty($row['published_at']) ? h(date('Y-m-d', strtotime($row['published_at']))) : ''; ?>
                            </span>
                            <span>
                                <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
                                <?php echo (int)($row['views'] ?? 0); ?> مشاهدة
                            </span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class = "section-sub">
            لا توجد أخبار منشورة بعد . يمكنك إضافة الأخبار من لوحة التحكم &gt; إدارة المحتوى .
        </p>
    <?php endif; ?>
</section>

<!-- 5 . قسم التوصيات الذكية -->
<?php if (!empty($smartRecommendations)): ?>
<section class = "smart-recommendations" style = "margin: 3rem 0;">
    <div class = "section-header">
        <div>
            <div class = "section-title">🎯 قد يعجبك</div>
            <div class = "section-sub">أخبار مختارة خصيصاً لك بناءً على اهتماماتك</div>
        </div>
    </div>

    <div class = "recommendations-grid" style = "
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
    ">
        <?php foreach ($smartRecommendations as $news): ?>
        <div class = "recommendation-card" style = "
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        ">
<?php if (!empty($news['featured_image'])): ?>
            <div class = "recommendation-image" style = "
                height: 160px;
                overflow: hidden;
            ">
				<img loading="lazy" decoding="async" src = "<?php echo h(gdy_img_src($news['featured_image'] ?? '')); ?>" alt = "<?php echo h(nf($news,'title')); ?>" style = "
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                    transition: transform 0.3s ease;
                ">
            </div>
            <?php endif; ?>
            <div class = "recommendation-content" style = "padding: 1.5rem;">
                <h3 style = "
                    margin: 0 0 1rem 0;
                    font-size: 1.1rem;
                    color: 
                    line-height: 1.4;
                ">
                    <a href = "<?php echo h($newsUrl($news)); ?>" style = "color: inherit; text-decoration: none;">
                        <?php echo h(mb_substr(nf($news,'title'), 0, 80)) . (mb_strlen(nf($news,'title')) > 80 ? '…' : ''); ?>
                    </a>
                </h3>
                <div style = "display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; color: 
                    <span><svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#plus"></use></svg> <?php echo !empty($news['published_at']) ? h(date('Y-m-d', strtotime($news['published_at']))) : ''; ?></span>
                    <span><svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#external-link"></use></svg> <?php echo number_format($news['views']); ?> مشاهدة</span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- إعلان أسفل المحتوى -->
<?php if (!empty($contentBottomAd) && strpos($contentBottomAd, 'No active ad') === false): ?>
<div class = "content-ad-container" style = "margin: 2rem 0; text-align: center;">
    <?php echo $contentBottomAd; ?>
</div>
<?php endif; ?>

<!-- باقي الكود كما هو -->
<?php if ($enableMostRead || $enableMostCommented): ?>
    <!-- ... أقسام الأكثر قراءة / تعليقاً إن وجدت ... -->
<?php endif; ?>

<!-- إعلان الفوتر -->
<?php if (!empty($footerAd) && strpos($footerAd, 'No active ad') === false): ?>
<div class = "footer-ad-container" style = "margin-top: 3rem; text-align: center;">
    <?php echo $footerAd; ?>
</div>
<?php endif; ?>

<!-- نافذة الفيديو المنبثقة -->
<div id = "videoModal" class = "video-modal">
    <div class = "video-modal-backdrop"></div>
    <div class = "video-modal-dialog">
        <button type = "button" id = "videoModalClose" class = "video-modal-close" aria-label = "إغلاق">
            ✕
        </button>
        <div class = "video-modal-body">
            <div class = "video-modal-iframe-wrapper">
                <iframe id = "videoModalIframe"
                        src = ""
                        frameborder = "0"
                        allow = "accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen
                        referrerpolicy = "strict-origin-when-cross-origin"></iframe>
            </div>
        </div>
    </div>
</div>

<!-- تنسيقات CSS للميزات الجديدة -->
<?php $cspNonce = defined('GDY_CSP_NONCE') ? (string)GDY_CSP_NONCE : ''; ?>
