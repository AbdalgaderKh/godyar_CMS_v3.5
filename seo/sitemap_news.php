<?php

require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/xml; charset=UTF-8');

$cacheFile = __DIR__ . '/../cache/sitemap-news.xml';
$cacheTtl  = 300; 

if (is_file($cacheFile) && (time() - (int)@filemtime($cacheFile)) < $cacheTtl) {
    readfile($cacheFile);
    exit;
}

$base = rtrim((string)base_url(), '/');
if ($base === '') {
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $base = $host !== '' ? ($scheme . '://' . $host) : '';
}

$siteName = (string)(get_setting('site_name') ?? 'Godyar News');
$lang = (string)(get_setting('site_lang') ?? 'ar');
if ($lang === '') $lang = 'ar';

$since = date('Y-m-d H:i:s', time() - 48 * 3600);

$items = [];
try {
    if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
        
        $pdo = $GLOBALS['pdo'];
        
        $sql = "SELECT slug, title, COALESCE(published_at, created_at) AS pub
                FROM news
                WHERE COALESCE(status,'published')='published'
                  AND COALESCE(published_at, created_at) >= :since
                ORDER BY pub DESC
                LIMIT 1000";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':since' => $since]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $slug = (string)($row['slug'] ?? '');
            if ($slug === '') continue;
            $title = (string)($row['title'] ?? '');
            $pub = (string)($row['pub'] ?? '');
            $items[] = [
                'loc' => ($base ?: '') . '/news/' . rawurlencode($slug),
                'title' => $title,
                'pub' => $pub,
            ];
        }
    }
} catch (Throwable $e) {
    
}

$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
$xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:news=\"http://www.google.com/schemas/sitemap-news/0.9\">\n";

foreach ($items as $it) {
    $loc = (string)($it['loc'] ?? '');
    $title = (string)($it['title'] ?? '');
    $pub = (string)($it['pub'] ?? '');
    $pubIso = $pub !== '' ? date('c', strtotime($pub)) : date('c');

    $xml .= "  <url>\n";
    $xml .= "    <loc>" . htmlspecialchars($loc, ENT_QUOTES | ENT_XML1, 'UTF-8') . "</loc>\n";
    $xml .= "    <news:news>\n";
    $xml .= "      <news:publication>\n";
    $xml .= "        <news:name>" . htmlspecialchars($siteName, ENT_QUOTES | ENT_XML1, 'UTF-8') . "</news:name>\n";
    $xml .= "        <news:language>" . htmlspecialchars($lang, ENT_QUOTES | ENT_XML1, 'UTF-8') . "</news:language>\n";
    $xml .= "      </news:publication>\n";
    $xml .= "      <news:publication_date>" . htmlspecialchars($pubIso, ENT_QUOTES | ENT_XML1, 'UTF-8') . "</news:publication_date>\n";
    $xml .= "      <news:title>" . htmlspecialchars($title, ENT_QUOTES | ENT_XML1, 'UTF-8') . "</news:title>\n";
    $xml .= "    </news:news>\n";
    $xml .= "  </url>\n";
}

$xml .= "</urlset>\n";

@file_put_contents($cacheFile, $xml);

echo $xml;
