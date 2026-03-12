<?php
$__gdy_embed = (defined('GDY_SETTINGS_EMBED') && GDY_SETTINGS_EMBED === true);
if ($__gdy_embed === false) {
    require_once __DIR__ . '/_settings_guard.php';
    require_once __DIR__ . '/_settings_meta.php';
    settings_apply_context();
    require_once __DIR__ . '/../layout/app_start.php';
}

$__gdy_tab = 'pwa';

$notice = '';
$error = '';

if (!function_exists('gdy_b64url')) {
    function gdy_b64url(string $bin): string {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }
}

if (!function_exists('gdy_generate_vapid_keys')) {
    
    function gdy_generate_vapid_keys(): array {
        $key = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ]);
        if (!$key) {
            throw new RuntimeException('OpenSSL failed to generate EC key');
        }

        $details = openssl_pkey_get_details($key);
        if (!is_array($details) || !isset($details['ec']) || !is_array($details['ec'])) {
            throw new RuntimeException('OpenSSL failed to read key details');
        }

        $ec = $details['ec'];
        $x = (string)($ec['x'] ?? '');
        $y = (string)($ec['y'] ?? '');
        $d = (string)($ec['d'] ?? '');

        if (strlen($x) !== 32 || strlen($y) !== 32 || strlen($d) !== 32) {
            throw new RuntimeException('Unexpected EC key size');
        }

        $publicRaw = "\x04" . $x . $y;
        $publicKey = gdy_b64url($publicRaw);
        $privateKey = gdy_b64url($d);

        return ['public' => $publicKey, 'private' => $privateKey];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($__gdy_embed === false || (string)($_POST['settings_tab'] ?? '') === $__gdy_tab)) {
    if (function_exists('verify_csrf')) { verify_csrf(); }

    $action = (string)($_POST['action'] ?? '');
    try {
        if ($action === 'generate_vapid') {
            $keys = gdy_generate_vapid_keys();
            $subject = trim((string)($_POST['push_subject'] ?? settings_get('push.subject', 'mailto:admin@' . ($_SERVER['HTTP_HOST'] ?? 'example.com'))));
            if ($subject === '') {
                $subject = 'mailto:admin@' . ($_SERVER['HTTP_HOST'] ?? 'example.com');
            }
            settings_save([
                'push.vapid_public' => $keys['public'],
                'push.vapid_private' => $keys['private'],
                'push.subject' => $subject,
                'push.enabled' => '1',
            ]);
            $notice = __('t_push_keys_generated', 'تم توليد مفاتيح VAPID وحفظها بنجاح.');
        } elseif ($action === 'save_push') {
            $subject = trim((string)($_POST['push_subject'] ?? ''));
            if ($subject === '') {
                $subject = 'mailto:admin@' . ($_SERVER['HTTP_HOST'] ?? 'example.com');
            }
            $enabled = !empty($_POST['push_enabled']) ? '1' : '0';
            settings_save([
                'push.subject' => $subject,
                'push.enabled' => $enabled,
            ]);
    if (!($pdo instanceof PDO)) {
        throw new RuntimeException('Database connection not available');
    }
            $notice = __('t_saved', 'تم الحفظ.');

} elseif ($action === 'send_push') {
    if (!($pdo instanceof PDO)) {
        throw new RuntimeException('Database connection not available');
    }

    $title = trim((string)($_POST['push_title'] ?? ''));
    $body = trim((string)($_POST['push_body'] ?? ''));
    $url = trim((string)($_POST['push_url'] ?? ''));
    $ttl = (int)($_POST['push_ttl'] ?? 300);
    $testOnly = !empty($_POST['push_test_only']);

    if ($title === '' && $body === '') {
        throw new RuntimeException(__('t_push_msg_required', 'اكتب عنوانًا أو نصًا للإشعار.'));
    }
    if ($url === '') {
        $url = '/';
    }
    if ($ttl < 0) $ttl = 0;
    if ($ttl > 86400) $ttl = 86400;

    $pushEnabled = (string)settings_get('push.enabled', '0');
    if ($pushEnabled !== '1') {
        throw new RuntimeException(__('t_push_disabled', 'الإشعارات غير مفعّلة. فعّلها أولاً.'));
    }

    $vapidPublic = (string)settings_get('push.vapid_public', '');
    $vapidPrivate = (string)settings_get('push.vapid_private', '');
    $subject = (string)settings_get('push.subject', 'mailto:admin@' . ($_SERVER['HTTP_HOST'] ?? 'example.com'));

    $payload = [
        'title' => $title !== '' ? $title : 'Godyar',
        'body' => $body,
        'url' => $url,
        'icon' => '/assets/images/icons/icon-192.png',
        'badge' => '/assets/images/icons/icon-192.png',
    ];

    try {
        $svc = new \Godyar\Services\WebPushService($pdo, $vapidPublic, $vapidPrivate, $subject);
        $sendRes = $svc->sendBroadcast($payload, $ttl, $testOnly);

        
        $logDir = ABSPATH . '/storage/push';
        if (!is_dir($logDir)) {
            gdy_mkdir($logDir, 0755, true);
        }
        $logLine = [
            'ts' => date('c'),
            'title' => $payload['title'],
            'url' => $payload['url'],
            'ttl' => $ttl,
            'test_only' => $testOnly,
            'result' => $sendRes,
        ];
        gdy_file_put_contents($logDir . '/send-log-' .date('Y-m-d') . '.jsonl', json_encode($logLine, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . "\n", FILE_APPEND);
        $total = (int)($sendRes['total'] ?? 0);
        if ($total === 0) {
            
            $sendRes['ok'] = true;
            $notice = __('t_push_no_subs', 'لا يوجد مشتركين للإشعارات حتى الآن. افتح الموقع من الجوال وفعّل الإشعارات أولاً ثم أعد الإرسال.') . ' 📲';
        } elseif (empty($sendRes['ok']) === false) {
            $notice = __('t_push_sent', 'تم إرسال الإشعار. ') . '✅';
        } else {
            $errs = implode(' | ', array_slice((array)($sendRes['errors'] ?? []), 0, 3));
            $error = __('t_push_failed', 'فشل إرسال الإشعار: ') . ($errs ?: __('t_unknown', 'غير معروف'));
        }
$GLOBALS['__push_send_result'] = $sendRes;
    } catch (\Throwable $e) {
        throw new RuntimeException($e->getMessage());
    }
        }
    } catch (\Throwable $e) {
        $error = __('t_error', 'حدث خطأ: ') . $e->getMessage();
    }
}

$pushEnabled = (string)settings_get('push.enabled', '0');
$pushSubject = (string)settings_get('push.subject', 'mailto:admin@' . ($_SERVER['HTTP_HOST'] ?? 'example.com'));
$vapidPublic = (string)settings_get('push.vapid_public', '');
$vapidPrivate = (string)settings_get('push.vapid_private', '');

$subsCount = 0;
try {
    if (isset($pdo) && $pdo instanceof PDO) {
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS push_subscriptions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            endpoint_hash CHAR(40) NOT NULL,
            user_id INT UNSIGNED NULL,
            endpoint TEXT NOT NULL,
            p256dh TEXT NOT NULL,
            auth TEXT NOT NULL,
            prefs_json JSON NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY uniq_endpoint (endpoint_hash),
            KEY idx_user (user_id)
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4");
        $st = $pdo->query("SELECT COUNT(*) FROM push_subscriptions");
        $subsCount = (int)($st ? $st->fetchColumn() : 0);
    }
} catch (\Throwable $e) {
    $subsCount = 0;
}
?>

<div class = "row g-3">
  <div class = "col-lg-4">
    <?php require_once __DIR__ . '/_settings_nav.php'; ?>
  </div>

  <div class = "col-lg-8">

    <?php if ($notice): ?>
      <div class = "alert alert-success"><?php echo h($notice); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class = "alert alert-danger"><?php echo h($error); ?></div>
    <?php endif; ?>

    <div class = "card mb-3">
      <div class = "card-header d-flex align-items-center justify-content-between">
        <div>
          <div class = "fw-semibold"><?php echo h(__('t_pwa_push_title', 'PWA & Push')); ?></div>
          <div class = "text-muted small"><?php echo h(__('t_pwa_push_hint', 'تهيئة مفاتيح الإشعارات (Web Push) وإعدادات التثبيت كتطبيق.')); ?></div>
        </div>
        <span class = "badge text-bg-secondary"><?php echo h(__('t_subscribers', 'مشتركين')); ?>: <?php echo (int)$subsCount; ?></span>
      </div>

      <div class = "card-body">

        <form method = "post" class = "mb-4">
          <?php if (function_exists('csrf_field')) echo csrf_field(); ?>
          <input type = "hidden" name = "settings_tab" value = "<?php echo h($__gdy_tab); ?>" />
          <input type = "hidden" name = "action" value = "save_push"     />

          <div class = "form-check form-switch mb-3">
            <input class = "form-check-input" type = "checkbox" role = "switch" id = "push_enabled" name = "push_enabled" value = "1" <?php echo ($pushEnabled === '1') ? 'checked' : ''; ?> />
            <label class = "form-check-label" for = "push_enabled"><?php echo h(__('t_push_enable', 'تفعيل الاشتراك بالإشعارات')); ?></label>
          </div>

          <div class = "mb-3">
            <label class = "form-label" for = "push_subject"><?php echo h(__('t_push_subject', 'Subject (mailto)')); ?></label>
            <input class = "form-control" id = "push_subject" name = "push_subject" value = "<?php echo h($pushSubject); ?>" placeholder = "mailto:admin@<?php echo h($_SERVER['HTTP_HOST'] ?? 'example.com'); ?>" />
            <div class = "form-text"><?php echo h(__('t_push_subject_help', 'يُستخدم داخل VAPID كبيان تواصل (mailto: أو https://).')); ?></div>
          </div>

          <button class = "btn btn-primary" type = "submit"><?php echo h(__('t_save', 'حفظ')); ?></button>
        </form>

        <hr class = "my-4"     />

        <form method = "post">
          <?php if (function_exists('csrf_field')) echo csrf_field(); ?>
          <input type = "hidden" name = "settings_tab" value = "<?php echo h($__gdy_tab); ?>" />
                    <input type = "hidden" name = "push_subject" value = "<?php echo h($pushSubject); ?>" />

          <div class = "d-flex align-items-center justify-content-between mb-3">
            <div class = "fw-semibold"><?php echo h(__('t_vapid_keys', 'مفاتيح VAPID')); ?></div>
            <button class = "btn btn-outline-dark" type = "submit" name = "action" value = "generate_vapid"><?php echo h(__('t_generate', 'توليد مفاتيح')); ?></button>
          </div>

          <div class = "row g-3">
            <div class = "col-12">
              <label class = "form-label"><?php echo h(__('t_vapid_public', 'Public Key')); ?></label>
              <textarea class = "form-control" rows = "2" readonly><?php echo h($vapidPublic); ?></textarea>
              <div class = "form-text"><?php echo h(__('t_vapid_public_help', 'انسخه كما هو. سيتم استخدامه في المتصفح للاشتراك.')); ?></div>
            </div>
            <div class = "col-12">
              <label class = "form-label"><?php echo h(__('t_vapid_private', 'Private Key')); ?></label>
              <textarea class = "form-control" rows = "2" readonly><?php echo h($vapidPrivate); ?></textarea>
              <div class = "form-text"><?php echo h(__('t_vapid_private_help', 'مهم: لا تشاركه. يُستخدم فقط لإرسال الإشعارات من السيرفر.')); ?></div>
            </div>
          </div>

          
          <hr class = "my-4"     />

          <div class = "d-flex align-items-center justify-content-between mb-3">
            <div class = "fw-semibold"><?php echo h(__('t_push_send_now', 'إرسال إشعار الآن')); ?></div>
            <div class = "small text-muted"><?php echo h(__('t_push_send_hint', 'إرسال يدوي (Broadcast) لكل المشتركين')); ?></div>
          </div>

          <?php $sendRes = $GLOBALS['__push_send_result'] ?? null; ?>
          <?php if (is_array($sendRes)) : ?>
            <div class = "alert <?php echo !empty($sendRes['ok']) ? 'alert-success' : 'alert-danger'; ?> py-2">
              <div class = "fw-semibold mb-1"><?php echo h(__('t_push_send_result', 'نتيجة الإرسال')); ?></div>
              <div class = "small">
                <?php echo h(__('t_sent', 'تم الإرسال')); ?>: <?php echo (int)($sendRes['sent'] ?? 0); ?> —
                <?php echo h(__('t_failed', 'فشل')); ?>: <?php echo (int)($sendRes['failed'] ?? 0); ?> —
                <?php echo h(__('t_total', 'الإجمالي')); ?>: <?php echo (int)($sendRes['total'] ?? 0); ?>
              </div>
              <?php if (!empty($sendRes['errors']) && is_array($sendRes['errors'])) : ?>
                <div class = "small text-muted mt-1"><?php echo h(implode(' | ', array_slice($sendRes['errors'], 0, 2))); ?></div>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <div class = "row g-3">
            <div class = "col-12">
              <label class = "form-label"><?php echo h(__('t_push_title', 'عنوان الإشعار')); ?></label>
              <input class = "form-control" type = "text" name = "push_title" value = "<?php echo h((string)($_POST['push_title'] ?? '')); ?>" placeholder = "<?php echo h(__('t_example', 'مثال')); ?>: خبر عاجل" />
            </div>
            <div class = "col-12">
              <label class = "form-label"><?php echo h(__('t_push_body', 'نص الإشعار')); ?></label>
              <textarea class = "form-control" name = "push_body" rows = "3" placeholder = "<?php echo h(__('t_push_body_ph', 'اكتب ملخصًا قصيرًا...')); ?>"><?php echo h((string)($_POST['push_body'] ?? '')); ?></textarea>
            </div>
            <div class = "col-md-8">
              <label class = "form-label"><?php echo h(__('t_push_url', 'الرابط عند الضغط')); ?></label>
              <input class = "form-control" type = "text" name = "push_url" value = "<?php echo h((string)($_POST['push_url'] ?? '/')); ?>" placeholder = "/ar/" />
              <div class = "form-text"><?php echo h(__('t_push_url_help', 'يمكنك وضع رابط داخل الموقع مثل /ar/news/123')); ?></div>
            </div>
            <div class = "col-md-4">
              <label class = "form-label"><?php echo h(__('t_push_ttl', 'مدة TTL بالثواني')); ?></label>
              <input class = "form-control" type = "number" name = "push_ttl" min = "0" max = "86400" value = "<?php echo h((string)($_POST['push_ttl'] ?? '300')); ?>" />
            </div>
            <div class = "col-12">
              <div class = "form-check">
                <input class = "form-check-input" type = "checkbox" id = "push_test_only" name = "push_test_only" value = "1" <?php echo !empty($_POST['push_test_only']) ? 'checked' : ''; ?> />
                <label class = "form-check-label" for = "push_test_only"><?php echo h(__('t_push_test_only', 'إرسال تجريبي لأول مشترك فقط')); ?></label>
              </div>
            </div>
            <div class = "col-12 d-flex gap-2">
              <button class = "btn btn-dark" type = "submit" name = "action" value = "send_push"><?php echo h(__('t_send_now', 'إرسال الآن')); ?></button>
              <button class = "btn btn-outline-secondary" type = "submit" name = "action" value = "send_push" data-check-target = "#push_test_only"><?php echo h(__('t_send_test', 'إرسال تجريبي')); ?></button>
            </div>
          </div>

<div class = "alert alert-warning mt-3 mb-0">
            <div class = "fw-semibold mb-1"><?php echo h(__('t_push_note', 'ملاحظة')); ?></div>
            <div class = "small text-muted">
              <?php echo h(__('t_push_note_body', 'هذه الصفحة تُجهّز مفاتيح الاشتراك. يمكنك إرسال إشعار يدوي الآن من نفس الصفحة بعد توليد مفاتيح VAPID وتفعيل الإشعارات.')); ?>
            </div>
          </div>
        </form>

      </div>
    </div>

  </div>
</div>

<?php if ($__gdy_embed === false) { require_once __DIR__ . '/../layout/app_end.php'; } ?>
