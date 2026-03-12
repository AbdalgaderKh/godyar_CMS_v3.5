<?php

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/TemplateEngine.php';
require_once __DIR__ . '/../../includes/site_settings.php';

if (session_status() === PHP_SESSION_NONE) {
    gdy_session_start();
}

if (!function_exists('h')) {
    function h($v): string {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

$pdo = gdy_pdo_safe();

if (!function_exists('gdy_has_column')) {
    function gdy_has_column(PDO $pdo, string $table, string $column): bool {
        static $cache = [];
        $key = $table . '.' . $column;
        if (array_key_exists($key, $cache)) {
            return (bool)$cache[$key];
        }
        try {
            $db = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
            if ($db === '') {
                return $cache[$key] = false;
            }
            $stmt = $pdo->prepare('SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1');
            $stmt->execute([$db, $table, $column]);
            return $cache[$key] = (bool)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return $cache[$key] = false;
        }
    }
}

$settings = gdy_load_settings($pdo);
$frontendOptions = gdy_prepare_frontend_options($settings);

$homeLatestTitle = (string)($settings['home_latest_title'] ?? $settings['settings.home_latest_title'] ?? 'الأحدث');
$homeFeaturedTitle = (string)($settings['home_featured_title'] ?? $settings['settings.home_featured_title'] ?? 'مختارات');
$homeTabsTitle = (string)($settings['home_tabs_title'] ?? $settings['settings.home_tabs_title'] ?? 'الأقسام');
$homeMostReadTitle = (string)($settings['home_most_read_title'] ?? $settings['settings.home_most_read_title'] ?? 'الأكثر قراءة');
$homeMostCommentedTitle = (string)($settings['home_most_commented_title'] ?? $settings['settings.home_most_commented_title'] ?? 'الأكثر تفاعلاً');
$homeRecommendedTitle = (string)($settings['home_recommended_title'] ?? $settings['settings.home_recommended_title'] ?? 'موصى به');
$carbonBadgeText = (string)($settings['carbon_badge_text'] ?? $settings['settings.carbon_badge_text'] ?? '');
$showCarbonBadge = ((string)($settings['show_carbon_badge'] ?? $settings['settings.show_carbon_badge'] ?? '0') === '1');

extract($frontendOptions, EXTR_OVERWRITE);

$user = $_SESSION['user'] ?? null;
$isLoggedIn = !empty($user);
$isAdmin = $isLoggedIn && (($user['role'] ?? '') === 'admin');

$headerCategories = [];
try {
    if ($pdo instanceof PDO) {
        
        if (function_exists('gdy_has_column') && gdy_has_column($pdo, 'categories', 'sort_order')) {
            $stmt = $pdo->query("SELECT id, name, slug FROM categories ORDER BY sort_order ASC, id DESC LIMIT 8");
        } elseif (function_exists('gdy_has_column') && gdy_has_column($pdo, 'categories', 'position')) {
            $stmt = $pdo->query("SELECT id, name, slug FROM categories ORDER BY position ASC, id DESC LIMIT 8");
        } elseif (function_exists('gdy_has_column') && gdy_has_column($pdo, 'categories', 'created_at')) {
            $stmt = $pdo->query("SELECT id, name, slug FROM categories ORDER BY created_at DESC, id DESC LIMIT 8");
        } else {
            $stmt = $pdo->query("SELECT id, name, slug FROM categories ORDER BY id DESC LIMIT 8");
        }

        $headerCategories = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
} catch (\Throwable $e) {
    error_log('[Contact] categories error: ' . $e->getMessage());
}

if (function_exists('base_url')) {
    $baseUrl = rtrim(base_url(), '/');
} else {
    $baseUrl = '/godyar';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (function_exists('csrf_verify_or_die')) { csrf_verify_or_die('csrf_token'); }

    $name = gdy_clean_user_text($_POST['name'] ?? '', 190);
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $email = gdy_strip_controls($email);
    $subject = gdy_clean_user_text($_POST['subject'] ?? '', 255);
    $message = gdy_clean_user_text($_POST['message'] ?? '', 4000);

    $errors = [];

    if ($name === '') {
        $errors[] = 'الاسم مطلوب.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'البريد الإلكتروني غير صالح.';
    }
    if ($message === '') {
        $errors[] = 'نص الرسالة مطلوب.';
    }

    if ($errors) {
        $_SESSION['contact_errors'] = $errors;
        $_SESSION['contact_old'] = [
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $message,
        ];

        header('Location: ' . $baseUrl . '/contact');
        exit;
    }

    
    if ($pdo instanceof PDO) {
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS contact_messages (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(190) NOT NULL,
                    email VARCHAR(190) NOT NULL,
                    subject VARCHAR(255) NULL,
                    message TEXT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci
            ");

            $stmt = $pdo->prepare("
                INSERT INTO contact_messages (name, email, subject, message)
                VALUES (:name, :email, :subject, :message)
            ");
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':subject' => $subject,
                ':message' => $message,
            ]);
        } catch (\Throwable $e) {
            error_log('[Contact] DB error: ' . $e->getMessage());
        }
    }

    
    try {
        
        $adminEmail = '';
        if (function_exists('gdy_setting')) {
            $adminEmail = gdy_setting($settings, 'contact_email', '');
        } else {
            $adminEmail = (string)($settings['contact_email'] ?? '');
        }

        
        if ($adminEmail && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            
            $mailSubject = 'رسالة جديدة من نموذج اتصل بنا-' . ($siteName ?? 'Godyar');
            $encodedSubject = '=?UTF-8?B?' .base64_encode($mailSubject) . '?=';

            
            $bodyLines = [
                "تم استلام رسالة جديدة من نموذج اتصل بنا في الموقع:",
                "",
                "الاسم: {$name}",
                "البريد الإلكتروني: {$email}",
                "الموضوع: " . ($subject !== '' ? $subject : 'بدون موضوع'),
                "",
                "نص الرسالة:",
                $message,
                "",
                "----------------------------",
                "تاريخ الإرسال: " .date('Y-m-d H:i:s'),
                "عنوان الموقع: " . ($baseUrl ?: ''),
            ];
            $body = implode("\n", $bodyLines);

            
            
            $fromEmail = 'admin@example.com';
            $fromName = $siteName ?? 'Godyar';

            $headers = 'From: ' .sprintf('"%s" <%s>', '=?UTF-8?B?' .base64_encode($fromName) . '?=', $fromEmail) . "\r\n";
            $headers .= 'Reply-To: ' . $email . "\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $headers .= "MIME-Version: 1.0\r\n";

            
            gdy_mail($adminEmail, $encodedSubject, $body, $headers);
        }
    } catch (\Throwable $e) {
        error_log('[Contact] mail error: ' . $e->getMessage());
    }

    
    $_SESSION['contact_success'] = 'تم إرسال رسالتك بنجاح، سنقوم بالرد عليك في أقرب وقت ممكن.';
    header('Location: ' . $baseUrl . '/contact');
    exit;
}

$contactErrors = $_SESSION['contact_errors'] ?? [];
$contactOld = $_SESSION['contact_old'] ?? [];
$contactSuccess = $_SESSION['contact_success'] ?? null;

unset($_SESSION['contact_errors'], $_SESSION['contact_old'], $_SESSION['contact_success']);

$page = [
    'slug' => 'contact',
    'title' => 'اتصل بنا',
    'content' => '',
    'created_at' => date('Y-m-d'),
    'updated_at' => null,
];

$pageNotFound = $pageNotFound ?? false;

$templateData = [
    
    'siteName' => $siteName,
    'siteTagline' => $siteTagline,
    'siteLogo' => $siteLogo,
    'primaryColor' => $primaryColor,
    'primaryDark' => $primaryDark,
    'baseUrl' => $baseUrl,
    'themeClass' => $themeClass,

    
    'searchPlaceholder' => $searchPlaceholder,
    'homeLatestTitle' => $homeLatestTitle,
    'homeFeaturedTitle' => $homeFeaturedTitle,
    'homeTabsTitle' => $homeTabsTitle,
    'homeMostReadTitle' => $homeMostReadTitle,
    'homeMostCommentedTitle' => $homeMostCommentedTitle,
    'homeRecommendedTitle' => $homeRecommendedTitle,
    'carbonBadgeText' => $carbonBadgeText,
    'showCarbonBadge' => $showCarbonBadge,

    
    'isLoggedIn' => $isLoggedIn,
    'isAdmin' => $isAdmin,

    
    'headerCategories' => $headerCategories,

    
    'page' => $page,
    'pageNotFound' => $pageNotFound,

    
    'contactErrors' => $contactErrors,
    'contactOld' => $contactOld,
    'contactSuccess' => $contactSuccess,
];

$template = new TemplateEngine();
$template->render(__DIR__ . '/../views/page/content.php', $templateData);
