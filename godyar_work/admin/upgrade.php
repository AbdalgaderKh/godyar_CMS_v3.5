<?php

require_once __DIR__ . '/_admin_guard.php';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../tools/migrate_lib.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    gdy_session_start();
}

$root = realpath(__DIR__ . '/..');
$migrationsDir = $root . '/database/migrations';

$upgradeKey = (string)(getenv('UPGRADE_KEY') ?: '');
$lockFile = $root . '/storage/upgrade.done';
$isLocked = is_file($lockFile);

header('Content-Type: text/html; charset=utf-8');

?><!doctype html>
<html lang="ar" dir="rtl">
<head><meta charset="utf-8">
<title>ترقية قاعدة البيانات — Godyar News Platform</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:system-ui,Segoe UI,Tahoma,Arial; background:#0b1220; color:#e8eefc; padding:24px;}
    .card{max-width:900px;margin:0 auto;background:#0f1b33;border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:18px;}
    button{background:#19c37d;border:none;color:#071b12;padding:10px 14px;border-radius:12px;font-weight:700;cursor:pointer}
    pre{background:#071127;border-radius:12px;padding:12px;overflow:auto;max-height:55vh}
    .muted{opacity:.8}
    .warn{color:#ffd37a}
  </style>
</head>
<body>
  <div class="card">
    <h2>ترقية قاعدة البيانات</h2>
    <p class="muted">هذه الصفحة تشغّل سكربت الترقية الموجود في <code>tools/migrate.php</code>. يُفضّل أخذ نسخة احتياطية من قاعدة البيانات قبل الترقية.</p>
    <form method="post">
      <?php if ($upgradeKey !== ''): ?>
        <p class="muted">الحماية مفعّلة: يجب إدخال <code>UPGRADE_KEY</code> من ملف <code>.env</code>.</p>
        <label class="muted" for="uk">مفتاح الترقية</label><br>
        <input id="uk" name="upgrade_key" type="password" autocomplete="off" style="width:100%;max-width:420px;padding:10px 12px;border-radius:12px;border:1px solid rgba(255,255,255,.15);background:#071127;color:#e8eefc;margin:8px 0 14px;">
      <?php endif; ?>

      <?php if ($isLocked): ?>
        <p class="warn">تم تشغيل الترقية مسبقًا. إذا رغبت بإعادة التشغيل (غير مستحسن)، احذف الملف: <code>storage/upgrade.done</code>.</p>
      <?php endif; ?>
      <button type="submit" name="run" value="1">تشغيل الترقية الآن</button>
    </form>

    <?php if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['run'])): ?>
      <h3>النتيجة</h3>
      <pre><?php
        if ($upgradeKey !== '') {
            $given = (string)($_POST['upgrade_key'] ?? '');
            if (!hash_equals($upgradeKey, $given)) {
                echo htmlspecialchars("مفتاح الترقية غير صحيح.\n", ENT_QUOTES, 'UTF-8');
                echo htmlspecialchars("تأكد من قيمة UPGRADE_KEY في .env ثم أعد المحاولة.\n", ENT_QUOTES, 'UTF-8');
                exit;
            }
        }

        if ($isLocked) {
            echo htmlspecialchars("تم تشغيل الترقية مسبقًا. لن يتم إعادة التشغيل حفاظًا على سلامة البيانات.\n", ENT_QUOTES, 'UTF-8');
            echo htmlspecialchars("إذا كنت متأكدًا، احذف storage/upgrade.done ثم أعد المحاولة.\n", ENT_QUOTES, 'UTF-8');
            exit;
        }

        
        $pdo = function_exists('gdy_pdo_safe') ? gdy_pdo_safe() : null;
        if (!$pdo) {
            $out = "تعذر الاتصال بقاعدة البيانات من داخل لوحة التحكم. تحقق من إعدادات DB في .env وملفات config.\n";
        } else {
            list($ok, $out) = gdy_run_migrations($pdo, $migrationsDir);
            if (!$ok) {
                $out .= "\nفشلت بعض الترقيات. راجع السطر FAIL أعلاه.\n";
            } else {
                
                @is_dir($root . '/storage') || @mkdir($root . '/storage', 0755, true);
                @file_put_contents($lockFile, "upgraded_at=" . date('c') . "\n");
            }
        }
        echo htmlspecialchars($out ?: 'No output', ENT_QUOTES, 'UTF-8');
      ?></pre>
      <p class="warn">ملاحظة: تم تشغيل الترقية من داخل PHP نفسه لتجنب مشاكل CLI في بعض الاستضافات.</p>
    <?php endif; ?>
  </div>
</body>
</html>
