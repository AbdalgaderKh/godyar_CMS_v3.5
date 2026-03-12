<?php

$lang = $lang ?? 'ar';
$dir  = $dir  ?? ($lang === 'ar' ? 'rtl' : 'ltr');
$baseUrl = $baseUrl ?? '';

$error = $login_error ?? '';
$identifier = $login_identifier ?? '';
$csrf = $login_csrf ?? '';
$next = $login_next ?? '';
$wait = (int)($login_wait ?? 0);

$meta_title = $meta_title ?? 'تسجيل الدخول';
$meta_description = $meta_description ?? 'سجّل الدخول للوصول إلى حسابك على Godyar News.';
$canonical_url = $canonical_url ?? (rtrim($baseUrl, '/') . '/login');
$langPrefix = (!empty($lang) && in_array($lang, ['ar','en','fr'], true)) ? '/' . $lang : '';
$formAction = htmlspecialchars(rtrim($baseUrl,'/') . $langPrefix . '/login', ENT_QUOTES, 'UTF-8');

require ROOT_PATH . '/frontend/views/partials/header.php';
?>

<main class="gdy-main">
  <div class="container gdy-login-pad">
    <div class="gdy-auth">
      <div class="gdy-auth-card card">
        <div class="card-body">
          <h1 class="gdy-auth-title">تسجيل الدخول</h1>
          <p class="gdy-auth-subtitle">أدخل بياناتك للوصول إلى حسابك.</p>

          <?php if ($wait > 0): ?>
            <div class="alert alert-warning rounded-4" role="alert">
              محاولات كثيرة. الرجاء الانتظار <?= (int)$wait ?> ثانية ثم المحاولة مجدداً.
            </div>
          <?php endif; ?>

          <?php if (!empty($error)): ?>
            <div class="alert alert-danger rounded-4" role="alert">
              <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
            </div>
          <?php endif; ?>

          <form method="post" action="<?= $formAction ?>" autocomplete="on" class="gdy-auth-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>">
            <?php if (!empty($next)): ?>
              <input type="hidden" name="next" value="<?= htmlspecialchars((string)$next, ENT_QUOTES, 'UTF-8') ?>">
            <?php endif; ?>

            <div class="mb-3">
              <label for="login_identifier" class="form-label">البريد الإلكتروني أو اسم المستخدم</label>
              <input
                id="login_identifier"
                name="login"
                type="text"
                class="form-control"
                required
                autocomplete="username"
                value="<?= htmlspecialchars((string)$identifier, ENT_QUOTES, 'UTF-8') ?>"
                placeholder="name@example.com أو username">
            </div>

            <div class="mb-3">
              <label for="login_password" class="form-label">كلمة المرور</label>
              <input
                id="login_password"
                name="password"
                type="password"
                class="form-control"
                required
                autocomplete="current-password"
                placeholder="••••••••">
            </div>

            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" value="1" id="remember_me" name="remember_me">
              <label class="form-check-label" for="remember_me">تذكرني</label>
            </div>

            <div class="d-flex align-items-center justify-content-between flex-wrap gdy-gap-10">
              <button class="btn btn-primary" type="submit">دخول</button>
              <a href="<?= htmlspecialchars(rtrim($baseUrl,'/') . '/register', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">إنشاء حساب</a>
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
