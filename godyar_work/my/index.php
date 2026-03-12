<?php 

require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_once dirname(__DIR__) . '/includes/functions.php';

ob_start();

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$isLoggedIn = !empty($_SESSION['user']) || !empty($_SESSION['user_id']) || !empty($_SESSION['is_member_logged']);

$user = null;
$userId = 0;

if ($isLoggedIn) {
    
    $user = [];
    
    
    if (!empty($_SESSION['user_id'])) {
        $userId = (int)$_SESSION['user_id'];
        $user['id'] = $userId;
    } elseif (!empty($_SESSION['user']['id'])) {
        $userId = (int)$_SESSION['user']['id'];
        $user['id'] = $userId;
    }
    
    
    if (!empty($_SESSION['username'])) {
        $user['username'] = (string)$_SESSION['username'];
    } elseif (!empty($_SESSION['user']['username'])) {
        $user['username'] = (string)$_SESSION['user']['username'];
    }
    
    
    if (!empty($_SESSION['user_email'])) {
        $user['email'] = (string)$_SESSION['user_email'];
    } elseif (!empty($_SESSION['user']['email'])) {
        $user['email'] = (string)$_SESSION['user']['email'];
    }
    
    
    if (!empty($_SESSION['user']['role'])) {
        $user['role'] = (string)$_SESSION['user']['role'];
    } elseif (!empty($_SESSION['role'])) {
        $user['role'] = (string)$_SESSION['role'];
    }
}

if (!$isLoggedIn || $userId === 0) {
    
    $lang = function_exists('gdy_lang') ? (string)gdy_lang() : (isset($_GET['lang']) ? (string)$_GET['lang'] : 'ar');
    $rootUrl = function_exists('base_url') ? rtrim((string)base_url(), '/') : '';
    $navBaseUrl = ($rootUrl !== '' ? $rootUrl : '') . '/' . trim($lang, '/');
    if ($rootUrl === '') { $navBaseUrl = '/' . trim($lang, '/'); }
    $next = '/my';
    header('Location: ' . rtrim($navBaseUrl, '/') . '/login?next=' . rawurlencode($next));
    exit;
}

$pdo = gdy_pdo_safe();

$siteSettings = [];
$baseUrl = function_exists('base_url') ? rtrim(base_url(), '/') : '';
$siteName = 'Godyar News';
$lang = 'ar';

if ($pdo instanceof PDO) {
    try {
        $settingsStmt = $pdo->query("SELECT * FROM settings WHERE 1");
        if ($settingsStmt) {
            $settingsRows = $settingsStmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($settingsRows as $row) {
                $siteSettings[$row['key']] = $row['value'];
            }
            
            $siteName = $siteSettings['site_name'] ?? $siteSettings['sitename'] ?? 'Godyar News';
            $lang = $siteSettings['site_lang'] ?? $siteSettings['language'] ?? 'ar';
        }
    } catch (Throwable $e) {
        @error_log('[Godyar My] Settings fetch error: ' . $e->getMessage());
    }
}

$userData = [];
if ($pdo instanceof PDO && $userId > 0) {
    try {
        
        $checkColumns = $pdo->query("SHOW COLUMNS FROM users LIKE 'display_name'");
        if ($checkColumns && $checkColumns->rowCount() == 0) {
            
            $pdo->exec("ALTER TABLE users 
                ADD COLUMN display_name VARCHAR(100) NULL AFTER username,
                ADD COLUMN phone VARCHAR(20) NULL AFTER email,
                ADD COLUMN bio TEXT NULL,
                ADD COLUMN avatar VARCHAR(255) NULL,
                ADD COLUMN facebook VARCHAR(255) NULL,
                ADD COLUMN twitter VARCHAR(255) NULL,
                ADD COLUMN instagram VARCHAR(255) NULL,
                ADD COLUMN youtube VARCHAR(255) NULL,
                ADD COLUMN tiktok VARCHAR(255) NULL,
                ADD COLUMN website VARCHAR(255) NULL,
                ADD COLUMN location VARCHAR(100) NULL,
                ADD COLUMN birthdate DATE NULL,
                ADD COLUMN last_login DATETIME NULL,
                ADD COLUMN updated_at DATETIME NULL,
                ADD COLUMN email_verified TINYINT(1) DEFAULT 0,
                ADD COLUMN phone_verified TINYINT(1) DEFAULT 0,
                ADD COLUMN two_factor_enabled TINYINT(1) DEFAULT 0,
                ADD COLUMN notification_settings TEXT NULL
            ");
        }
        
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $userId]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        
        
        if (!empty($userData)) {
            $user = array_merge($user, $userData);
        }
    } catch (PDOException $e) {
        error_log("Error fetching user data: " . $e->getMessage());
    }
}

$successMessage = '';
$errorMessage = '';

