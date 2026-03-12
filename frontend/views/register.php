<?php

$lang = $lang ?? 'ar';
$dir  = $dir  ?? ($lang === 'ar' ? 'rtl' : 'ltr');
$baseUrl = $baseUrl ?? '';

$error = (string)($reg_error ?? '');
$success = (string)($reg_success ?? '');
$csrf = (string)($reg_csrf ?? '');
$old = is_array($reg_old ?? null) ? $reg_old : [];

$meta_title = $meta_title ?? 'إنشاء حساب';
$meta_description = $meta_description ?? 'أنشئ حسابك على Godyar News.';
$canonical_url = $canonical_url ?? (rtrim($baseUrl, '/') . '/register');
$langPrefix = (!empty($lang) && in_array($lang, ['ar','en','fr'], true)) ? '/' . $lang : '';
$formAction = htmlspecialchars(rtrim($baseUrl,'/') . $langPrefix . '/register', ENT_QUOTES, 'UTF-8');

require ROOT_PATH . '/frontend/views/partials/header.php';
?>

<main class="gdy-main">
  <div class="container gdy-login-pad">
    <div class="gdy-auth">
      <div class="gdy-auth-card card">
        <div class="card-body">
          <h1 class="gdy-auth-title">إنشاء حساب</h1>
          <p class="gdy-auth-subtitle">أدخل بياناتك لإنشاء حساب جديد.</p>

          <?php if ($error !== ''): ?>
            <div class="alert alert-danger rounded-4" role="alert">
              <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
          <?php endif; ?>

          <?php if ($success !== ''): ?>
            <div class="alert alert-success rounded-4" role="status">
              <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
            </div>
          <?php endif; ?>

          <form method="post" action="<?= $formAction ?>" class="gdy-auth-form" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

            <div class="mb-3">
              <label for="reg_name" class="form-label">الاسم (اختياري)</label>
              <input
                id="reg_name"
                type="text"
                name="name"
                class="form-control"
                autocomplete="name"
                value="<?= htmlspecialchars((string)($old['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="mb-3">
              <label for="reg_username" class="form-label">اسم المستخدم (اختياري)</label>
              <input
                id="reg_username"
                type="text"
                name="username"
                class="form-control"
                autocomplete="username"
                value="<?= htmlspecialchars((string)($old['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="mb-3">
              <label for="reg_email" class="form-label">البريد الإلكتروني</label>
              <input
                id="reg_email"
                type="email"
                name="email"
                class="form-control"
                required
                autocomplete="email"
                inputmode="email"
                value="<?= htmlspecialchars((string)($old['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                placeholder="name@example.com">
            </div>

            <div class="mb-3">
              <label for="reg_pass" class="form-label">كلمة المرور</label>
              <input
                id="reg_pass"
                type="password"
                name="password"
                class="form-control"
                required
                minlength="6"
                autocomplete="new-password"
                placeholder="••••••••">
            </div>

            <div class="mb-3">
              <label for="reg_pass2" class="form-label">تأكيد كلمة المرور</label>
              <input
                id="reg_pass2"
                type="password"
                name="confirm_password"
                class="form-control"
                required
                minlength="6"
                autocomplete="new-password"
                placeholder="••••••••">
            </div>

            <div class="d-flex align-items-center justify-content-between flex-wrap gdy-gap-10">
              <button type="submit" class="btn btn-primary">إنشاء حساب</button>
              <a href="<?= htmlspecialchars(rtrim($baseUrl,'/') . '/login', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">لدي حساب</a>
            </div>
          </form>

          <div class="gdy-auth-links">
            <a href="<?= htmlspecialchars($baseUrl ?: '/', ENT_QUOTES, 'UTF-8') ?>">العودة للرئيسية</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require ROOT_PATH . '/frontend/views/partials/footer.php'; ?>
