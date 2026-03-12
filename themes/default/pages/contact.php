<section class="g-v4-wrap g-v4-card g-v4-prose g-v4-article">
    <div class="g-v4-page-hero">
        <span class="g-v4-kicker">تواصل</span>
        <h1>اتصل بنا</h1>
        <p class="g-v4-summary">نرحب باستفساراتكم وملاحظاتكم من خلال النموذج التالي.</p>
    </div>
    <?php if (!empty($flash)): ?>
        <div class="g-v4-alert <?= !empty($flash['ok']) ? 'ok' : 'error' ?>">
            <?php foreach (($flash['messages'] ?? []) as $m): ?><p><?= e($m) ?></p><?php endforeach; ?>
        </div>
    <?php unset($_SESSION['gdy_v4_contact_flash']); endif; ?>
    <form method="post" action="<?= e(godyar_v4_url($locale ?? 'ar', 'contact/send')) ?>" class="g-v4-form">
        <?= function_exists('csrf_field') ? csrf_field() : '' ?>
        <label>الاسم<input type="text" name="name" required></label>
        <label>البريد<input type="email" name="email" required></label>
        <label>الرسالة<textarea name="message" rows="6" required></textarea></label>
        <button type="submit">إرسال</button>
    </form>
</section>
