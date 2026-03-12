<?php

if (defined('GDY_FOOTER_RENDERED')) { return; }
define('GDY_FOOTER_RENDERED', true);

if (!function_exists('h')) {
  function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('u')) {
  function u(string $url): string {
    $url = trim($url);
    if ($url === '') return '';
    if (preg_match('~^https?://~i', $url)) return h($url);
    if (preg_match('~^(//|javascript:)~i', $url)) return '#';
    if ($url[0] === '/') {
      $base = function_exists('base_url') ? rtrim((string)base_url(), '/') : '';
      return h($base . $url);
    }
    return h($url);
  }
}

if (!function_exists('__')) {
  function __(string $key, array $vars = []): string {
    $out = $key;
    foreach ($vars as $k => $v) {
      $out = str_replace('{' . $k . '}', (string)$v, $out);
    }
    return $out;
  }
}

if (!function_exists('asset_url')) {
  function asset_url(string $path = ''): string {
    $path = ltrim($path, '/');
    $base = function_exists('base_url') ? rtrim((string)base_url(), '/') : '';
    return $base . '/' . $path;
  }
}

$baseUrl = function_exists('base_url') ? rtrim((string)base_url(), '/') : '';
$_gdy_lang = function_exists('gdy_lang') ? (string)gdy_lang() : (string)($_SESSION['lang'] ?? 'ar');
$_gdy_lang = trim($_gdy_lang, '/');
$_gdy_navBaseUrl = $baseUrl . '/' . $_gdy_lang;

$siteSettings = [];
if (class_exists('HomeController')) {
  try {
    $tmp = HomeController::getSiteSettings();
    $siteSettings = is_array($tmp) ? $tmp : [];
  } catch (Throwable $e) {
    $siteSettings = [];
  }
}

$siteName    = (string)__('brand.name', (string)($siteSettings['site_name'] ?? 'Godyar News'));
$siteTagline = (string)__('brand.tagline', (string)($siteSettings['site_tagline'] ?? 'منصة إخبارية متكاملة'));
$desc        = trim((string)($siteSettings['site_description'] ?? ''));

$logoRaw = trim((string)($siteSettings['site_logo'] ?? ''));
$logoUrl = '';
if ($logoRaw !== '') {
  $logoUrl = preg_match('~^https?://~i', $logoRaw) ? $logoRaw : ($baseUrl . '/' . ltrim($logoRaw, '/'));
}

$siteEmail = (string)($siteSettings['site_email'] ?? '');
$sitePhone = (string)($siteSettings['site_phone'] ?? '');
$siteAddr  = (string)($siteSettings['site_address'] ?? '');

$iconsSprite = asset_url('assets/icons/godyar-icons.svg');
$gdyIsUser = (!empty($_SESSION['user']) || !empty($_SESSION['user_id']) || !empty($_SESSION['user_email']));

$getSetting = function(string $key): string {
  if (function_exists('settings_get')) {
    return trim((string)settings_get($key, ''));
  }
  return '';
};

$social = [
  'facebook'  => trim((string)\App\Services\Settings::get('social.facebook', (string)($siteSettings['social_facebook'] ?? $siteSettings['social.facebook'] ?? ''))),
  
  'twitter'   => trim((string)\App\Services\Settings::get('social.twitter', (string)($siteSettings['social_twitter'] ?? $siteSettings['social_x'] ?? $siteSettings['social.twitter'] ?? ''))),
  'instagram' => trim((string)\App\Services\Settings::get('social.instagram', (string)($siteSettings['social_instagram'] ?? $siteSettings['social.instagram'] ?? ''))),
  'youtube'   => trim((string)\App\Services\Settings::get('social.youtube', (string)($siteSettings['social_youtube'] ?? $siteSettings['social.youtube'] ?? ''))),
  'telegram'  => trim((string)\App\Services\Settings::get('social.telegram', (string)($siteSettings['social_telegram'] ?? $siteSettings['social.telegram'] ?? ''))),
  'whatsapp'  => trim((string)\App\Services\Settings::get('social.whatsapp', (string)($siteSettings['social_whatsapp'] ?? $siteSettings['social.whatsapp'] ?? ''))),
];

