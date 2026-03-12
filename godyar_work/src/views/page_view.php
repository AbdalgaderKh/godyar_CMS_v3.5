<?php
declare(strict_types=1);

$pageTitle = trim((string)($page['title'] ?? 'صفحة'));
$pageSlug = trim((string)($page['slug'] ?? ''));
$pageContent = trim((string)($page['content'] ?? ''));
$pageDescription = trim((string)($page['meta_description'] ?? ''));

if (!function_exists('gdy_h')) {
    function gdy_h($v): string {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

$fallback = [
    'about' => '
        <h2>من نحن</h2>
        <p>Godyar News Platform منصة إخبارية وإعلامية متكاملة، صُممت لإدارة المحتوى الرقمي باحترافية وسهولة، مع دعم واضح للأخبار والأقسام والصفحات الثابتة وتجربة القراءة الحديثة.</p>
        <p>تهدف المنصة إلى تمكين المؤسسات الإعلامية والناشرين من إطلاق مواقع مرنة وسريعة وقابلة للتطوير.</p>
    ',
    'privacy' => '
        <h2>سياسة الخصوصية</h2>
        <p>نحن نحترم خصوصية المستخدمين والزوار، ونعمل على حماية البيانات التي يتم جمعها أثناء استخدام الموقع وفق أفضل الممارسات الممكنة.</p>
        <ul>
            <li>قد يتم جمع بيانات تقنية مثل نوع المتصفح وعنوان IP لأغراض الأمان والتحسين.</li>
            <li>لا يتم بيع البيانات الشخصية أو إساءة استخدامها.</li>
            <li>يمكن تحديث هذه السياسة عند الحاجة بما يتوافق مع متطلبات التشغيل والتطوير.</li>
        </ul>
    ',
    'terms' => '
        <h2>الشروط والأحكام</h2>
        <p>باستخدامك لهذا الموقع، فإنك توافق على الالتزام بالشروط والأحكام المعمول بها.</p>
        <ul>
            <li>عدم إساءة استخدام الموقع أو محاولة الإضرار به.</li>
            <li>احترام حقوق النشر والملكية الفكرية للمحتوى.</li>
            <li>يجوز لإدارة الموقع تعديل هذه الشروط عند الحاجة.</li>
        </ul>
    ',
    'contact' => '
        <h2>اتصل بنا</h2>
        <p>يمكنك التواصل معنا عبر صفحة الاتصال أو عبر بيانات التواصل الرسمية الخاصة بالموقع.</p>
        <p>لإضافة نموذج مراسلة فعلي، يمكنك دمج هذه الصفحة مع نظام البريد أو الحفظ داخل قاعدة البيانات.</p>
    ',
];

$contentToRender = $pageContent !== '' ? $pageContent : ($fallback[$pageSlug] ?? '<p>لا يوجد محتوى متاح لهذه الصفحة حالياً.</p>');
?>
<main class="gdy-main">
  <section class="container" style="padding-top:24px;padding-bottom:32px;">
    <div class="category-head" style="margin-bottom:24px;padding:20px;border-radius:16px;background:#fff;box-shadow:0 8px 24px rgba(0,0,0,.05)">
      <h1 class="category-title" style="margin:0 0 8px;font-size:28px;font-weight:700;">
        <?= gdy_h($pageTitle) ?>
      </h1>
      <?php if ($pageDescription !== ''): ?>
        <p style="margin:0;color:#6b7280;line-height:1.8;"><?= gdy_h($pageDescription) ?></p>
      <?php endif; ?>
    </div>

    <article class="page-content" style="background:#fff;border:1px solid 
      <?= $contentToRender ?>
    </article>
  </section>
</main>
