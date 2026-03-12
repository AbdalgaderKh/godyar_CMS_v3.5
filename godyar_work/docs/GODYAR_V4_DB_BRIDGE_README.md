# Godyar CMS v4 Database Bridge + Legacy Redirect Map

## ما الذي تمت إضافته
- جسر قراءة آمن من v4 إلى قاعدة بيانات المشروع الحالية عبر `includes/classes/DB.php`.
- Repositories محدثة تقرأ من الجداول الحقيقية إن كانت متاحة:
  - `news`
  - `categories`
  - `pages`
  - `settings`
  - `site_settings`
- طبقة Redirects مركزية داخل:
  - `app/Config/legacy_redirects.php`
  - `app/Services/LegacyRedirectService.php`
- Migration جاهز لجدول `redirects` لمتابعة التحويلات مستقبلاً.

## سلوك الأمان
- إذا لم تتوفر قاعدة البيانات أو فشل الاتصال، يعود النظام تلقائيًا إلى بيانات fallback.
- لا يتم تعديل قاعدة البيانات من v4 في هذه المرحلة؛ القراءة فقط.
- لم يتم استبدال `index.php` الحالي.

## المسارات القديمة التي تم تجهيزها
- `/home.php` → `/`
- `/index.php` → `/`
- `/page/contact` → `/contact`
- `/contact/index.php` → `/contact`
- `/contact-submit.php` → `/contact`
- `/category.php?slug=...` → `/category/...`
- `/news_single.php?slug=...` → `/news/...`
- `/news_detail.php?slug=...` → `/news/...`
- `/news_report.php?slug=...` → `/news/...`

## ما الذي يحتاج ربطًا بعد ذلك
1. تفعيل قاعدة بيانات v4 على staging أولًا.
2. ربط `ContactController@send` بجدول `contact_messages`.
3. إضافة `MenuRepository` و`MenuService`.
4. إدخال `RedirectRepository` لقراءة جدول `redirects` بدل الاكتفاء بالملف الثابت.
5. إدخال `MediaService` لتوحيد الصور المصغرة و WebP.
