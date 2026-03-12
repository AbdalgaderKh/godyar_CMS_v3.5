<?php

if (!function_exists('h')) {
    function h($v): string {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

$slug = $page['slug'] ?? '';
$cspNonce = defined('GDY_CSP_NONCE') ? (string)GDY_CSP_NONCE : '';
$nonceAttr = ($cspNonce !== '') ? ' nonce="' . h($cspNonce) . '"' : '';
?>

<?php if ($slug === 'contact'): ?>

  <div class = "gdy-contact-wrapper fade-in">
    <div class = "d-flex justify-content-between align-items-baseline mb-2">
      <div>
        <h1 class = "gdy-contact-title">اتصل بنا</h1>
        <p class = "gdy-contact-sub">
          قم بالتواصل معنا من خلال وسائل التواصل الاجتماعي، أو قم بإرسال رسالة عبر النموذج التالي .
        </p>
      </div>
    </div>

    <div class = "gdy-contact-grid">
      <!-- عمود المعلومات -->
      <div class = "gdy-contact-info">
        <div class = "gdy-contact-social">
          <a href = "#" aria-label = "YouTube"><svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#youtube"></use></svg></a>
          <a href = "#" aria-label = "Instagram"><svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#instagram"></use></svg></a>
          <a href = "#" aria-label = "LinkedIn"><svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg></a>
          <a href = "#" aria-label = "Twitter"><svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#x"></use></svg></a>
          <a href = "#" aria-label = "Facebook"><svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#facebook"></use></svg></a>
        </div>

        <div class = "gdy-contact-block">
          <div class = "gdy-contact-block-title">
            <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
            <span>العنوان</span>
          </div>
          <p>
            يمكنك هنا وضع عنوان شركتك أو مقر موقعك، مثال:<br>
            Building 3, twofour54 P .O .Box 77866, Abu Dhabi, United Arab Emirates
<?php
$baseUrl = $baseUrl ?? '';
$pageNotFound = $pageNotFound ?? false;
?>
        <div class = "gdy-contact-block">
          <div class = "gdy-contact-block-title">
            <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>

        <div class = "gdy-contact-block">
          <div class = "gdy-contact-block-title">
            <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
            <span>اتصل معنا عبر</span>
          </div>
          <p>
            الهاتف: +000 000 0000<br>
            الفاكس: +000 000 0001
          </p>
        </div>

        <div class = "gdy-contact-block">
          <div class = "gdy-contact-block-title">
            <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
            <span>أرسل بريدك الإلكتروني إلى</span>
          </div>
          <p>
            <a href = "mailto:admin@example.com">admin@example .com</a>
          </p>
        </div>
      </div>

      <!-- عمود النموذج -->
      <div>
        <!-- ✅ تعديل الـ action ليشير إلى contact-submit .php -->
        <form class = "gdy-contact-form" method = "post" action = "<?php echo h($baseUrl); ?>/contact-submit.php">
          <?php if (function_exists('csrf_field')) { csrf_field(); } ?>
          <div class = "row g-2 mb-2">
            <div class = "col-md-6">
              <label for = "contact_name">
                الاسم <span class = "required">*</span>
              </label>
              <!-- ✅ تغيير اسم الحقل إلى name ليتوافق مع سكربت المعالجة -->
              <input type = "text" name = "name" id = "contact_name" class = "form-control" required>
            </div>
            <div class = "col-md-6">
              <label for = "contact_email">
                البريد الإلكتروني <span class = "required">*</span>
              </label>
              <input type = "email" name = "email" id = "contact_email" class = "form-control" required>
            </div>
          </div>

          <div class = "mb-2">
            <label for = "contact_message">
              النص <span class = "required">*</span>
            </label>
            <textarea name = "message" id = "contact_message" rows = "8" class = "form-control" required></textarea>
          </div>

          <div class = "gdy-contact-submit">
            <button type = "submit">
              أرسل
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

<?php else: ?>

  <div class = "fade-in">
    <div class = "hero-card" style = "margin-bottom:18px;">
      <div class = "hero-badge">
        <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
        <span><?php echo $pageNotFound ? 'صفحة غير موجودة' : 'صفحة ثابتة'; ?></span>
      </div>
      <h1 class = "hero-title"><?php echo h($page['title'] ?? ''); ?></h1>
      <div class = "hero-meta">
        <span>
          <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
          <?php echo h(date('Y-m-d', strtotime($page['created_at'] ?? 'now'))); ?>
        </span>
        <?php if (!empty($page['updated_at']) && $page['updated_at'] !== $page['created_at']): ?>
          <span>
            <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
            آخر تحديث: <?php echo h(date('Y-m-d', strtotime($page['updated_at']))); ?>
          </span>
        <?php endif; ?>
      </div>
    </div>

    <div class = "gdy-page-content" style = "
        background:#ffffff;
        border-radius:18px;
        border:1px solid 
        box-shadow:0 12px 25px rgba(15,23,42,0.08);
        padding:20px 18px;
        font-size: . 92rem;
        color:#0f172a;
    ">
      <?php echo $page['content'] ?? ''; ?>
    </div>
  </div>

<?php endif; ?>

<?php
