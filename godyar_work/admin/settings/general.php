<?php

$__gdy_embed = (defined('GDY_SETTINGS_EMBED') && GDY_SETTINGS_EMBED === true);
if ($__gdy_embed === false) {
    require_once __DIR__ . '/_settings_guard.php';
    require_once __DIR__ . '/_settings_meta.php';
    settings_apply_context();
    require_once __DIR__ . '/../layout/app_start.php';
}

$__gdy_tab = 'general';

$notice = '';
$error = '';

if (!function_exists('gdy_branding_file_path')) {
    function gdy_branding_file_path(): string {
        return __DIR__ . '/../../storage/site_branding.php';
    }
}

if (!function_exists('gdy_branding_write')) {
    function gdy_branding_write(array $data): void {
        $path = gdy_branding_file_path();
        $dir = dirname($path);
        if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
        $payload = '<?php return ' . var_export($data, true) . ';';
        @file_put_contents($path, $payload, LOCK_EX);
    }
}

if (!function_exists('gdy_branding_read')) {
    function gdy_branding_read(): array {
        $path = gdy_branding_file_path();
        if (!is_file($path)) return [];
        $data = require $path;
        return is_array($data) ? $data : [];
    }
}

if (!function_exists('gdy_pick_uploaded_image')) {
    function gdy_pick_uploaded_image(array $files, array $preferred = ['site_logo','logo','branding_logo','site[logo]']): ?array {
        foreach ($preferred as $key) {
            if (isset($files[$key]) && is_array($files[$key])) {
                return $files[$key];
            }
        }
        foreach ($files as $item) {
            if (is_array($item) && isset($item['tmp_name'])) {
                return $item;
            }
        }
        return null;
    }
}

