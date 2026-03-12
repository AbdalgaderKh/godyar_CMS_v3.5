<?php

require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/rss+xml; charset=UTF-8');

$cacheFile = __DIR__ . '/../cache/rss.xml';
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

$siteName = (string)($GLOBALS['siteSettings']['site_name'] ?? $GLOBALS['site_name'] ?? 'Godyar News');
$siteDesc = (string)($GLOBALS['siteSettings']['site_description'] ?? '');

$items = [];
try {
    if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
        
        $pdo = $GLOBALS['pdo'];
        
        $sql = "SELECT title, slug, COALESCE(published_at, updated_at, created_at) AS dt, excerpt, summary, description FROM news WHERE COALESCE(status,'published')='published' ORDER BY dt DESC LIMIT 50";
        $stmt = $pdo->query($sql);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $title = (string)($row['title'] ?? '');
            $slug  = (string)($row['slug'] ?? '');
            if ($title === '' || $slug === '') continue;
            $dt = (string)($row['dt'] ?? '');
            $desc = (string)($row['excerpt'] ?? $row['summary'] ?? $row['description'] ?? '');
            $link = ($base ?: '') . '/news/' . rawurlencode($slug);
            $items[] = [
                'title' => $title,
                'link' => $link,
                'guid' => $link,
                'pubDate' => $dt ? date(DATE_RSS, strtotime($dt)) : date(DATE_RSS),
                'description' => $desc,
            ];
        }
    }
} catch (Throwable $e) {
    
}

$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
$xml .= "<rss version=\"2.0\">\n";
$xml .= "  <channel>\n";
$xml .= "    <title>" . htmlspecialchars($siteName, ENT_QUOTES | ENT_XML1, 'UTF-8') . "</title>\n";
$xml .= "    <link>" . htmlspecialchars(($base ?: '') . '/', ENT_QUOTES | ENT_XML1, 'UTF-8') . "</link>\n";
$xml .= "    <description>" . htmlspecialchars($siteDesc ?: $siteName, ENT_QUOTES | ENT_XML1, 'UTF-8') . "</description>\n";
$xml .= "    <language>" . htmlspecialchars((string)($GLOBALS['siteSettings']['site_lang'] ?? 'ar'), ENT_QUOTES | ENT_XML1, 'UTF-8') . "</language>\n";
$xml .= "    <lastBuildDate>" . date(DATE_RSS) . "</lastBuildDate>\n";

foreach ($items as $it) {
    $xml .= "    <item>\n";
    $xml .= "      <title>" . htmlspecialchars($it['title'], ENT_QUOTES | ENT_XML1, 'UTF-8') . "</title>\n";
    $xml .= "      <link>" . htmlspecialchars($it['link'], ENT_QUOTES | ENT_XML1, 'UTF-8') . "</link>\n";
    $xml .= "      <guid isPermaLink=\"true\">" . htmlspecialchars($it['guid'], ENT_QUOTES | ENT_XML1, 'UTF-8') . "</guid>\n";
    $xml .= "      <pubDate>" . htmlspecialchars($it['pubDate'], ENT_QUOTES | ENT_XML1, 'UTF-8') . "</pubDate>\n";
    if (trim($it['description']) !== '') {
        $xml .= "      <description><![CDATA[" . $it['description'] . "]]></description>\n";
    }
    $xml .= "    </item>\n";
}

$xml .= "  </channel>\n";
$xml .= "</rss>\n";

@file_put_contents($cacheFile, $xml);

echo $xml;