if (!function_exists('gdy_social_normalize')) {
  function gdy_social_normalize(string $key, string $val): string {
    $val = trim($val);
    if ($val === '') return '';
    if (preg_match('~^https?://~i', $val)) return $val;
    if (preg_match('~^(//|javascript:)~i', $val)) return '';
    if ($val !== '' && $val[0] === '@') $val = ltrim($val, '@');
    if (preg_match('~^(t\.me|wa\.me|instagram\.com|x\.com|twitter\.com|facebook\.com|youtube\.com)/~i', $val)) {
      return 'https://' . $val;
    }
    $k = strtolower($key);
    if ($k === 'whatsapp') {
      $digits = preg_replace('~[^0-9]~', '', $val);
      if ($digits !== '') return 'https://wa.me/' . $digits;
    }
    if ($k === 'telegram') {
      return 'https://t.me/' . rawurlencode($val);
    }
    if ($k === 'twitter') {
      return 'https://x.com/' . rawurlencode($val);
    }
    if ($k === 'instagram') {
      return 'https://www.instagram.com/' . rawurlencode($val);
    }
    if ($k === 'facebook') {
      if (preg_match('~^(profile\.php\?|pages/|groups/)~i', $val)) return 'https://www.facebook.com/' . $val;
      return 'https://www.facebook.com/' . rawurlencode($val);
    }
    if ($k === 'youtube') {
      if (preg_match('~^(@|channel/|c/|user/|watch\?|shorts/)~i', $val)) return 'https://www.youtube.com/' . $val;
      return 'https://www.youtube.com/@' . rawurlencode($val);
    }
    if ($k === 'linkedin') {
      return 'https://www.linkedin.com/' . ltrim($val, '/');
    }
    if ($k === 'tiktok') {
      $v = $val;
      if ($v !== '' && $v[0] !== '@') $v = '@' . $v;
      return 'https://www.tiktok.com/' . rawurlencode($v);
    }
    return $val;
  }
}

foreach ($social as $k => $v) {
  $social[$k] = gdy_social_normalize($k, (string)$v);
}

if (count(array_filter($social, fn($v) => $v !== '')) === 0) {
  $raw = $siteSettings['social_links'] ?? null;
  $arr = [];
  if (is_string($raw) && trim($raw) !== '') {
    $tmp = json_decode($raw, true);
    $arr = is_array($tmp) ? $tmp : [];
  } elseif (is_array($raw)) {
    $arr = $raw;
  }

  if (!empty($arr)) {
    foreach ($arr as $item) {
      if (!is_array($item)) continue;
      $url = trim((string)($item['url'] ?? $item['link'] ?? ''));
      if ($url === '') continue;

      $key = strtolower(trim((string)($item['icon'] ?? $item['key'] ?? $item['name'] ?? '')));
      $key = str_replace([' ', '-'], '_', $key);
      
      if (in_array($key, ['x', 'twitter_x', 'twitter'], true)) $key = 'twitter';
      if (in_array($key, ['fb', 'facebook'], true)) $key = 'facebook';
      if (in_array($key, ['insta', 'instagram'], true)) $key = 'instagram';
      if (in_array($key, ['yt', 'youtube'], true)) $key = 'youtube';
      if (in_array($key, ['wa', 'whatsapp'], true)) $key = 'whatsapp';
      if (in_array($key, ['tg', 'telegram'], true)) $key = 'telegram';
      if (in_array($key, ['linkedin', 'linked_in'], true)) $key = 'linkedin';
      if (in_array($key, ['tiktok', 'tik_tok'], true)) $key = 'tiktok';

      
      if (!isset($social[$key])) {
        $social[$key] = $url;
      }
    }
  }
}

?>

