<!-- ملف: views/news/single .php -->
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($meta_title) ?></title>
    <meta name = "description" content = "<?= htmlspecialchars($meta_description) ?>">
    <link rel = "canonical" href="<?= h_url($canonical_url) ?>">
    <meta property = "og:title" content="<?= h_attr($article['title'] ?? '') ?>">
    <meta property = "og:description" content = "<?= htmlspecialchars($meta_description) ?>">
    <meta property = "og:image" content="<?= h_url($og_image) ?>">
</head>
<body>
    <article class = "article-single">
        <!-- عنوان المقال -->
        <header class = "article-header">
            <h1><?= htmlspecialchars($article['title']) ?></h1>
            
            <!-- معلومات النشر -->
            <div class = "article-meta">
                <span class = "author">
                    <img src="<?= h_url($article['avatar'] ?? '') ?>" alt="<?= h_attr($article['username'] ?? '') ?>">
                    <?= htmlspecialchars($article['username']) ?>
                </span>
                <span class = "date">
                    <?= date('Y-m-d', strtotime($article['published_at'])) ?>
                </span>
                <span class = "category">
                    <a href = "/category/<?= h_url($article['category_slug'] ?? '') ?>">
                        <?= htmlspecialchars($article['category_name']) ?>
                    </a>
                </span>
                <span class = "views">
                    👁️ <?= number_format($article['views'] + 1) ?> مشاهدة
                </span>
            </div>
            
            <!-- صورة المقال الرئيسية -->
            <?php if (!empty($article['image'])): ?>
            <div class = "article-image">
                <img src = "<?= h_url($article['image'] ?? '') ?>" alt = "<?= h($article['title'] ?? '') ?>">
            </div>
            <?php endif; ?>
        </header>
        
        <!-- محتوى المقال -->
        <div class = "article-content">
            <?= gdy_sanitize_basic_html($article['content'] ?? '') ?> <!-- قد يكون HTML من محرر نصي -->
        </div>
        
        <!-- الكلمات المفتاحية -->
        <?php if (!empty($article['keywords'])): ?>
        <div class = "article-keywords">
            <?php 
            $keywords = explode(',', $article['keywords']);
            foreach ($keywords as $keyword):
            ?>
                <span class = "keyword"><?= h(trim((string)$keyword)) ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- مقالات ذات صلة -->
        <?php if (!empty($related)): ?>
        <section class = "related-articles">
            <h3>مقالات ذات صلة</h3>
            <div class = "related-grid">
                <?php foreach ($related as $relatedArticle): ?>
                <article class = "related-item">
                    <a href = "/news/id/<?= (int)($relatedArticle['id'] ?? 0) ?>">
                        <?php if (!empty($relatedArticle['image'])): ?>
                        <img src = "<?= h_url($relatedArticle['image'] ?? '') ?>" alt = "<?= h($relatedArticle['title'] ?? '') ?>">
                        <?php endif; ?>
                        <h4><?= htmlspecialchars($relatedArticle['title']) ?></h4>
                    </a>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- التعليقات -->
        <section class = "comments-section">
            <h3>التعليقات (<?= count($comments) ?>)</h3>
            
            <!-- نموذج إضافة تعليق -->
            <?php if ($userLoggedIn): ?>
	            <form class = "comment-form" method = "POST" action = "<?= h(($baseUrl ?: '') . '/comment/add') ?>" data-gdy-once="1">
                <?php if (function_exists('csrf_field')) { csrf_field(); } ?>
                <input type = "hidden" name = "news_id" value = "<?= (int)$id ?>">
                <textarea name = "content" required placeholder = "أضف تعليقك..."></textarea>
                <button type = "submit">نشر التعليق</button>
            </form>
            <?php else: ?>
	            <p>يجب <a href = "<?= h(($baseUrl ?: '') . '/login') ?>">تسجيل الدخول</a> لإضافة تعليق</p>
            <?php endif; ?>
            
            <!-- قائمة التعليقات -->
            <div class = "comments-list">
                <?php foreach ($comments as $comment): ?>
                <div class = "comment">
                    <div class = "comment-author">
                        <img src = "<?= h_url($comment['avatar'] ?? '') ?>" alt = "<?= h($comment['username'] ?? '') ?>">
                        <strong><?= h($comment['username'] ?? '') ?></strong>
                        <span class = "comment-date">
                            <?= date('Y-m-d H:i', strtotime($comment['created_at'])) ?>
                        </span>
                    </div>
                    <div class = "comment-content">
                        <?= nl2br(htmlspecialchars($comment['content'])) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </article>
</body>
</html>