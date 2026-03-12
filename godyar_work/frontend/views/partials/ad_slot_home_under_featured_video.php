<?php

$__adHtml = '';
try {
    if (isset($pdo) && $pdo instanceof PDO && class_exists('\Godyar\Services\AdService')) {
        $svc = new \Godyar\Services\AdService($pdo);
        $__adHtml = $svc->render('home_under_featured_video', $baseUrl ?? '');
    }
} catch (\Throwable $e) {
    $__adHtml = '';
}

if ($__adHtml === '') {
    return;
}
?>
<?php $cspNonce = defined('GDY_CSP_NONCE') ? (string)GDY_CSP_NONCE : ''; $nonceAttr = ($cspNonce !== '') ? ' nonce="' . htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>
<div class = "hm-under-featured-ad" role="region" role="region" role="region" role="region" role="region" role="region" aria-label="&#039; . htmlspecialchars($label, ENT_QUOTES, &#039;UTF-8&#039;) . &#039;">
  <?php echo $__adHtml; ?>
</div>
