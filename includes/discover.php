<?php
declare(strict_types=1);

function gdy_discover_articles(PDO $pdo, int $limit = 5)
{
    $limit = max(1, min(20, $limit));
    return $pdo->query("
        SELECT id, slug, title, COALESCE(image, featured_image, image_path, '') AS image
        FROM news
        WHERE status = 'published'
        ORDER BY COALESCE(views,0) DESC, id DESC
        LIMIT {$limit}
    ");
}