$csrfToken = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrfToken;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    $postedToken = (string)($_POST['csrf_token'] ?? '');
    
    if (!hash_equals($csrfToken, $postedToken)) {
        $errorMessage = 'خطأ في التحقق من الأمان. يرجى تحديث الصفحة والمحاولة مرة أخرى.';
    } else {
        $action = $_POST['action'];
        
        
        if ($action === 'update_profile' && $pdo instanceof PDO) {
            $displayName = trim((string)($_POST['display_name'] ?? ''));
            $email = trim((string)($_POST['email'] ?? $user['email'] ?? ''));
            $phone = trim((string)($_POST['phone'] ?? ''));
            $bio = trim((string)($_POST['bio'] ?? ''));
            $location = trim((string)($_POST['location'] ?? ''));
            $birthdate = trim((string)($_POST['birthdate'] ?? ''));
            
            
            $facebook = trim((string)($_POST['facebook'] ?? ''));
            $twitter = trim((string)($_POST['twitter'] ?? ''));
            $instagram = trim((string)($_POST['instagram'] ?? ''));
            $youtube = trim((string)($_POST['youtube'] ?? ''));
            $tiktok = trim((string)($_POST['tiktok'] ?? ''));
            $website = trim((string)($_POST['website'] ?? ''));
            
            
            $validationErrors = [];
            
            if (empty($email)) {
                $validationErrors[] = 'البريد الإلكتروني مطلوب.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $validationErrors[] = 'البريد الإلكتروني غير صالح.';
            }
            
            if (!empty($phone) && !preg_match('/^[0-9+\-\s]+$/', $phone)) {
                $validationErrors[] = 'رقم الهاتف غير صالح.';
            }
            
            if (!empty($birthdate)) {
                $date = DateTime::createFromFormat('Y-m-d', $birthdate);
                if (!$date || $date->format('Y-m-d') !== $birthdate) {
                    $validationErrors[] = 'تاريخ الميلاد غير صالح.';
                }
            }
            
            
            $socialFields = ['facebook', 'twitter', 'instagram', 'youtube', 'tiktok', 'website'];
            foreach ($socialFields as $field) {
                if (!empty($$field) && !filter_var($$field, FILTER_VALIDATE_URL)) {
                    $validationErrors[] = 'رابط ' . $field . ' غير صالح.';
                }
            }
            
            
            if ($email !== $user['email'] && empty($validationErrors)) {
                try {
                    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
                    $checkStmt->execute([':email' => $email, ':id' => $userId]);
                    if ($checkStmt->fetch()) {
                        $validationErrors[] = 'البريد الإلكتروني موجود مسبقاً.';
                    }
                } catch (PDOException $e) {
                    error_log("Error checking email: " . $e->getMessage());
                }
            }
            
            if (empty($validationErrors)) {
                try {
                    
                    $updateStmt = $pdo->prepare("
                        UPDATE users SET 
                            display_name = :display_name,
                            email = :email,
                            phone = :phone,
                            bio = :bio,
                            location = :location,
                            birthdate = :birthdate,
                            facebook = :facebook,
                            twitter = :twitter,
                            instagram = :instagram,
                            youtube = :youtube,
                            tiktok = :tiktok,
                            website = :website,
                            updated_at = NOW()
                        WHERE id = :id
                    ");
                    
                    $updateStmt->execute([
                        ':display_name' => $displayName,
                        ':email' => $email,
                        ':phone' => $phone,
                        ':bio' => $bio,
                        ':location' => $location,
                        ':birthdate' => $birthdate ?: null,
                        ':facebook' => $facebook,
                        ':twitter' => $twitter,
                        ':instagram' => $instagram,
                        ':youtube' => $youtube,
                        ':tiktok' => $tiktok,
                        ':website' => $website,
                        ':id' => $userId
                    ]);
                    
                    
                    $_SESSION['user_email'] = $email;
                    if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
                        $_SESSION['user']['email'] = $email;
                    }
                    
                    $successMessage = 'تم تحديث الملف الشخصي بنجاح.';
                    
                    
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
                    $stmt->execute([':id' => $userId]);
                    $userData = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
                    $user = array_merge($user, $userData);
                    
                } catch (PDOException $e) {
                    error_log("Error updating profile: " . $e->getMessage());
                    $errorMessage = 'حدث خطأ أثناء تحديث الملف الشخصي.';
                }
            } else {
                $errorMessage = implode('<br>', $validationErrors);
            }
        }
        
        
        elseif ($action === 'change_password' && $pdo instanceof PDO) {
            $currentPassword = (string)($_POST['current_password'] ?? '');
            $newPassword = (string)($_POST['new_password'] ?? '');
            $confirmPassword = (string)($_POST['confirm_password'] ?? '');
            
            $validationErrors = [];
            
            if (empty($currentPassword)) {
                $validationErrors[] = 'كلمة المرور الحالية مطلوبة.';
            }
            
            if (empty($newPassword)) {
                $validationErrors[] = 'كلمة المرور الجديدة مطلوبة.';
            } elseif (strlen($newPassword) < 8) {
                $validationErrors[] = 'كلمة المرور الجديدة يجب أن تكون 8 أحرف على الأقل.';
            } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $newPassword)) {
                $validationErrors[] = 'كلمة المرور يجب أن تحتوي على حرف كبير وحرف صغير ورقم.';
            }
            
            if ($newPassword !== $confirmPassword) {
                $validationErrors[] = 'كلمة المرور الجديدة وتأكيدها غير متطابقين.';
            }
            
            if (empty($validationErrors)) {
                try {
                    
                    $passStmt = $pdo->prepare("SELECT password_hash, password FROM users WHERE id = :id");
                    $passStmt->execute([':id' => $userId]);
                    $userPass = $passStmt->fetch(PDO::FETCH_ASSOC);
                    
                    $currentHash = (string)($userPass['password_hash'] ?? $userPass['password'] ?? '');
                    
                    if (empty($currentHash) || !password_verify($currentPassword, $currentHash)) {
                        $validationErrors[] = 'كلمة المرور الحالية غير صحيحة.';
                    } else {
                        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                        $updateStmt = $pdo->prepare("UPDATE users SET password_hash = :password, updated_at = NOW() WHERE id = :id");
                        $updateStmt->execute([
                            ':password' => $newHash,
                            ':id' => $userId
                        ]);
                        
                        $successMessage = 'تم تغيير كلمة المرور بنجاح.';
                    }
                } catch (PDOException $e) {
                    error_log("Error changing password: " . $e->getMessage());
                    $validationErrors[] = 'حدث خطأ أثناء تغيير كلمة المرور.';
                }
            }
            
            if (!empty($validationErrors)) {
                $errorMessage = implode('<br>', $validationErrors);
            }
        }
        
        
        elseif ($action === 'update_avatar' && $pdo instanceof PDO && isset($_FILES['avatar'])) {
            $file = $_FILES['avatar'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 2 * 1024 * 1024; 
            
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errorMessage = 'خطأ في رفع الملف.';
            } elseif (!in_array($file['type'], $allowedTypes)) {
                $errorMessage = 'نوع الملف غير مسموح. الأنواع المسموحة: JPG, PNG, GIF, WEBP';
            } elseif ($file['size'] > $maxSize) {
                $errorMessage = 'حجم الملف كبير جداً. الحد الأقصى 2 ميجابايت.';
            } else {
                
                $uploadDir = dirname(__DIR__) . '/uploads/avatars/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'avatar_' . $userId . '_' . time() . '.' . $extension;
                $uploadPath = $uploadDir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    
                    if (!empty($userData['avatar']) && file_exists(dirname(__DIR__) . $userData['avatar'])) {
                        unlink(dirname(__DIR__) . $userData['avatar']);
                    }
                    
                    
                    $avatarPath = '/uploads/avatars/' . $filename;
                    $updateStmt = $pdo->prepare("UPDATE users SET avatar = :avatar WHERE id = :id");
                    $updateStmt->execute([
                        ':avatar' => $avatarPath,
                        ':id' => $userId
                    ]);
                    
                    $successMessage = 'تم تحديث الصورة الشخصية بنجاح.';
                    
                    
                    $userData['avatar'] = $avatarPath;
                    $user['avatar'] = $avatarPath;
                } else {
                    $errorMessage = 'فشل في رفع الملف.';
                }
            }
        }
    }
}