if (!function_exists('gdy_admin_store_uploaded_image')) {
    function gdy_admin_store_uploaded_image(array $file, string $prefix = 'logo_'): string {
        $tmp  = (string)($file['tmp_name'] ?? '');
        $name = (string)($file['name'] ?? 'file');
        $size = (int)($file['size'] ?? 0);
        if ($tmp === '' || !is_file($tmp)) {
            throw new RuntimeException('ملف الرفع غير صالح.');
        }
        $mime = '';
        if (function_exists('finfo_open')) {
            $fi = gdy_finfo_open(FILEINFO_MIME_TYPE);
            if ($fi) { $mime = (string)gdy_finfo_file($fi, $tmp); gdy_finfo_close($fi); }
        }
        $allowed = ['image/png' => 'png','image/jpeg' => 'jpg','image/webp' => 'webp','image/gif' => 'gif','image/svg+xml' => 'svg','image/x-icon' => 'ico','image/vnd.microsoft.icon' => 'ico'];
        $extGuess = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if ($extGuess === 'jpeg') { $extGuess = 'jpg'; }
        if (($mime === '' || !isset($allowed[$mime])) && in_array($extGuess, ['png','jpg','webp','gif','svg','ico'], true)) {
            $mimeMap = ['png'=>'image/png','jpg'=>'image/jpeg','webp'=>'image/webp','gif'=>'image/gif','svg'=>'image/svg+xml','ico'=>'image/x-icon'];
            $mime = $mimeMap[$extGuess];
        }
        if (!isset($allowed[$mime])) {
            throw new RuntimeException('صيغة الملف غير مدعومة.');
        }
        $ext = $allowed[$mime];
        $roots = [];
        foreach ([defined('ROOT_PATH') ? ROOT_PATH : '', dirname(__DIR__, 2), $_SERVER['DOCUMENT_ROOT'] ?? ''] as $r) {
            $r = rtrim((string)$r, "/\\");
            if ($r !== '' && is_dir($r)) { $roots[] = $r; }
        }
        $roots = array_values(array_unique($roots));
        $rel = '/assets/uploads/site/' . $prefix . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $lastErr = 'تعذر تحديد مسار حفظ الملف.';
        foreach ($roots as $root) {
            $dest = $root . $rel;
            $dir = dirname($dest);
            if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
            $ok = false;
            if (function_exists('gdy_move_uploaded_file')) { $ok = gdy_move_uploaded_file($tmp, $dest); }
            if (!$ok && is_uploaded_file($tmp)) { $ok = @move_uploaded_file($tmp, $dest); }
            if (!$ok) { $ok = @copy($tmp, $dest); }
            if ($ok && is_file($dest)) {
                @chmod($dest, 0644);
                return $rel;
            }
            $lastErr = 'تعذر حفظ الملف على السيرفر.';
        }
        throw new RuntimeException($lastErr);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($__gdy_embed === false || (string)($_POST['settings_tab'] ?? '') === $__gdy_tab)) {
    if (function_exists('verify_csrf') === true) { verify_csrf(); }

    try {
        if (stripos((string)($_SERVER['CONTENT_TYPE'] ?? ''), 'multipart/form-data') !== false && empty($_FILES)) {
            error_log('[settings_general] multipart request received without files; check PHP upload limits or webserver temp dir.');
        }
        
    
	    
	    $logoUrl = settings_get('site.logo', settings_get('site_logo', ''));
    if (isset($_POST['remove_logo']) === true) {
        $logoUrl = '';
    }
    $logoFile = gdy_pick_uploaded_image($_FILES, ['site_logo','logo','branding_logo']);
    if (is_array($logoFile) && (int)(($logoFile['error'] ?? UPLOAD_ERR_NO_FILE)) !== UPLOAD_ERR_NO_FILE) {
        if ((int)($logoFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $size = (int)($logoFile['size'] ?? 0);
            if ($size > 2 * 1024 * 1024) {
                throw new RuntimeException('حجم الشعار أكبر من 2MB.');
            }
            $logoUrl = gdy_admin_store_uploaded_image($logoFile, 'logo_');
            if (function_exists('gdy_normalize_site_logo_value')) {
                $logoUrl = gdy_normalize_site_logo_value((string)$logoUrl, (string)base_url());
            }
        } else {
            throw new RuntimeException('فشل رفع الشعار.');
        }
    }

    
	    
	    $faviconUrl = settings_get('site.favicon', settings_get('site_favicon', ''));
    if (isset($_POST['remove_favicon']) === true) {
        $faviconUrl = '';
    }
    $faviconFile = gdy_pick_uploaded_image($_FILES, ['site_favicon','favicon','branding_favicon']);
    if (is_array($faviconFile) && (int)(($faviconFile['error'] ?? UPLOAD_ERR_NO_FILE)) !== UPLOAD_ERR_NO_FILE) {
        if ((int)($faviconFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $size = (int)($faviconFile['size'] ?? 0);
            if ($size > 1024 * 1024) {
                throw new RuntimeException('حجم الأيقونة أكبر من 1MB.');
            }
            $faviconUrl = gdy_admin_store_uploaded_image($faviconFile, 'favicon_');
            if (function_exists('gdy_normalize_site_logo_value')) {
                $faviconUrl = gdy_normalize_site_logo_value((string)$faviconUrl, (string)base_url());
            }
        } else {
            throw new RuntimeException('فشل رفع الأيقونة.');
        }
    }

    $themeColor = trim((string)($_POST['theme_color'] ?? ''));
	settings_save([
            'site.name' => trim((string)($_POST['site_name'] ?? '')),
            'site.desc' => trim((string)($_POST['site_desc'] ?? '')),
            'site.url' => trim((string)($_POST['site_url'] ?? '')),
            'site.email' => trim((string)($_POST['site_email'] ?? '')),
            'site.phone' => trim((string)($_POST['site_phone'] ?? '')),
            'site.address' => trim((string)($_POST['site_address'] ?? '')),
	            
	            'site.logo' => (string)$logoUrl,
	            'site_logo' => (string)$logoUrl,
            'logo' => (string)$logoUrl,
	            'site.favicon' => (string)$faviconUrl,
	            'site_favicon' => (string)$faviconUrl,
            'site.theme_color' => (string)$themeColor,

            'media.compress.enabled' => isset($_POST['media_compress_enabled']) ? 1 : 0,
            'media.compress.max_width' => (int)($_POST['media_compress_max_width'] ?? 1920),
            'media.compress.quality' => (int)($_POST['media_compress_quality'] ?? 82),
            'media.watermark.enabled' => isset($_POST['media_watermark_enabled']) ? 1 : 0,
            'media.watermark.opacity' => (int)($_POST['media_watermark_opacity'] ?? 35),
        ]);

        if (function_exists('settings_set')) {
            settings_set('site.logo', (string)$logoUrl);
            settings_set('site_logo', (string)$logoUrl);
            settings_set('logo', (string)$logoUrl);
            settings_set('site.favicon', (string)$faviconUrl);
            settings_set('site_favicon', (string)$faviconUrl);
        }
        
        if (function_exists('gdy_branding_write')) {
            gdy_branding_write([
                'site.logo'    => (string)$logoUrl,
                'site_logo'    => (string)$logoUrl,
                'logo'         => (string)$logoUrl,
                'site.favicon' => (string)$faviconUrl,
                'site_favicon' => (string)$faviconUrl,
            ]);
        }
        $brandingNow = function_exists('gdy_branding_read') ? gdy_branding_read() : [];
        $savedLogo = settings_get('site.logo', settings_get('site_logo', settings_get('logo', '')));
        if (trim((string)$savedLogo) === '' && is_array($brandingNow)) {
            $savedLogo = (string)($brandingNow['site.logo'] ?? $brandingNow['site_logo'] ?? $brandingNow['logo'] ?? '');
        }
        if ($logoUrl !== '' && trim((string)$savedLogo) === '') {
            throw new RuntimeException('تم رفع الشعار لكن لم يتم حفظ مساره في الإعدادات.');
        }
        $notice = __('t_36112f9024', 'تم حفظ الإعدادات العامة بنجاح.');
    } catch (\Throwable $e) {
        $error = __('t_4fa410044f', 'حدث خطأ أثناء الحفظ.');
        error_log('[settings_general] ' . $e->getMessage());
    }
}

$site_name = settings_get('site.name', '');
$site_desc = settings_get('site.desc', '');
$site_url = settings_get('site.url', '');
$site_email = settings_get('site.email', '');
$site_phone = settings_get('site.phone', '');
$site_address = settings_get('site.address', '');
	$__branding = function_exists('gdy_branding_read') ? gdy_branding_read() : [];
	$site_logo = settings_get('site.logo', settings_get('site_logo', settings_get('logo', (string)($__branding['site.logo'] ?? $__branding['site_logo'] ?? $__branding['logo'] ?? ''))));
	$site_favicon = settings_get('site.favicon', settings_get('site_favicon', (string)($__branding['site.favicon'] ?? $__branding['site_favicon'] ?? '')));
$theme_color = settings_get('site.theme_color', '#0ea5e9');
$media_compress_enabled = (int)settings_get('media.compress.enabled', 1);
$media_compress_max_width = (int)settings_get('media.compress.max_width', 1920);
$media_compress_quality = (int)settings_get('media.compress.quality', 82);
$media_watermark_enabled = (int)settings_get('media.watermark.enabled', 0);
$media_watermark_opacity = (int)settings_get('media.watermark.opacity', 35);
?>

<div class = "row g-3">
    <div class = "col-md-3">
      <?php include __DIR__ . '/_settings_nav.php'; ?>
    </div>

    <div class = "col-md-9">
      <div class = "card p-4">
<?php if ($notice): ?>
          <div class = "alert alert-success"><?php echo h($notice); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
          <div class = "alert alert-danger"><?php echo h($error); ?></div>
        <?php endif; ?>

        <form method = "post" enctype = "multipart/form-data">
          <?php if (function_exists('csrf_token')): ?>
            <input type = "hidden" name = "csrf_token" value = "<?php echo h(csrf_token()); ?>">
          <?php endif; ?>
          <input type = "hidden" name = "settings_tab" value = "<?php echo h($__gdy_tab); ?>">

          <div class = "mb-3">
            <label class = "form-label" for = "site_name"><?php echo h(__('t_2bd584d5c7', 'اسم الموقع')); ?></label>
            <input id = "site_name" class = "form-control" name = "site_name" value = "<?php echo h($site_name); ?>">
          </div>

          <div class = "mb-3">
            <label class = "form-label" for = "site_desc"><?php echo h(__('t_81edd198f5', 'وصف مختصر')); ?></label>
            <textarea id = "site_desc" class = "form-control" name = "site_desc" rows = "3"><?php echo h($site_desc); ?></textarea>
          </div>

          <div class = "mb-3">
            <label class = "form-label" for = "site_url"><?php echo h(__('t_cccf89f08f', 'رابط الموقع')); ?></label>
            <input id = "site_url" class = "form-control" name = "site_url" value = "<?php echo h($site_url); ?>">
          </div>

          <div class = "row">
            <div class = "col-md-6 mb-3">
              <label class = "form-label" for = "site_logo">شعار الموقع</label>
              <?php if (!empty($site_logo)): ?>
                <div class = "mb-2">
                  <img src = "<?php echo h($site_logo); ?>" alt = "Logo" style = "max-height:64px;max-width:100%;background:#fff;border:1px solid 
                </div>
                <div class = "form-check mb-2">
                  <input class = "form-check-input" type = "checkbox" name = "remove_logo" id = "remove_logo" value = "1">
                  <label class = "form-check-label" for = "remove_logo">إزالة الشعار</label>
                </div>
              <?php endif; ?>
              <input id = "site_logo" class = "form-control" type = "file" name = "site_logo" accept = "image/*">
              <div class = "form-text">PNG/JPG/WebP/GIF/SVG — الحد 2MB</div>
            </div>

            <div class = "col-md-6 mb-3">
              <label class = "form-label" for = "site_favicon">أيقونة الموقع (Favicon)</label>
              <?php if (!empty($site_favicon)): ?>
                <div class = "mb-2">
                  <img src = "<?php echo h($site_favicon); ?>" alt = "Favicon" style = "height:32px;width:32px;background:#fff;border:1px solid 
                </div>
                <div class = "form-check mb-2">
                  <input class = "form-check-input" type = "checkbox" name = "remove_favicon" id = "remove_favicon" value = "1">
                  <label class = "form-check-label" for = "remove_favicon">إزالة الأيقونة</label>
                </div>
              <?php endif; ?>
              <input id = "site_favicon" class = "form-control" type = "file" name = "site_favicon" accept = "image/png,image/x-icon,image/svg+xml">
              <div class = "form-text">PNG/ICO/SVG — الحد 1MB</div>
            </div>
          </div>

          <div class = "mb-3">
            <label class = "form-label" for = "theme_color">لون الثيم (PWA / المتصفح)</label>
            <input id = "theme_color" class = "form-control" type = "color" name = "theme_color" value = "<?php echo h($theme_color ?: '#0ea5e9'); ?>" style = "max-width:180px">
          </div>

          <hr>
          <h6 class = "mt-3 mb-2">إعدادات الصور (رفع المحرر)</h6>

          <div class = "form-check form-switch mb-2">
            <input class = "form-check-input" type = "checkbox" id = "media_compress_enabled" name = "media_compress_enabled" <?php echo $media_compress_enabled ? 'checked' : ''; ?>>
            <label class = "form-check-label" for = "media_compress_enabled">ضغط/تصغير الصور تلقائيًا عند الرفع من المحرر</label>
          </div>

          <div class = "row g-2 mb-3">
            <div class = "col-md-4">
              <label class = "form-label" for = "media_compress_max_width">أقصى عرض (px)</label>
              <input id = "media_compress_max_width" class = "form-control" type = "number" name = "media_compress_max_width" value = "<?php echo (int)$media_compress_max_width; ?>" min = "640" max = "4096">
            </div>
            <div class = "col-md-4">
              <label class = "form-label" for = "media_compress_quality">جودة JPEG/WebP (40-95)</label>
              <input id = "media_compress_quality" class = "form-control" type = "number" name = "media_compress_quality" value = "<?php echo (int)$media_compress_quality; ?>" min = "40" max = "95">
            </div>
          </div>

          <div class = "form-check form-switch mb-2">
            <input class = "form-check-input" type = "checkbox" id = "media_watermark_enabled" name = "media_watermark_enabled" <?php echo $media_watermark_enabled ? 'checked' : ''; ?>>
            <label class = "form-check-label" for = "media_watermark_enabled">إضافة علامة مائية (باستخدام شعار الموقع) عند رفع الصور من المحرر</label>
          </div>

          <div class = "row g-2 mb-1">
            <div class = "col-md-4">
              <label class = "form-label" for = "media_watermark_opacity">شفافية العلامة (10-90)</label>
              <input id = "media_watermark_opacity" class = "form-control" type = "number" name = "media_watermark_opacity" value = "<?php echo (int)$media_watermark_opacity; ?>" min = "10" max = "90">
            </div>
            <div class = "col-md-8 d-flex align-items-end">
              <div class = "form-text">يتم استخدام شعار الموقع المرفوع أعلاه كعلامة مائية . يفضل أن يكون PNG بخلفية شفافة . </div>
            </div>
          </div>

          <div class = "row mt-3">
            <div class = "col-md-4 mb-3">
              <label class = "form-label" for = "site_email"><?php echo h(__('t_2a1ce89dca', 'بريد التواصل')); ?></label>
              <input id = "site_email" class = "form-control" type = "email" name = "site_email" value = "<?php echo h($site_email); ?>">
            </div>
            <div class = "col-md-4 mb-3">
              <label class = "form-label" for = "site_phone"><?php echo h(__('t_0947ad5747', 'رقم الهاتف')); ?></label>
              <input id = "site_phone" class = "form-control" name = "site_phone" value = "<?php echo h($site_phone); ?>">
            </div>
            <div class = "col-md-4 mb-3">
              <label class = "form-label" for = "site_address"><?php echo h(__('t_6dc6588082', 'العنوان')); ?></label>
              <input id = "site_address" class = "form-control" name = "site_address" value = "<?php echo h($site_address); ?>">
            </div>
          </div>

          <button type = "submit" class = "btn btn-primary"><?php echo h(__('t_871a087a1d', 'حفظ')); ?></button>
        </form>

      </div>
    </div>
  </div>

<?php if ($__gdy_embed === false) { require_once __DIR__ . '/../layout/app_end.php'; } ?>
