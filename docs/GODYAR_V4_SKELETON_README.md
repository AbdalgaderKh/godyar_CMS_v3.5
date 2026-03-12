# Godyar CMS v4 Skeleton Patch

هذه الحزمة تضيف **هيكل v4 أولي** فوق مشروعك الحالي بدون استبدال المسار الحالي افتراضيًا.

## ماذا أضيف؟
- `bootstrap_v4.php`
- `public/index.v4.php`
- `app/` كنواة v4
- `themes/default/` كقالب موحد
- `public/.htaccess.v4-snippet` لتفعيل المسار الجديد لاحقًا

## لماذا هو آمن؟
- لم يتم استبدال `index.php` الحالي.
- لم يتم تغيير `app.php` الحالي.
- لم يتم حذف الملفات القديمة.
- تفعيل v4 يتم يدويًا بعد الاختبار.

## مسارات تجريبية بعد التفعيل
- `/ar/`
- `/ar/page/about`
- `/ar/news/demo-news-1`
- `/ar/category/general`
- `/ar/contact`
- `/sitemap.xml`

## ملاحظات مهمة
- هذه **Skeleton عملية** وليست migration كاملة.
- repositories الحالية هنا placeholders لتسهيل الربط المرحلي.
- الخطوة التالية هي ربط المستودعات الجديدة بقاعدة البيانات الحالية بدل البيانات الافتراضية.

## الخطوات التالية المقترحة
1. ربط `NewsRepository` و `CategoryRepository` و `PageRepository` بقاعدة البيانات الحالية.
2. ربط v4 بالهيدر والفوتر النهائيين من هوية الموقع.
3. إدخال redirects آمنة للمسارات القديمة بعد الاختبار.
4. توسيع ThemeService لدعم partials و menus و assets loader.
5. بدء فصل admin عن front.