<?php if (defined('GDY_HEADER_RENDERED')): ?>
      </div>
    </div>
  </main>
<?php endif; ?>

<footer class="gdy-footer">
  <div class="gdy-footer-top">
    <div class="container">
      <div class="gdy-footer-grid">

        <section class="gdy-footer-card"><h2 class="visually-hidden">Section</h2>
          <a class="gdy-footer-brand__link" href="<?php echo u($_gdy_navBaseUrl . '/'); ?>" aria-label="<?php echo h($siteName); ?>">
            <span class="gdy-footer-brand__logo" aria-hidden="true">
              <?php if ($logoUrl !== ''): ?>
                <img src="<?php echo h($logoUrl); ?>" alt="<?php echo h($siteName); ?>" loading="lazy" decoding="async">
              <?php else: ?>
                <span class="gdy-footer-brand__logo-fallback"><?php echo h(mb_substr($siteName, 0, 1)); ?></span>
              <?php endif; ?>
            </span>
            <div><div><?php echo h($siteName); ?></div>
              <div><?php echo h($siteTagline); ?></div>
            </div>
          </a>
          <!-- Badge logo (theme-aware contrast) -->
          <div class="gdy-footer-badge" aria-hidden="true">
            <?php if ($logoUrl !== ''): ?>
              <img src="<?php echo h($logoUrl); ?>" alt="" loading="lazy" decoding="async">
            <?php else: ?>
              <span class="gdy-footer-brand__logo-fallback"><?php echo h(mb_substr($siteName, 0, 1)); ?></span>
            <?php endif; ?>
          </div>

          <?php if ($desc !== '' && $desc !== $siteTagline): ?>
            <div><p><?php echo h($desc); ?></p></div>
          <?php endif; ?>
        </section>

        <section class="gdy-footer-card">
          <h4><?= h(__('nav.quick_links', 'روابط سريعة')) ?></h4>
          <ul class="gdy-footer-links">
            <li><a href="<?php echo u($_gdy_navBaseUrl . '/page/about'); ?>"><?= h(__('footer.about', 'من نحن')) ?></a></li>
            <li><a href="<?php echo u($_gdy_navBaseUrl . '/page/privacy'); ?>"><?= h(__('footer.privacy', 'سياسة الخصوصية')) ?></a></li>
            <li><a href="<?php echo u($_gdy_navBaseUrl . '/page/terms'); ?>"><?= h(__('footer.terms', 'الشروط والأحكام')) ?></a></li>
            <li><a href="<?php echo u($_gdy_navBaseUrl . '/contact'); ?>"><?= h(__('footer.contact', 'اتصل بنا')) ?></a></li>
            <li><a href="<?php echo u($_gdy_navBaseUrl . '/sitemap.xml'); ?>"><?= h(__('footer.sitemap', 'خريطة الموقع')) ?></a></li>
          </ul>
        </section>

        <section class="gdy-footer-card">
          <h4><?= h(__('footer.follow', 'تواصل معنا')) ?></h4>
          <div>
            <?php if ($siteEmail !== ''): ?>
	              <div><svg class="gdy-icon ms-1" aria-hidden="true"><use href="<?php echo h($iconsSprite); ?>#mail" xlink:href="<?php echo h($iconsSprite); ?>#mail"></use></svg> <?php echo h($siteEmail); ?></div>
            <?php endif; ?>
            <?php if ($sitePhone !== ''): ?>
	              <div><svg class="gdy-icon ms-1" aria-hidden="true"><use href="<?php echo h($iconsSprite); ?>#phone" xlink:href="<?php echo h($iconsSprite); ?>#phone"></use></svg> <?php echo h($sitePhone); ?></div>
            <?php endif; ?>
            <?php if ($siteAddr !== ''): ?>
	              <div><svg class="gdy-icon ms-1" aria-hidden="true"><use href="<?php echo h($iconsSprite); ?>#more-h" xlink:href="<?php echo h($iconsSprite); ?>#more-h"></use></svg> <?php echo h($siteAddr); ?></div>
            <?php endif; ?>
          </div>

          <div class="gdy-social">
            <?php
              $iconMap = [
                'facebook'=>'facebook',
                'twitter'=>'twitter',
                'instagram'=>'instagram',
                'youtube'=>'youtube',
                'telegram'=>'telegram',
                'whatsapp'=>'whatsapp',
                'linkedin'=>'linkedin',
                'tiktok'=>'tiktok',
              ];
              $printed = 0;
              foreach ($social as $k => $v) {
                if ($v === '') continue;
                $printed++;
                $iconId = $iconMap[$k] ?? 'more-h';
	                $ref = h($iconsSprite) . '#' . h($iconId);
	              $label = $k;
	              if ($k === 'twitter') $label = 'X';
	              echo '<a href="' . u($v) . '" target="_blank" rel="noopener noreferrer" aria-label="' . h($label) . '">' 
	                  . '<svg class="gdy-icon" aria-hidden="true"><use href="' . $ref . '" xlink:href="' . $ref . '"></use></svg>'
                  . '</a>';
              }
              if ($printed === 0) {
                echo '<div class="gdy-footer-muted">' . h(__('footer.social_hint', 'أضف روابط التواصل الاجتماعي من لوحة التحكم')) . '</div>';
              }
            ?>
          </div>
        </section>

      </div>
    </div>
  </div>

  <div class="gdy-footer-bottom">
    <div class="container">
      <div class="inner">
        <div>© <?php echo (int)date('Y'); ?> <?php echo h(__('footer.copyright_line', $siteName . " — جميع الحقوق محفوظة")); ?></div>
        <div><?= h(__('footer.designed_by', 'تصميم وتطوير')) ?>: <span>Godyar</span></div>
      </div>
    </div>
  </div>

  <button type="button" id="gdyBackTop" class="gdy-backtop" aria-label="<?= h(__('action.back_to_top', 'العودة للأعلى')) ?>" title="<?= h(__('action.back_to_top', 'العودة للأعلى')) ?>">
	    <svg class="gdy-icon" aria-hidden="true"><use href="<?php echo h($iconsSprite); ?>#arrow-up" xlink:href="<?php echo h($iconsSprite); ?>#arrow-up"></use></svg>
  </button>
