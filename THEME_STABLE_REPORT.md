# Godyar CMS v4.1 Stable Theme Build

## ما تم تنفيذه
- ترقية الثيم الافتراضي إلى نسخة مستقرة بواجهة أوضح.
- إضافة ملفات CSS منظمة:
  - `themes/default/assets/css/utilities.css`
  - `themes/default/assets/css/components.css`
  - `themes/default/assets/css/dark.css`
- تحديث `master.php` لتحميل الطبقات الجديدة وتفعيل `defer` لملف JS.
- إضافة الوضع الليلي مع حفظ الحالة في `localStorage`.
- إضافة شريط تقدم القراءة أعلى الصفحة.
- إضافة شريط أخبار عاجلة في الهيدر.
- تحسين بطاقات الأخبار لدعم الصور وتحميلها lazy.
- تحسين صفحات:
  - home
  - page
  - news
  - category
  - contact
- تحديث `theme.json` للثيمات `default`, `ocean`, `aurora`.

## أهم الميزات الجديدة
- Dark mode
- Sticky header
- Breaking news bar
- Reading progress bar
- Lazy-loaded images
- News cards محسنة
- Hero sections أوضح
- Footer أكثر اتساقًا

## الملفات المعدلة
- `themes/default/layout/master.php`
- `themes/default/layout/header.php`
- `themes/default/layout/footer.php`
- `themes/default/partials/news-card.php`
- `themes/default/pages/home.php`
- `themes/default/pages/page.php`
- `themes/default/pages/news.php`
- `themes/default/pages/category.php`
- `themes/default/pages/contact.php`
- `themes/default/theme.json`
- `themes/ocean/theme.json`
- `themes/aurora/theme.json`
- `themes/default/assets/js/app.js`

## الملفات الجديدة
- `themes/default/assets/css/utilities.css`
- `themes/default/assets/css/components.css`
- `themes/default/assets/css/dark.css`

## التحقق
- تم فحص ملفات PHP المعدلة بـ `php -l` ونجحت.

## اقتراحات المرحلة التالية
- Theme settings center من لوحة الإدارة
- Theme customizer مباشر للألوان والخطوط
- Skeleton loaders للأقسام الإخبارية
- Advanced mobile nav
- Search overlay
- Trending strip مربوط بالبيانات الفعلية