$GLOBALS['isLoggedIn'] = $isLoggedIn;
$GLOBALS['userId'] = $userId;
$GLOBALS['isAdmin'] = (isset($user['role']) && ($user['role'] === 'admin' || $user['role'] === 'super_admin' || $user['role'] === 'administrator'));

$categories = [];
if ($pdo instanceof PDO) {
    try {
        $catStmt = $pdo->query("SELECT id, name, slug FROM categories WHERE status = 'active' ORDER BY sort_order ASC, name ASC LIMIT 10");
        if ($catStmt) {
            $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
    } catch (Throwable $e) {
        @error_log('[Godyar My] Categories fetch error: ' . $e->getMessage());
    }
}

$bookmarkedNews = [];

if ($pdo instanceof PDO) {
    try {
        $tableExists = false;
        $checkStmt = $pdo->query("SHOW TABLES LIKE 'user_bookmarks'");
        if ($checkStmt && $checkStmt->rowCount() > 0) {
            $tableExists = true;
        }
        
        if ($tableExists) {
            $stmt = $pdo->prepare("
                SELECT n.id, n.title, n.slug, n.published_at, n.image, n.excerpt
                FROM user_bookmarks b
                INNER JOIN news n ON n.id = b.news_id
                WHERE b.user_id = :uid
                ORDER BY b.created_at DESC
                LIMIT 30
            ");
            $stmt->execute([':uid' => $userId]);
            $bookmarkedNews = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
    } catch (Throwable $e) {
        @error_log('[Godyar My] Bookmarks fetch error: ' . $e->getMessage());
    }
}

$pageTitle       = 'الملف الشخصي - ' . ($user['display_name'] ?? $user['username'] ?? $siteName);
$pageDescription = 'صفحة الملف الشخصي وإدارة الحساب';
$meta_title = $pageTitle;
$meta_description = $pageDescription;
$pageLang = $lang;

ob_clean();

require_once dirname(__DIR__) . '/frontend/views/partials/header.php';
?>

<main class="main-content profile-page">
    <div class="container py-4">
        <!-- رسائل النجاح والخطأ -->
        <?php if ($successMessage): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <svg class="gdy-icon me-2" width="20" height="20"><use href="<?= htmlspecialchars(asset_url('assets/icons/godyar-icons.svg'), ENT_QUOTES, 'UTF-8') ?>#check-circle"></use></svg>
                <?= $successMessage ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($errorMessage): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <svg class="gdy-icon me-2" width="20" height="20"><use href="<?= htmlspecialchars(asset_url('assets/icons/godyar-icons.svg'), ENT_QUOTES, 'UTF-8') ?>#alert-circle"></use></svg>
                <?= $errorMessage ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- الشريط الجانبي -->
            <div class="col-lg-3 mb-4">
                <div class="profile-sidebar card shadow-sm">
                    <div class="card-body text-center">
                        <div class="profile-avatar-wrapper mb-3">
                            <div class="profile-avatar">
                                <?php if (!empty($user['avatar'])): ?>
                                    <img src="<?= htmlspecialchars($baseUrl . $user['avatar'], ENT_QUOTES, 'UTF-8') ?>" 
                                         alt="<?= htmlspecialchars($user['display_name'] ?? $user['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                         class="rounded-circle img-fluid"
                                         style="width: 120px; height: 120px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="avatar-circle">
                                        <span class="avatar-initials">
                                            <?= htmlspecialchars(mb_substr(($user['display_name'] ?? $user['username'] ?? 'U'), 0, 1, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- زر تغيير الصورة -->
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" data-bs-toggle="modal" data-bs-target="#avatarModal">
                                <svg class="gdy-icon me-1" width="16" height="16"><use href="<?= htmlspecialchars(asset_url('assets/icons/godyar-icons.svg'), ENT_QUOTES, 'UTF-8') ?>#camera"></use></svg>
                                تغيير الصورة
                            </button>
                        </div>
                        
                        <h4 class="profile-name mb-1"><?= htmlspecialchars($user['display_name'] ?? $user['username'] ?? 'مستخدم', ENT_QUOTES, 'UTF-8') ?></h4>
                        <p class="profile-username text-muted small mb-2">@<?= htmlspecialchars($user['username'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                        
                        <?php if (!empty($user['bio'])): ?>
                            <p class="profile-bio small mb-3"><?= nl2br(htmlspecialchars($user['bio'], ENT_QUOTES, 'UTF-8')) ?></p>
                        <?php endif; ?>
                        
                        <div class="profile-stats d-flex justify-content-around mb-3">
                            <div class="stat-item">
                                <div class="stat-value"><?= count($bookmarkedNews) ?></div>
                                <div class="stat-label small text-muted">محفوظ</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">0</div>
                                <div class="stat-label small text-muted">متابعون</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">0</div>
                                <div class="stat-label small text-muted">يتابع</div>
                            </div>
                        </div>
                        
                        <?php if (!empty($user['location'])): ?>
                            <div class="profile-info-item text-muted small mb-1">
                                <svg class="gdy-icon me-1" width="14" height="14"><use href="<?= htmlspecialchars(asset_url('assets/icons/godyar-icons.svg'), ENT_QUOTES, 'UTF-8') ?>#map-pin"></use></svg>
                                <?= htmlspecialchars($user['location'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($user['birthdate'])): ?>
                            <div class="profile-info-item text-muted small mb-1">
                                <svg class="gdy-icon me-1" width="14" height="14"><use href="<?= htmlspecialchars(asset_url('assets/icons/godyar-icons.svg'), ENT_QUOTES, 'UTF-8') ?>#calendar"></use></svg>
                                <?= htmlspecialchars(date('d M Y', strtotime($user['birthdate'])), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- روابط التواصل الاجتماعي -->
                        <div class="social-links mt-3">
                            <?php if (!empty($user['facebook'])): ?>
                                <a href="<?= htmlspecialchars($user['facebook'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="social-link facebook me-1" title="فيسبوك">
                                    <svg class="gdy-icon" width="20" height="20"><use href="<?= htmlspecialchars(asset_url('assets/icons/godyar-icons.svg'), ENT_QUOTES, 'UTF-8') ?>#facebook"></use></svg>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($user['twitter'])): ?>
                                <a href="<?= htmlspecialchars($user['twitter'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="social-link twitter me-1" title="تويتر">
                                    <svg class="gdy-icon" width="20" height="20"><use href="<?= htmlspecialchars(asset_url('assets/icons/godyar-icons.svg'), ENT_QUOTES, 'UTF-8') ?>#twitter"></use></svg>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($user['instagram'])): ?>
                                <a href="<?= htmlspecialchars($user['instagram'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="social-link instagram me-1" title="انستغرام">
                                    <svg class="gdy-icon" width="20" height="20"><use href="<?= htmlspecialchars(asset_url('assets/icons/godyar-icons.svg'), ENT_QUOTES, 'UTF-8') ?>#instagram"></use></svg>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($user['youtube'])): ?>
                                <a href="<?= htmlspecialchars($user['youtube'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="social-link youtube me-1" title="يوتيوب">
                                    <svg class="gdy-icon" width="20" height="20"><use href="<?= htmlspecialchars(asset_url('assets/icons/godyar-icons.svg'), ENT_QUOTES, 'UTF-8') ?>#youtube"></use></svg>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($user['tiktok'])): ?>
                                <a href="<?= htmlspecialchars($user['tiktok'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="social-link tiktok me-1" title="تيك توك">
                                    <svg class="gdy-icon" width="20" height="20"><use href="<?= htmlspecialchars(asset_url('assets/icons/godyar-icons.svg'), ENT_QUOTES, 'UTF-8') ?>#tiktok"></use></svg>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($user['website'])): ?>
                                <a href="<?= htmlspecialchars($user['website'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="social-link website me-1" title="الموقع الشخصي">
                                    <svg class="gdy-icon" width="20" height="20"><use href="<?= htmlspecialchars(asset_url('assets/icons/godyar-icons.svg'), ENT_QUOTES, 'UTF-8') ?>#globe"></use></svg>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- قائمة التنقل الجانبية -->
                    <div class="list-group list-group-flush">
                        <a href="#profile-tab" class="list-group-item list-group-item-action active" data-bs-toggle="list">
                            <svg class="gdy-icon me-2" width="18" height="18"><use href="<?= htmlspecialchars(asset_url('assets/icons/godyar-icons.svg'), ENT_QUOTES, 'UTF-8') ?>#user"></use></svg>
                            المعلومات الشخصية
                        </a>
                        <a href="#social-tab" class="list-group-item list-group-item-action" data-bs-toggle="list">
                            <svg class="gdy-icon me-2" width="18" height="18"><use href="<?= htmlspecialchars(asset_url('assets/icons/godyar-icons.svg'), ENT_QUOTES, 'UTF-8') ?>#share-2"></use></svg>
                            وسائل التواصل
                        </a>
                        <a href="#password-tab" class="list-group-item list-group-item-action" data-bs-toggle="list">
                            <svg class="gdy-icon me-2" width="18" height="18"><use href="<?= htmlspecialchars(asset_url('assets/icons/godyar-icons.svg'), ENT_QUOTES, 'UTF-8') ?>#lock"></use></svg>
                            الأمان وكلمة المرور
                        </a>
                        <a href="#bookmarks-tab" class="list-group-item list-group-item-action" data-bs-toggle="list">
                            <svg class="gdy-icon me-2" width="18" height="18"><use href="<?= htmlspecialchars(asset_url('assets/icons/godyar-icons.svg'), ENT_QUOTES, 'UTF-8') ?>#bookmark"></use></svg>
                            المحفوظات (<?= count($bookmarkedNews) ?>)
                        </a>
                        <a href="#notifications-tab" class="list-group-item list-group-item-action" data-bs-toggle="list">
                            <svg class="gdy-icon me-2" width="18" height="18"><use href="<?= htmlspecialchars(asset_url('assets/icons/godyar-icons.svg'), ENT_QUOTES, 'UTF-8') ?>#bell"></use></svg>
                            الإشعارات
                        </a>
                        <a href="/logout" class="list-group-item list-group-item-action text-danger">
                            <svg class="gdy-icon me-2" width="18" height="18"><use href="<?= htmlspecialchars(asset_url('assets/icons/godyar-icons.svg'), ENT_QUOTES, 'UTF-8') ?>#logout"></use></svg>
                            تسجيل الخروج
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- المحتوى الرئيسي -->
            <div class="col-lg-9">
                <div class="tab-content">
                    <!-- تبويب المعلومات الشخصية -->
                    <div class="tab-pane fade show active" id="profile-tab">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">
                                    <svg class="gdy-icon me-2" width="20" height="20"><use href="<?= htmlspecialchars(asset_url('assets/icons/godyar-icons.svg'), ENT_QUOTES, 'UTF-8') ?>#user"></use></svg>
                                    المعلومات الشخصية
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="" class="profile-form">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="action" value="update_profile">
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="username" class="form-label">اسم المستخدم</label>
                                            <input type="text" class="form-control" id="username" 
                                                   value="<?= htmlspecialchars($user['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                                   disabled readonly>
                                            <small class="text-muted">لا يمكن تغيير اسم المستخدم</small>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="display_name" class="form-label">الاسم المعروض</label>
                                            <input type="text" class="form-control" id="display_name" name="display_name" 
                                                   value="<?= htmlspecialchars($user['display_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                   placeholder="الاسم الذي يظهر للآخرين">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">البريد الإلكتروني</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="phone" class="form-label">رقم الهاتف</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" 
                                                   value="<?= htmlspecialchars($user['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                   placeholder="05xxxxxxxx">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="location" class="form-label">الموقع</label>
                                            <input type="text" class="form-control" id="location" name="location" 
                                                   value="<?= htmlspecialchars($user['location'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                   placeholder="المدينة، الدولة">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="birthdate" class="form-label">تاريخ الميلاد</label>
                                            <input type="date" class="form-control" id="birthdate" name="birthdate" 
                                                   value="<?= htmlspecialchars($user['birthdate'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="bio" class="form-label">نبذة عني</label>
                                        <textarea class="form-control" id="bio" name="bio" rows="4" 
                                                  placeholder="اكتب نبذة قصيرة عن نفسك..."><?= htmlspecialchars($user['bio'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                                        <small class="text-muted">الحد الأقصى 500 حرف</small>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <svg class="gdy-icon me-1" width="18" height="18"><use href="<?= htmlspecialchars(asset_url('assets/icons/godyar-icons.svg'), ENT_QUOTES, 'UTF-8') ?>#save"></use></svg>
                                        حفظ التغييرات
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- تبويب وسائل التواصل -->
                    <div class="tab-pane fade" id="social-tab">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">
                                    <svg class="gdy-icon me-2" width="20" height="20"><use href="<?= htmlspecialchars(asset_url('assets/icons/godyar-icons.svg'), ENT_QUOTES, 'UTF-8') ?>#share-2"></use></svg>
                                    وسائل التواصل الاجتماعي
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="" class="social-form">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="action" value="update_profile">
                                    
                                    <div class="mb-3">
                                        <label for="facebook" class="form-label">
                                            <svg class="gdy-icon me-1" width="16" height="16"><use href="<?= htmlspecialchars(asset_url('assets/icons/godyar-icons.svg'), ENT_QUOTES, 'UTF-8') ?>#facebook"></use></svg>
                                            فيسبوك
                                        </label>
                                        <input type="url" class="form-control" id="facebook" name="facebook" 
                                               value="<?= htmlspecialchars($user['facebook'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                               placeholder="https://facebook.com/username">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="twitter" class="form-label">
                                            <svg class="gdy-icon me-1" width="16" height="16"><use href="<?= htmlspecialchars(asset_url('assets/icons/godyar-icons.svg'), ENT_QUOTES, 'UTF-8') ?>#twitter"></use></svg>
                                            تويتر
                                        </label>
                                        <input type="url" class="form-control" id="twitter" name="twitter" 
                                               value="<?= htmlspecialchars($user['twitter'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                               placeholder="https://twitter.com/username">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="instagram" class="form-label">
                                            <svg class="gdy-icon me-1" width="16" height="16"><use href="<?= htmlspecialchars(asset_url('assets/icons/godyar-icons.svg'), ENT_QUOTES, 'UTF-8') ?>#instagram"></use></svg>
                                            انستغرام
                                        </label>
                                        <input type="url" class="form-control" id="instagram" name="instagram" 
                                               value="<?= htmlspecialchars($user['instagram'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                               placeholder="https://instagram.com/username">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="youtube" class="form-label">
                                            <svg class="gdy-icon me-1" width="16" height="16"><use href="<?= htmlspecialchars(asset_url('assets/icons/godyar-icons.svg'), ENT_QUOTES, 'UTF-8') ?>#youtube"></use></svg>
                                            يوتيوب
                                        </label>
                                        <input type="url" class="form-control" id="youtube" name="youtube" 
                                               value="<?= htmlspecialchars($user['youtube'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                               placeholder="https://youtube.com/@channel">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="tiktok" class="form-label">
                                            <svg class="gdy-icon me-1" width="16" height="16"><use href="<?= htmlspecialchars(asset_url('assets/icons/godyar-icons.svg'), ENT_QUOTES, 'UTF-8') ?>#tiktok"></use></svg>
                                            تيك توك
                                        </label>
                                        <input type="url" class="form-control" id="tiktok" name="tiktok" 
                                               value="<?= htmlspecialchars($user['tiktok'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                               placeholder="https://tiktok.com/@username">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="website" class="form-label">
                                            <svg class="gdy-icon me-1" width="16" height="16"><use href="<?= htmlspecialchars(asset_url('assets/icons/godyar-icons.svg'), ENT_QUOTES, 'UTF-8') ?>#globe"></use></svg>
                                            الموقع الشخصي
                                        </label>
                                        <input type="url" class="form-control" id="website" name="website" 
                                               value="<?= htmlspecialchars($user['website'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                               placeholder="https://example.com">
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <svg class="gdy-icon me-1" width="18" height="18"><use href="<?= htmlspecialchars(asset_url('assets/icons/godyar-icons.svg'), ENT_QUOTES, 'UTF-8') ?>#save"></use></svg>
                                        حفظ روابط التواصل
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- تبويب الأمان وكلمة المرور -->
                    <div class="tab-pane fade" id="password-tab">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">
                                    <svg class="gdy-icon me-2" width="20" height="20"><use href="<?= htmlspecialchars(asset_url('assets/icons/godyar-icons.svg'), ENT_QUOTES, 'UTF-8') ?>#lock"></use></svg>
                                    تغيير كلمة المرور
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="" class="password-form">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="action" value="change_password">
                                    
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">كلمة المرور الحالية</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">كلمة المرور الجديدة</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        <small class="text-muted">
                                            يجب أن تحتوي على 8 أحرف على الأقل، حرف كبير، حرف صغير، ورقم
                                        </small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">تأكيد كلمة المرور الجديدة</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="logout_others" name="logout_others" value="1">
                                            <label class="form-check-label" for="logout_others">
                                                تسجيل الخروج من جميع الأجهزة الأخرى
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-warning">
                                        <svg class="gdy-icon me-1" width="18" height="18"><use href="<?= htmlspecialchars(asset_url('assets/icons/godyar-icons.svg'), ENT_QUOTES, 'UTF-8') ?>#key"></use></svg>
                                        تغيير كلمة المرور
                                    </button>
                                </form>
                                
                                <hr class="my-4">
                                
                                <h6 class="mb-3">التحقق بخطوتين (2FA)</h6>
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="mb-1">تعزيز أمان حسابك بإضافة طبقة حماية إضافية</p>
                                        <small class="text-muted">عند تفعيلها، ستحتاج إلى رمز تحقق إضافي عند تسجيل الدخول</small>
                                    </div>
                                    <button class="btn btn-outline-primary" disabled>
                                        قريباً
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- تبويب المحفوظات -->
                    <div class="tab-pane fade" id="bookmarks-tab">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <svg class="gdy-icon me-2" width="20" height="20"><use href="<?= htmlspecialchars(asset_url('assets/icons/godyar-icons.svg'), ENT_QUOTES, 'UTF-8') ?>#bookmark"></use></svg>
                                    الأخبار المحفوظة
                                </h5>
                                <span class="badge bg-primary"><?= count($bookmarkedNews) ?></span>
                            </div>
                            
                            <div class="card-body">
                                <?php if (empty($bookmarkedNews)): ?>
                                    <div class="text-center py-5">
                                        <svg class="gdy-icon text-muted mb-3" width="48" height="48">
                                            <use href="<?= htmlspecialchars(asset_url('assets/icons/godyar-icons.svg'), ENT_QUOTES, 'UTF-8') ?>#bookmark"></use>
                                        </svg>
                                        <h6>لا توجد أخبار محفوظة</h6>
                                        <p class="text-muted">عندما تحفظ الأخبار، ستظهر هنا</p>
                                        <a href="/" class="btn btn-primary btn-sm">استعرض الأخبار</a>
                                    </div>
                                <?php else: ?>
                                    <div class="row g-3">
                                        <?php foreach ($bookmarkedNews as $item): ?>
                                            <div class="col-md-6">
                                                <div class="card h-100 news-card">
                                                    <?php if (!empty($item['image'])): ?>
                                                        <a href="<?= htmlspecialchars($baseUrl . '/news/id/' . (int)($item['id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>" class="news-image-link">
                                                            <img src="<?= htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8') ?>" 
                                                                 class="card-img-top news-image" 
                                                                 alt="<?= htmlspecialchars($item['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                                 loading="lazy">
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <div class="card-body">
                                                        <h6 class="card-title mb-2">
                                                            <a href="<?= htmlspecialchars($baseUrl . '/news/id/' . (int)($item['id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>" 
                                                               class="text-decoration-none text-dark stretched-link">
                                                                <?= htmlspecialchars($item['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                                            </a>
                                                        </h6>
                                                        
                                                        <?php if (!empty($item['published_at'])): ?>
                                                            <small class="text-muted">
                                                                <svg class="gdy-icon me-1" width="12" height="12">
                                                                    <use href="<?= htmlspecialchars(asset_url('assets/icons/godyar-icons.svg'), ENT_QUOTES, 'UTF-8') ?>#calendar"></use>
                                                                </svg>
                                                                <?= htmlspecialchars(date('Y-m-d', strtotime($item['published_at'])), ENT_QUOTES, 'UTF-8') ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- تبويب الإشعارات -->
                    <div class="tab-pane fade" id="notifications-tab">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">
                                    <svg class="gdy-icon me-2" width="20" height="20"><use href="<?= htmlspecialchars(asset_url('assets/icons/godyar-icons.svg'), ENT_QUOTES, 'UTF-8') ?>#bell"></use></svg>
                                    إعدادات الإشعارات
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="text-center text-muted py-5">
                                    <svg class="gdy-icon mb-3" width="48" height="48">
                                        <use href="<?= htmlspecialchars(asset_url('assets/icons/godyar-icons.svg'), ENT_QUOTES, 'UTF-8') ?>#bell"></use>
                                    </svg><br>
                                    إعدادات الإشعارات قيد التطوير
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- مودال تغيير الصورة الشخصية -->
<div class="modal fade" id="avatarModal" tabindex="-1" aria-labelledby="avatarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="avatarModalLabel">تغيير الصورة الشخصية</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="update_avatar">
                    
                    <div class="mb-3">
                        <label for="avatar" class="form-label">اختر صورة جديدة</label>
                        <input type="file" class="form-control" id="avatar" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" required>
                        <small class="text-muted">الحد الأقصى 2 ميجابايت - الصيغ المسموحة: JPG, PNG, GIF, WEBP</small>
                    </div>
                    
                    <div class="preview-avatar text-center mb-3" style="display: none;">
                        <img src="" alt="معاينة" class="img-thumbnail rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">رفع الصورة</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>

.profile-page .profile-sidebar {
    border: none;
    border-radius: 12px;
    overflow: hidden;
}

.profile-page .avatar-circle {
    width: 120px;
    height: 120px;
    background: linear-gradient(135deg, 
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);
}

.profile-page .avatar-initials {
    font-size: 3rem;
    font-weight: bold;
    color: white;
    text-transform: uppercase;
}

.profile-page .list-group-item {
    border: none;
    padding: 0.8rem 1.25rem;
    display: flex;
    align-items: center;
    transition: all 0.2s;
}

.profile-page .list-group-item.active {
    background: linear-gradient(135deg, 
    color: white;
    border: none;
}

.profile-page .list-group-item .gdy-icon {
    fill: currentColor;
}

.profile-page .social-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: 
    color: 
    transition: all 0.2s;
}

.profile-page .social-link:hover {
    transform: translateY(-2px);
}

.profile-page .social-link.facebook:hover {
    background: 
    color: white;
}

.profile-page .social-link.twitter:hover {
    background: 
    color: white;
}

.profile-page .social-link.instagram:hover {
    background: linear-gradient(45deg, 
    color: white;
}

.profile-page .social-link.youtube:hover {
    background: 
    color: white;
}

.profile-page .social-link.tiktok:hover {
    background: 
    color: white;
}

.profile-page .social-link.website:hover {
    background: 
    color: white;
}

.profile-page .stat-item {
    text-align: center;
}

.profile-page .stat-value {
    font-weight: bold;
    font-size: 1.2rem;
}

.profile-page .news-card {
    transition: transform 0.2s, box-shadow 0.2s;
    border: 1px solid rgba(0,0,0,0.08);
    overflow: hidden;
    height: 100%;
}

.profile-page .news-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.profile-page .news-image {
    height: 140px;
    object-fit: cover;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

[dir="rtl"] .profile-page .me-1,
[dir="rtl"] .profile-page .me-2 {
    margin-left: 0.25rem !important;
    margin-right: 0 !important;
}

[dir="rtl"] .profile-page .ms-2 {
    margin-right: 0.5rem !important;
    margin-left: 0 !important;
}
</style>

<script>

document.getElementById('avatar')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.querySelector('.preview-avatar');
            const img = preview.querySelector('img');
            img.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const hash = window.location.hash;
    if (hash) {
        const tab = document.querySelector(`a[href="${hash}"]`);
        if (tab) {
            tab.click();
        }
    }
});
</script>

<?php

require_once dirname(__DIR__) . '/frontend/views/partials/footer.php';
ob_end_flush();
?>