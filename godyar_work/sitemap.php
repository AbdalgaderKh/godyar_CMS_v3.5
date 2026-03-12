<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/xml; charset=utf-8');

$base = function_exists('base_url') ? rtrim((string)base_url(), '/') : 'https://example.com';

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

try {
    $stmt = $pdo->query("
        SELECT slug, updated_at, published_at
        FROM news
        WHERE status = 'published'
        ORDER BY id DESC
        LIMIT 5000
    ");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $slug = trim((string)($row['slug'] ?? ''));
        if ($slug === '') continue;

        $url = $base . '/news/' . rawurlencode($slug);
        $lastmod = (string)($row['updated_at'] ?: $row['published_at'] ?: '');

        echo '<url>';
        echo '<loc>' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '</loc>';
        if ($lastmod !== '') {
            echo '<lastmod>' . date('c', strtotime($lastmod)) . '</lastmod>';
        }
        echo '<changefreq>daily</changefreq>';
        echo '<priority>0.8</priority>';
        echo '</url>';
    }
} catch (Throwable $e) {
}

echo '</urlset>';