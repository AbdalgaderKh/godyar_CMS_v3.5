
هذه الحزمة تضيف وتصلح:

- نظام الصفحات الثابتة:
  - `/page/about`
  - `/page/privacy`
  - `/page/terms`
  - `/page/contact`
  - `/contact`
- View آمن يعرض المحتوى أو fallback افتراضي
- Controller مع fallback على اللغة الحالية أو العامة
- ملف routes snippet لإضافته داخل `app.php`
- ملف `.htaccess` snippet لدعم الروابط المباشرة
- ملف SQL لإنشاء جدول `pages` إذا لم يكن موجودًا

- `src/Controllers/PageController.php`
- `src/views/page_view.php`
- `patches/app_routes_snippet.php`
- `patches/.htaccess_pages_snippet.txt`
- `database/pages_patch.sql`

انسخ الملفات إلى نفس المسارات داخل المشروع.

افتح الملف `app.php` وأضف محتوى `patches/app_routes_snippet.php`
قبل fallback النهائي أو قبل 404 / dispatch الأخير.

أضف محتوى `patches/.htaccess_pages_snippet.txt`
قبل قاعدة:
`RewriteRule ^ app.php [L,QSA]`

شغّل الملف:
`database/pages_patch.sql`

يمكنك بعد ذلك إدخال السجلات:
- about
- privacy
- terms
- contact

إن لم توجد سجلات، سيعرض النظام محتوى fallback تلقائيًا.

هذا Patch مستقل وآمن، ولا يعتمد على وجود محتوى فعلي داخل قاعدة البيانات حتى تبدأ الصفحات بالعمل.