</footer>

<nav class="gdy-mobile-bar" id="gdyMobileBar" aria-label="<?= h(__('nav.mobile', 'التنقل')) ?>">
  <a class="mb-item" href="<?php echo u($_gdy_navBaseUrl . '/'); ?>" data-tab="home" aria-label="<?= h(__('nav.home', 'الرئيسية')) ?>">
	    <svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="<?php echo h($iconsSprite); ?>#home" xlink:href="<?php echo h($iconsSprite); ?>#home"></use></svg><span><?= h(__('nav.home', 'الرئيسية')) ?></span>
  </a>
  <button class="mb-item" type="button" data-action="cats" data-tab="cats" aria-label="<?= h(__('nav.sections', 'الأقسام')) ?>">
	    <svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="<?php echo h($iconsSprite); ?>#menu" xlink:href="<?php echo h($iconsSprite); ?>#menu"></use></svg><span><?= h(__('nav.sections', 'الأقسام')) ?></span>
  </button>
  <a class="mb-item" href="<?php echo u($_gdy_navBaseUrl . '/saved'); ?>" data-tab="saved" aria-label="<?= h(__('nav.saved', 'محفوظاتي')) ?>">
	    <svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="<?php echo h($iconsSprite); ?>#bookmark" xlink:href="<?php echo h($iconsSprite); ?>#bookmark"></use></svg><span><?= h(__('nav.saved', 'محفوظاتي')) ?></span>
  </a>
  <?php if ($gdyIsUser): ?>
    <a class="mb-item" href="<?php echo u($_gdy_navBaseUrl . '/profile.php'); ?>" data-tab="profile" aria-label="<?= h(__('nav.account', 'حسابي')) ?>">
	      <svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="<?php echo h($iconsSprite); ?>#user" xlink:href="<?php echo h($iconsSprite); ?>#user"></use></svg><span><?= h(__('nav.account', 'حسابي')) ?></span>
    </a>
  <?php else: ?>
    <a class="mb-item" href="<?php echo u($_gdy_navBaseUrl . '/login'); ?>" data-tab="login" aria-label="<?= h(__('nav.login', 'دخول')) ?>">
      <svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="<?php echo h($iconsSprite); ?>#user"></use></svg><span><?= h(__('nav.login', 'دخول')) ?></span>
    </a>
  <?php endif; ?>
  <button class="mb-item" type="button" data-action="theme" aria-label="<?= h(__('nav.dark_mode', 'الوضع الليلي')) ?>">
    <svg class="gdy-icon" aria-hidden="true" focusable="false"><use href="<?php echo h($iconsSprite); ?>#moon"></use></svg><span><?= h(__('nav.dark_mode_short', 'ليلي')) ?></span>
  </button>
