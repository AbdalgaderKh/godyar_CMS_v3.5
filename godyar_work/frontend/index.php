<?php

if (!defined('ROOT_PATH')) {
    $bootstrapPath = __DIR__ . '/../includes/bootstrap.php';
    if (is_file($bootstrapPath) === true) {
        require_once $bootstrapPath;
    }
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    if (function_exists('gdy_session_start')) {
        gdy_session_start();
    } elseif (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
}

if (class_exists('Godyar\\Support\\Security')) {
    try {
        \Godyar\Support\Security::headers();
    } catch (Throwable $e) {
        
    }
}

if (function_exists('h') === false) {
    function h($v): string
    {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

$siteName = 'Godyar News';
$siteTagline = 'منصة إخبارية متكاملة';

if (function_exists('settings_get') === true) {
    $siteName = settings_get('site.name', $siteName);
    $siteTagline = settings_get('site.desc', $siteTagline);
} elseif (isset($GLOBALS['site_settings']) && is_array($GLOBALS['site_settings'])) {
    $siteName = $GLOBALS['site_settings']['site_name'] ?? $siteName;
    $siteTagline = $GLOBALS['site_settings']['site_tagline'] ?? $siteTagline;
}

$pageTitle = $siteName;

$headerFile = __DIR__ . '/views/partials/header.php';
$footerFile = __DIR__ . '/views/partials/footer.php';
$homeFile = __DIR__ . '/home.php';

if (is_file($headerFile) === true) {
    
    require $headerFile;
    ?>
    <main class="gdy-main">
    <?php
} else {
    
    ?>
    <!doctype html>
    <html lang = "ar" dir = "rtl">
    <head><meta charset = "utf-8">
<title><?php echo h($pageTitle); ?></title>
        <meta name = "viewport" content = "width=device-width, initial-scale=1">
        <link href="<?= h(asset_url('assets/vendor/bootstrap/css/bootstrap.rtl.min.css')) ?>" rel = "stylesheet">
    </head>
    <body class = "bg-light text-dark">
    <main class = "container my-4">
    <?php
}

if (is_file($homeFile) === true) {
    require $homeFile; 
} else {
    ?>
    <div class = "alert alert-warning mt-3">
        ملف <code>frontend/home .php</code> غير موجود .
    </div>
    <?php
}

	
	if (is_file($headerFile) === true) {
	    echo "</main>\n";
	}

	
	if (is_file($footerFile) === true) {
	    require $footerFile;
	} else {
	    
	    ?>
	    </main>
	    <footer class="border-top mt-4 py-3 text-center small text-muted">
	        &copy; <?php echo date('Y'); ?> <?php echo h($siteName); ?>
	    </footer>
	    </body>
	    </html>
	    <?php
	}