<?php

header('Location: ../slider/index.php', true, 301);
exit;

require_once __DIR__ . '/../_admin_guard.php';
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth.php';

use Godyar\Auth;

if (!Auth::isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$currentPage = 'sliders';
$pageTitle = __('t_5d1adeeb8d', 'إضافة شريحة جديدة');

$pdo = gdy_pdo_safe();
$errors = [];
$success = null;

$tableExists = false;
if ($pdo instanceof PDO) {
    try {
        $check = gdy_db_stmt_table_exists($pdo, 'sliders');
        $tableExists = $check && $check->fetchColumn();
    } catch (\Throwable $e) {
        error_log(__('t_bc532aa1a3', 'خطأ في التحقق من جدول السلايدر: ') . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    
    if (function_exists('verify_csrf')) {
        try { verify_csrf(); } catch (\Throwable $e) {
            if (function_exists('gdy_security_log')) { gdy_security_log('csrf_failed', ['file' => __FILE__]); }
            $_SESSION['error_message'] = $_SESSION['error_message'] ?? 'انتهت صلاحية الجلسة، يرجى إعادة المحاولة.';
            header('Location: ' . ($_SERVER['REQUEST_URI'] ?? 'index.php'));
            exit;
        }
    }
$title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $buttonText = trim($_POST['button_text'] ?? '');
    $buttonUrl = trim($_POST['button_url'] ?? '');
    $displayOrder = intval($_POST['display_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    
    if (empty($title)) {
        $errors[] = __('t_a0754d34d5', 'حقل العنوان مطلوب');
    }
    
    if (empty($_FILES['image']['name'])) {
        $errors[] = __('t_f71df02c7f', 'حقل الصورة مطلوب');
    }
    
    if (empty($errors)) {
        try {
            require_once __DIR__ . '/../../includes/classes/SafeUploader.php';
            $SafeUploaderClass = 'Godyar' .chr(92) . 'SafeUploader';

            $destAbs = rtrim((string)ROOT_PATH, '/') . '/assets/uploads/sliders';
            $urlPrefix = '/assets/uploads/sliders';

            
            if (!is_dir($destAbs)) {
                if (function_exists('gdy_mkdir')) {
                    gdy_mkdir($destAbs, 0755, true);
                } else {
                    @mkdir($destAbs, 0755, true);
                }
            }
            $ht = $destAbs . '/.htaccess';
            if (is_dir($destAbs) && !is_file($ht)) {
                $rules = <<<'HTACCESS'
Options -Indexes
<FilesMatch "\.(php|phtml|phar)$">
  Require all denied
</FilesMatch>
HTACCESS;
                @file_put_contents($ht, $rules);
            }

            $res = $SafeUploaderClass::upload($_FILES['image'], [
                'dest_abs_dir' => $destAbs,
                'url_prefix' => $urlPrefix,
                'max_bytes' => 5 * 1024 * 1024,
                'allowed_ext' => ['jpg','jpeg','png','gif','webp'],
                'allowed_mime' => [
                    'jpg' => ['image/jpeg'],
                    'jpeg' => ['image/jpeg'],
                    'png' => ['image/png'],
                    'gif' => ['image/gif'],
                    'webp' => ['image/webp'],
                ],
                'prefix' => 'slider_',
            ]);

            if (($res['success'] ?? false) !== true) {
                throw new Exception((string)($res['error'] ?? __('t_a579958e8f', 'فشل في رفع الصورة')));
            }

            $imageUrl = (string)$res['rel_url'];

            
            if ($tableExists) {
                $stmt = $pdo->prepare("
                    INSERT INTO sliders (title, description, image_path, button_text, button_url, display_order, is_active)
                    VALUES (:title, :description, :image_path, :button_text, :button_url, :display_order, :is_active)
                ");

                $stmt->execute([
                    ':title' => $title,
                    ':description' => $description,
                    ':image_path' => $imageUrl,
                    ':button_text' => $buttonText,
                    ':button_url' => $buttonUrl,
                    ':display_order' => $displayOrder,
                    ':is_active' => $isActive
                ]);

                $success = __('t_0f995f1d71', 'تم إضافة الشريحة بنجاح!');
            }

        } catch (\Throwable $e) {
            $errors[] = __('t_44f36bbf4b', 'حدث خطأ أثناء حفظ البيانات: ') . $e->getMessage();
        }
            
    }
}

require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../layout/sidebar.php';
?>

<div class = "admin-content container-fluid py-4">
    <div class = "admin-content gdy-page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <div>
            <h1 class = "h4 text-white mb-1"><?php echo h(__('t_5d1adeeb8d', 'إضافة شريحة جديدة')); ?></h1>
            <p class = "text-muted mb-0 small"><?php echo h(__('t_4913c6eeca', 'أضف شريحة عرض جديدة إلى السلايدر الرئيسي')); ?></p>
        </div>
        <div class = "mt-3 mt-md-0">
            <a href = "index.php" class = "btn btn-outline-light">
                <svg class = "gdy-icon me-1" aria-hidden = "true" focusable = "false"><use href = "#arrow-left"></use></svg><?php echo h(__('t_19ae074cbf', 'العودة للقائمة')); ?>
            </a>
        </div>
    </div>

    <?php if (!$tableExists): ?>
        <div class = "alert alert-warning">
            <svg class = "gdy-icon me-2" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
            <?php echo h(__('t_3b529cf8d7', 'جدول السلايدر غير موجود.')); ?> 
            <a href = "create_table.php" class = "alert-link"><?php echo h(__('t_096b487b25', 'أنشئ الجدول أولاً')); ?></a>
        </div>
    <?php else: ?>
        <?php if ($success): ?>
            <div class = "alert alert-success alert-dismissible fade show" role = "alert">
                <?php echo htmlspecialchars($success); ?>
                <button type = "button" class = "btn-close" data-bs-dismiss = "alert" aria-label = "Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class = "alert alert-danger alert-dismissible fade show" role = "alert">
                <ul class = "mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type = "button" class = "btn-close" data-bs-dismiss = "alert" aria-label = "Close"></button>
            </div>
        <?php endif; ?>

        <div class = "row">
            <div class = "col-lg-8">
                <div class = "card shadow-sm">
                    <div class = "card-body">
                        <form method = "post" enctype = "multipart/form-data">
  <?php if (function_exists('csrf_field')) { csrf_field(); } else { ?>
">
  <?php } ?>
">

                            <div class = "row g-3">
                                <div class = "col-12">
                                    <label class = "form-label"><?php echo h(__('t_4e2b58ea7a', 'عنوان الشريحة')); ?></label>
                                    <input type = "text" name = "title" class = "form-control" required 
                                           value = "<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                                </div>
                                
                                <div class = "col-12">
                                    <label class = "form-label"><?php echo h(__('t_f58d38d563', 'الوصف')); ?></label>
                                    <textarea name = "description" class = "form-control" rows = "3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class = "col-12">
                                    <label class = "form-label"><?php echo h(__('t_5fc681f4cf', 'صورة الشريحة')); ?></label>
                                    <input type = "file" name = "image" class = "form-control" accept = "image/*" required>
                                    <div class = "form-text"><?php echo h(__('t_f19ff51209', 'الحجم المقترح: 1920x800 بكسل-الأنواع المسموحة: JPG, PNG, GIF')); ?></div>
                                </div>
                                
                                <div class = "col-md-6">
                                    <label class = "form-label"><?php echo h(__('t_a955d706fe', 'نص الزر')); ?></label>
                                    <input type = "text" name = "button_text" class = "form-control" 
                                           value = "<?php echo htmlspecialchars($_POST['button_text'] ?? ''); ?>"
                                           placeholder = "<?php echo h(__('t_6f435a52f4', 'مثال: اكتشف المزيد')); ?>">
                                </div>
                                
                                <div class = "col-md-6">
                                    <label class = "form-label"><?php echo h(__('t_763705bf30', 'رابط الزر')); ?></label>
                                    <input type = "url" name = "button_url" class = "form-control" 
                                           value = "<?php echo htmlspecialchars($_POST['button_url'] ?? ''); ?>"
                                           placeholder = "<?php echo h(__('t_0977904375', 'مثال: https://example.com')); ?>">
                                </div>
                                
                                <div class = "col-md-6">
                                    <label class = "form-label"><?php echo h(__('t_2fcc9e97b9', 'ترتيب العرض')); ?></label>
                                    <input type = "number" name = "display_order" class = "form-control" 
                                           value = "<?php echo htmlspecialchars($_POST['display_order'] ?? 0); ?>" min = "0">
                                </div>
                                
                                <div class = "col-md-6">
                                    <div class = "form-check form-switch mt-4">
                                        <input class = "form-check-input" type = "checkbox" name = "is_active" id = "isActive" value = "1" checked>
                                        <label class = "form-check-label" for = "isActive"><?php echo h(__('t_62898c5d16', 'شريحة نشطة')); ?></label>
                                    </div>
                                </div>
                                
                                <div class = "col-12">
                                    <button type = "submit" class = "btn btn-primary w-100">
                                        <svg class = "gdy-icon me-2" aria-hidden = "true" focusable = "false"><use href = "#save"></use></svg><?php echo h(__('t_915ff03e02', 'حفظ الشريحة')); ?>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class = "col-lg-4">
                <div class = "card shadow-sm">
                    <div class = "card-header bg-dark text-light">
                        <h6 class = "card-title mb-0"><?php echo h(__('t_02e8b338c4', 'نصائح للإضافة')); ?></h6>
                    </div>
                    <div class = "card-body">
                        <div class = "mb-3">
                            <strong><?php echo h(__('t_eac34e0740', '🖼️ الصور:')); ?></strong>
                            <p class = "small text-muted mb-0"><?php echo h(__('t_f31d8c4fe6', 'استخدم صوراً عالية الجودة بحجم 1920x800 بكسل للحصول على أفضل نتائج العرض.')); ?></p>
                        </div>
                        <div class = "mb-3">
                            <strong><?php echo h(__('t_a13c78c2db', '📝 المحتوى:')); ?></strong>
                            <p class = "small text-muted mb-0"><?php echo h(__('t_52fa060f07', 'اجعل العناوين مختصرة وجذابة، والوصف لا يزيد عن 150 حرفاً.')); ?></p>
                        </div>
                        <div class = "mb-3">
                            <strong><?php echo h(__('t_14df2374ce', '🔗 الروابط:')); ?></strong>
                            <p class = "small text-muted mb-0"><?php echo h(__('t_912da00e09', 'استخدم روابط واضحة وذات صلة بمحتوى الشريحة.')); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../layout/footer.php';
?>