</nav>

<?php
  
    
  $pushEnabled = function_exists('settings_get') ? (string)settings_get('push.enabled', '0') : '0';
  $vapidPublic = function_exists('settings_get') ? (string)settings_get('push.vapid_public', '') : '';
  $cspNonce = defined('GDY_CSP_NONCE') ? (string)GDY_CSP_NONCE : '';
  echo '<script' . ($cspNonce !== '' ? ' nonce="' . h($cspNonce) . '"' : '') . '>';
  echo 'window.GDY_PUSH_ENABLED=' . json_encode(($pushEnabled === '1'), JSON_UNESCAPED_SLASHES) . ';';
  echo 'window.GDY_VAPID_PUBLIC_KEY=' . json_encode($vapidPublic, JSON_UNESCAPED_SLASHES) . ';';
  echo 'window.GDY_ASSET_VER=' . json_encode((string)($siteSettings['assets_version'] ?? (defined('GODYAR_VERSION') ? GODYAR_VERSION : '20260226')), JSON_UNESCAPED_SLASHES) . ';';
  echo '</script>' . "\n";

  $bundle = '/assets/js/godyar.bundle.js';
  $ver = preg_replace('~[^0-9A-Za-z._-]~','',(string)($siteSettings['assets_version'] ?? (defined('GODYAR_VERSION') ? GODYAR_VERSION : '20260226')));
  if ($ver === '') $ver = '20260226';
  echo '<script defer src="' . h($baseUrl . $bundle . '?v=' . $ver) . '"></script>' . "\n";
  
  $rt = '/assets/js/news-report-tools.js';
  echo '<script defer src="' . h($baseUrl . $rt . '?v=' . $ver) . '"></script>' . "\n";
  $fix = '/assets/js/push-fix.js';
  echo '<script defer src="' . h($baseUrl . $fix . '?v=' . $ver) . '"></script>' . "\n";
?>

<div id="gdy-push-toast" class="gdy-push-toast" role="dialog" aria-live="polite" role="region" role="region" role="region" role="region" role="region" aria-label="&#039; . htmlspecialchars($label, ENT_QUOTES, &#039;UTF-8&#039;) . &#039;">
  <div class="gdy-push-toast__title"><?= h(__('push.title', 'تفعيل إشعارات الأخبار')) ?></div>
  <div class="gdy-push-toast__desc"><?= h(__('push.desc', 'وصل تنبيه لأهم الأخبار على جهازك . يمكنك إيقافها في أي وقت .')) ?></div>
  <div class="gdy-push-toast__actions">
    <button type="button" class="gdy-btn gdy-btn-primary" data-gdy-push-enable><?= h(__('push.enable', 'تفعيل')) ?></button>
    <button type="button" class="gdy-btn gdy-btn-ghost" data-gdy-push-later><?= h(__('push.later', 'لاحقاً')) ?></button>
  </div>
</div>

</body>
</html>
