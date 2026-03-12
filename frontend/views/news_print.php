<?php

$title = (string)($post['title'] ?? '');
$content = (string)($post['content'] ?? ($post['body'] ?? ''));
$dateRaw = (string)($post['date'] ?? ($post['published_at'] ?? ($post['created_at'] ?? '')));
$ts = $dateRaw !== '' ? gdy_strtotime($dateRaw) : false;
$dateFmt = $ts ? date('Y/m/d', $ts) : ($dateRaw !== '' ? $dateRaw : '');

$autoprint = isset($_GET['autoprint']) && (string)$_GET['autoprint'] === '1';
$hideQr = isset($_GET['noqr']) && (string)$_GET['noqr'] === '1';

$baseUrl = rtrim((string)$baseUrl, '/');
$cspNonce = defined('GDY_CSP_NONCE') ? (string)GDY_CSP_NONCE : '';
$nonceAttr = ($cspNonce !== '') ? ' nonce="' . htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8') . '"' : '';
$articleUrlFull = (string)$articleUrlFull;

$site = isset($GLOBALS['site_settings']) && is_array($GLOBALS['site_settings']) ? $GLOBALS['site_settings'] : [];
$siteName = (string)($site['site_name'] ?? 'Godyar News');
$siteDesc = (string)($site['site_desc'] ?? 'منصة إخبارية متكاملة');
$logoRaw = (string)($site['site_logo'] ?? '');

$logoUrl = '';
if ($logoRaw !== '') {
  
  if (preg_match('~^https?://~i', $logoRaw)) {
    $logoUrl = $logoRaw;
  } else {
    $logoUrl = $baseUrl . '/' .ltrim($logoRaw, '/');
  }
}

$cover = (string)($post['image'] ?? ($post['cover'] ?? ($post['thumb'] ?? '')));
$coverUrl = '';
if ($cover !== '') {
  if (preg_match('~^https?://~i', $cover)) {
    $coverUrl = $cover;
  } else {
    $coverUrl = $baseUrl . '/' .ltrim($cover, '/');
  }
}

function gdy_strip_print_highlights(string $html): string
{
  
  if (function_exists('iconv')) {
    $fixed = gdy_iconv('UTF-8', 'UTF-8//IGNORE', $html);
    if (is_string($fixed) && $fixed !== '') {
      $html = $fixed;
    }
  }

  
  $tmp = preg_replace('~<\s*mark\b~i', '<span', $html);
  if ($tmp !== null) { $html = $tmp; }
  $tmp = preg_replace('~</\s*mark\s*>~i', '</span>', $html);
  if ($tmp !== null) { $html = $tmp; }

  
  $tmp = preg_replace("~\\sbgcolor\\s*=\\s*(\"|').*?\\1~i", '', $html);
  if ($tmp !== null) { $html = $tmp; }

  
  $tmp = preg_replace_callback("~\\sstyle\\s*=\\s*(\"|')(.*?)\\1~is", function ($m) {
    $quote = $m[1];
    $style = (string)($m[2] ?? '');

    
    $style = preg_replace('~\bbackground(?:-color)?\s*:\s*[^;]+;?~i', '', $style) ?? $style;
    $style = preg_replace('~\bbox-shadow\s*:\s*[^;]+;?~i', '', $style) ?? $style;
    $style = preg_replace('~\bfilter\s*:\s*[^;]+;?~i', '', $style) ?? $style;
    $style = preg_replace('~\bopacity\s*:\s*[^;]+;?~i', '', $style) ?? $style;

    
    $style = trim((string)$style);
    $style = preg_replace('~;{2,}~', ';', $style) ?? $style;
    $style = trim((string)$style, " \t\n\r\0\x0B;");

    if ($style === '') {
      return '';
    }
    return ' style=' . $quote . $style . $quote;
  }, $html);
  if ($tmp !== null) { $html = $tmp; }

  return (string)$html;
}

$contentClean = gdy_strip_print_highlights($content);

$tmpImg = preg_replace('~<img loading="lazy" decoding="async"\b(?![^>]*\bloading=)([^>]*?)>~i', '<img loading="lazy" decoding="async"$1>', (string)$contentClean);
if ($tmpImg !== null) { $contentClean = $tmpImg; }
$catName = is_array($category) ? (string)($category['name'] ?? '') : '';
$catSlug = is_array($category) ? (string)($category['slug'] ?? '') : '';
$catUrl = ($catSlug !== '') ? ($baseUrl . '/category' .rawurlencode($catSlug)) : '';

?><!doctype html>
<html lang = "ar" dir = "rtl">
<head><meta charset = "utf-8"    />
<meta name = "viewport" content = "width=device-width, initial-scale=1"    />
  <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?> — <?php echo htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'); ?></title>

  <!-- Optional Arabic webfont (falls back gracefully if blocked) -->
  </head>
<body>
  <div class = "paper">

    <div class = "mast">
      <div class = "brand">
        <?php if ($logoUrl !== ''): ?>
          <img loading="lazy" decoding="async" class = "logo" src = "<?php echo htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8'); ?>" alt = "logo"/>
        <?php endif; ?>
        <div class = "brandText">
          <div class = "siteName"><?php echo htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'); ?></div>
          <div class = "siteDesc"><?php echo htmlspecialchars($siteDesc, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
      </div>

      <div class = "meta">
        <?php if ($catName !== ''): ?>
          <div><strong>القسم:</strong> <?php echo htmlspecialchars($catName, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($dateFmt !== ''): ?>
          <div><strong>التاريخ:</strong> <?php echo htmlspecialchars($dateFmt, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
      </div>
    </div>

    <h1><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>

    <div class = "subbar">
      <div>
        <a href = "<?php echo htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8'); ?>">الرئيسية</a>
        <?php if ($catName !== '' && $catUrl !== ''): ?>
          <span class = "dot">•</span>
          <a href = "<?php echo htmlspecialchars($catUrl, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($catName, ENT_QUOTES, 'UTF-8'); ?></a>
        <?php endif; ?>
      </div>
      <div class = "url"><?php echo htmlspecialchars($articleUrlFull, ENT_QUOTES, 'UTF-8'); ?></div>
    </div>

    <?php if ($coverUrl !== ''): ?>
      <div class = "cover"><img loading="lazy" decoding="async" src = "<?php echo htmlspecialchars($coverUrl, ENT_QUOTES, 'UTF-8'); ?>" alt = "cover"/></div>
    <?php endif; ?>

    <?php if (!$hideQr): ?>
      <div class = "qrline">
        <div class = "qr">
          <div>
            <small>للوصول السريع للخبر عبر الهاتف</small>
            <small class = "badge">امسح QR أو افتح الرابط</small>
          </div>
        </div>
        <div class = "url"><?php echo htmlspecialchars($articleUrlFull, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    <?php endif; ?>

    <div class = "content">
      <?php echo $contentClean ; ?>
    </div>

    <div class = "foot">
      <div class = "left"><?php echo htmlspecialchars($articleUrlFull, ENT_QUOTES, 'UTF-8'); ?></div>
      <div>نسخة للطباعة — <span class = "badge">GDY_BUILD: v9.0</span></div>
    </div>

  </div>

  <?php if ($autoprint): ?>
  <?php endif; ?>
</body>
</html>