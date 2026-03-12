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

$pdo = gdy_pdo_safe();
$limit = isset($limit) && (int)$limit > 0 ? (int)$limit : 4;

$items = [];

if ($pdo instanceof PDO) {
    try {
        $sql = "
            SELECT 
                n .id,
                n .title,
                n .slug,
                n .excerpt,
                n .content,
                n .image,
                n .published_at,
                oa .id AS opinion_author_id,
                oa .name AS author_name,
                oa .page_title AS author_page_title,
                oa .avatar AS author_avatar,
                oa .social_website AS author_website,
                oa .social_twitter AS author_twitter,
                oa .social_facebook AS author_facebook,
                oa .email AS author_email
            FROM news n
            INNER JOIN opinion_authors oa 
                ON n .opinion_author_id = oa .id
            WHERE 
                n .status = 'published'
            ORDER BY n .published_at DESC
            LIMIT :limit
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {
        error_log('Opinion block error: ' . $e->getMessage());
        $items = [];
    }
}

if (!function_exists('gdy_trim_text')) {
    function gdy_trim_text(string $text, int $length = 180): string {
        $text = strip_tags($text);
        if (function_exists('mb_strlen')) {
            if (mb_strlen($text, 'UTF-8') <= $length) {
                return $text;
            }
            return mb_substr($text, 0, $length, 'UTF-8') . '...';
        }
        if (strlen($text) <= $length) {
            return $text;
        }
        return substr($text, 0, $length) . '...';
    }
}
?>

<?php if (!empty($items)) { ?>
<?php $cspNonce = defined('GDY_CSP_NONCE') ? (string)GDY_CSP_NONCE : ''; $nonceAttr = ($cspNonce !== '') ? ' nonce="' . htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>
<div class = "opinion-block">
    <div class = "opinion-block-header">
        <div class = "opinion-block-title">
            <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
            <span>آراء وكتاب</span>
        </div>
    </div>

    <div class = "opinion-items">
        <?php foreach ($items as $row) { ?>
            <article class = "opinion-card">
                <div class = "opinion-author">
                    <div class = "opinion-author-avatar">
                        <?php if (!empty($row['author_avatar'])) { ?>
                            <img loading="lazy" decoding="async" src = "<?php echo htmlspecialchars($row['author_avatar'], ENT_QUOTES, 'UTF-8'); ?>"
                                 alt = "<?php echo htmlspecialchars($row['author_name'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php } else { ?>
                            <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#user"></use></svg>
                        <?php } ?>
                    </div>
                    <div class = "opinion-author-info">
                        <?php if (!empty($row['author_page_title'])) { ?>
                            <div class = "opinion-author-page">
                                <?php echo htmlspecialchars($row['author_page_title'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php } ?>
                        <div class = "opinion-author-name">
                            <?php echo htmlspecialchars($row['author_name'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <div class = "opinion-author-social">
                            <?php if (!empty($row['author_facebook'])) { ?>
                                <a href = "<?php echo htmlspecialchars($row['author_facebook'], ENT_QUOTES, 'UTF-8'); ?>" target = "_blank" title = "فيسبوك">
                                    <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#facebook"></use></svg>
                                </a>
                            <?php } ?>
                            <?php if (!empty($row['author_email'])) { ?>
                                <a href = "mailto:<?php echo htmlspecialchars($row['author_email'], ENT_QUOTES, 'UTF-8'); ?>" title = "البريد الإلكتروني">
                                    <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
                                </a>
                            <?php } ?>
                            <?php if (!empty($row['author_website'])) { ?>
                                <a href = "<?php echo htmlspecialchars($row['author_website'], ENT_QUOTES, 'UTF-8'); ?>" target = "_blank" title = "الموقع الشخصي">
                                    <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#globe"></use></svg>
                                </a>
                            <?php } ?>
                            <?php if (!empty($row['author_twitter'])) { ?>
                                <a href = "<?php echo htmlspecialchars($row['author_twitter'], ENT_QUOTES, 'UTF-8'); ?>" target = "_blank" title = "تويتر">
                                    <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#x"></use></svg>
                                </a>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                    $slug = !empty($row['slug']) ? $row['slug'] : ('news-' . (int)$row['id']);
                    $url = '/news/id/' . (int)$id;
                    ?>
                    $slug = !empty($row['slug']) ? $row['slug'] : ('news-' . (int)$row['id']);
                    $url = '/news/id/' . (int)$id;
                    ?>
                    <a href = "<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </div>

                <div class = "opinion-article-text">
                    <?php
                    $text = $row['excerpt'] ?: $row['content'];
                    echo htmlspecialchars(gdy_trim_text((string)$text, 220), ENT_QUOTES, 'UTF-8');
                    ?>
                </div>

                <div class = "opinion-article-meta">
                    <span>
                        <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
                        <?php echo htmlspecialchars(date('Y-m-d', strtotime((string)$row['published_at'])), ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                    <span>
                        <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
                        قراءة المقال
                    </span>
                </div>
            </article>
        <?php } ?>
    </div>
</div>
<?php } ?>
