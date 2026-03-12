<?php

if (!function_exists('h')) {
    function h($v): string {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

$cspNonce = $cspNonce ?? (defined('GDY_CSP_NONCE') ? (string) GDY_CSP_NONCE : '');
$pageScripts = $pageScripts ?? '';

$__siteLogo = '';
if (function_exists('site_setting')) {
    $__pc = 0;
    try {
        $__rf = new ReflectionFunction('site_setting');
        $__pc = $__rf->getNumberOfParameters();
    } catch (Exception $__e) {
        $__pc = 0;
    }

    if ($__pc >= 2) {
        global $pdo;
        if (isset($pdo) && ($pdo instanceof PDO)) {
            $__siteLogo = (string)site_setting($pdo, 'site_logo', '');
            if ($__siteLogo === '') {
                $__siteLogo = (string)site_setting($pdo, 'site.logo', '');
            }
        }
    } else {
        $__siteLogo = (string)site_setting('site_logo');
        if ($__siteLogo === '') {
            $__siteLogo = (string)site_setting('site.logo');
        }
    }
}
?>
<footer class="gdy-admin-footer" aria-label="footer">
  <div class="gdy-admin-footer__left">
    <span class="gdy-admin-footer__logo" aria-hidden="true">
      <?php if ($__siteLogo !== ''): ?>
        <img src="<?php echo h($__siteLogo); ?>" alt="">
      <?php else: ?>
        <span style="font-weight:900;font-size:.95rem;opacity:.7;">G</span>
      <?php endif; ?>
    </span>

    <div style="display:flex;flex-direction:column;line-height:1.1;">
      <span class="gdy-admin-footer__brand">Godyar News Platform</span>
      <span class="gdy-admin-footer__muted">© <?php echo date('Y'); ?> جميع الحقوق محفوظة</span>
    </div>
  </div>

  <div class="gdy-admin-badge">
    Godyar News Platform <?php echo defined('GDY_VERSION') ? h((string)GDY_VERSION) : 'v3.5.0-stable'; ?>
  </div>
</footer>

<?php if ($pageScripts !== ''): ?>
  <?php echo $pageScripts; ?>
<?php endif; ?>

</body>
</html>