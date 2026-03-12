<?php

declare(strict_types=1);

$bootstrap = dirname(__DIR__) . '/includes/bootstrap.php';
if (is_file($bootstrap)) {
    require_once $bootstrap;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    if (function_exists('gdy_session_start')) {
        gdy_session_start();
    } else {
        @session_start();
    }
}

if (!function_exists('gdy_contact_redirect')) {
    function gdy_contact_redirect(string $target, string $type, string $message): void
    {
        $_SESSION['contact_flash'] = [
            'type' => $type,
            'message' => $message,
        ];
        header('Location: ' . $target, true, 302);
        exit;
    }
}

if (!function_exists('gdy_contact_back_url')) {
    function gdy_contact_back_url(): string
    {
        $ref = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        if ($ref !== '') {
            return $ref;
        }
        return '/contact';
    }
}

if (!function_exists('gdy_contact_pdo')) {
    function gdy_contact_pdo(): ?PDO
    {
        if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
            return $GLOBALS['pdo'];
        }
        if (class_exists('\\Godyar\\DB') && method_exists('\\Godyar\\DB', 'pdoOrNull')) {
            try {
                $pdo = \Godyar\DB::pdoOrNull();
                if ($pdo instanceof PDO) {
                    return $pdo;
                }
            } catch (Throwable $e) {
            }
        }
        if (function_exists('gdy_pdo_safe')) {
            try {
                $pdo = gdy_pdo_safe();
                if ($pdo instanceof PDO) {
                    return $pdo;
                }
            } catch (Throwable $e) {
            }
        }
        return null;
    }
}

if (!function_exists('gdy_contact_ensure_table')) {
    function gdy_contact_ensure_table(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS contact_messages (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(190) NOT NULL,
                email VARCHAR(190) NOT NULL,
                subject VARCHAR(255) NULL,
                message TEXT NOT NULL,
                lang VARCHAR(10) NOT NULL DEFAULT 'ar',
                ip VARCHAR(45) NULL,
                ua VARCHAR(255) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                status VARCHAR(30) NOT NULL DEFAULT 'new'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
}

if (!function_exists('gdy_contact_clean')) {
    function gdy_contact_clean($value): string
    {
        return trim((string) $value);
    }
}

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    gdy_contact_redirect(gdy_contact_back_url(), 'danger', 'طريقة الإرسال غير صالحة.');
}

try {
    if (function_exists('verify_csrf_or_throw')) {
        verify_csrf_or_throw();
    } elseif (function_exists('verify_csrf') && !verify_csrf()) {
        throw new RuntimeException('csrf');
    }
} catch (Throwable $e) {
    gdy_contact_redirect(gdy_contact_back_url(), 'danger', 'فشل التحقق الأمني. حدّث الصفحة وحاول مرة أخرى.');
}

$name = gdy_contact_clean($_POST['name'] ?? '');
$email = gdy_contact_clean($_POST['email'] ?? '');
$subject = gdy_contact_clean($_POST['subject'] ?? '');
$message = gdy_contact_clean($_POST['message'] ?? '');
$lang = gdy_contact_clean($_POST['lang'] ?? 'ar');
if (!in_array($lang, ['ar', 'en', 'fr'], true)) {
    $lang = 'ar';
}

if ($name === '' || mb_strlen($name) < 2) {
    gdy_contact_redirect(gdy_contact_back_url(), 'danger', 'يرجى إدخال الاسم بشكل صحيح.');
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    gdy_contact_redirect(gdy_contact_back_url(), 'danger', 'يرجى إدخال بريد إلكتروني صحيح.');
}
if ($message === '' || mb_strlen($message) < 10) {
    gdy_contact_redirect(gdy_contact_back_url(), 'danger', 'يرجى كتابة رسالة واضحة لا تقل عن 10 أحرف.');
}

$name = mb_substr($name, 0, 190);
$email = mb_substr($email, 0, 190);
$subject = mb_substr($subject, 0, 255);

$pdo = gdy_contact_pdo();
if (!$pdo instanceof PDO) {
    $storageDir = dirname(__DIR__) . '/storage';
    if (!is_dir($storageDir)) {
        @mkdir($storageDir, 0755, true);
    }
    $line = json_encode([
        'time' => date('c'),
        'ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        'name' => $name,
        'email' => $email,
        'subject' => $subject,
        'message' => $message,
        'lang' => $lang,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    @file_put_contents($storageDir . '/contact_messages.log', $line . PHP_EOL, FILE_APPEND);
    gdy_contact_redirect(gdy_contact_back_url(), 'success', 'تم استلام رسالتك وسيتم مراجعتها قريبًا.');
}

try {
    gdy_contact_ensure_table($pdo);
    $stmt = $pdo->prepare("
        INSERT INTO contact_messages
        (name, email, subject, message, lang, ip, ua, created_at, status)
        VALUES
        (:name, :email, :subject, :message, :lang, :ip, :ua, NOW(), 'new')
    ");
    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':subject' => $subject,
        ':message' => $message,
        ':lang' => $lang,
        ':ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        ':ua' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
    ]);
    gdy_contact_redirect(gdy_contact_back_url(), 'success', 'تم إرسال رسالتك بنجاح.');
} catch (Throwable $e) {
    gdy_contact_redirect(gdy_contact_back_url(), 'danger', 'حدث خطأ أثناء حفظ الرسالة. يرجى المحاولة لاحقًا.');
}
