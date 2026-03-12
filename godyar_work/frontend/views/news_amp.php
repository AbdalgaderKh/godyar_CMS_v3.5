<?php

$baseUrl = function_exists('base_url')
    ? rtrim((string)base_url(), '/')
    : (defined('BASE_URL') ? rtrim((string)BASE_URL, '/') : '');

$canonicalPath = (string)($canonical ?? '');
$canonicalUrl = (preg_match('~^https?://~i', $canonicalPath))
    ? $canonicalPath
    : (rtrim($baseUrl, '/') . ($canonicalPath !== '' && $canonicalPath[0] === '/' ? $canonicalPath : '/' .ltrim($canonicalPath, '/')));
?>
<!doctype html>
<html ⚡ lang = "ar" dir = "rtl">
<head><meta charset = "utf-8">
<title><?php echo htmlspecialchars($news['title'] ?? ''); ?> | AMP</title>
	  <link rel = "canonical" href = "<?php echo htmlspecialchars($canonicalUrl); ?>">
  <meta name = "viewport" content = "width=device-width,minimum-scale=1,initial-scale=1">
  </head>
<body>
  <header>
	    <a href = "<?php echo htmlspecialchars(rtrim($baseUrl, '/') . '/'); ?>" style = "text-decoration:none;color:#6D28D9;font-weight:700">Godyar</a>
  </header>
  <article>
    <h1><?php echo htmlspecialchars($news['title'] ?? ''); ?></h1>
	    <?php $d = $news['published_at'] ?? ($news['publish_at'] ?? ($news['created_at'] ?? 'now')); ?>
	    <div class = "meta"><?php echo htmlspecialchars(date('Y-m-d', strtotime((string)$d))); ?></div>
    <?php if (empty($news['featured_image']) === false): ?>
	      <amp-img src = "<?php echo htmlspecialchars(rtrim($baseUrl, '/') . '/img.php?src=' .rawurlencode((string)$news['featured_image']) . '&w=1200'); ?>"
               width = "1200" height = "675" layout = "responsive"
               alt = "<?php echo htmlspecialchars($news['title'] ?? ''); ?>"></amp-img>
    <?php endif; ?>
    <div class = "content"><?php echo gdy_sanitize_basic_html($news['content'] ?? ''); ?></div>
  </article>
</body>
</html>
