<?php
declare(strict_types=1);

function gdy_trending_news(PDO $pdo, int $limit = 10)
{
    $limit = max(1, min(20, $limit));
    return $pdo->query("
        SELECT id, slug, title, COALESCE(views,0) AS views
        FROM news
        WHERE status = 'published'
        ORDER BY COALESCE(views,0) DESC, id DESC
        LIMIT {$limit}
    ");
}