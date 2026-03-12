# Phase 20 Report

تم تنفيذ هذه المرحلة على النسخة الحالية بدون كسر الكود.

## ما تم تنفيذه
- Autosave إلى السيرفر عبر `admin/news/autosave.php`
  - يحفظ مسودة التحرير داخل `storage/v4/news_autosave/*.json`
  - يعمل من صفحات إنشاء/تعديل الخبر.
- Revision Compare داخل المحرر عبر `admin/news/ajax_revision_compare.php`
  - مقارنة نسخة محفوظة مع النسخة الحالية أو نسخة أخرى.
- دعم أفضل للنسخ من Word/Office والتطبيقات المشابهة
  - لصق كنص منظف داخل المحتوى بدل إدخال تنسيقات مزعجة.
- زر إدراج للرابط الداخلي
  - داخل اقتراحات الروابط الداخلية في محرر الخبر.
- Headline Analyzer
  - تحليل سريع لعنوان الخبر مع درجة وتوصية.
- Scheduled Publish UX
  - إظهار حالة الجدولة مباشرة داخل المحرر.
- تحسين صفحة إدارة المقالات
  - إضافة وضع عرض مختلط: قائمة / بطاقات.

## الملفات المضافة
- `admin/news/autosave.php`
- `admin/news/ajax_revision_compare.php`
- `admin/assets/css/admin-news-index.css`
- `admin/assets/js/admin-news-index.js`

## الملفات المعدلة
- `admin/news/create.php`
- `admin/news/edit.php`
- `admin/news/index.php`
- `admin/assets/js/admin-news-editor.js`
- `admin/assets/css/admin-news-editor.css`

## التحقق
- تم فحص ملفات PHP المعدلة والجديدة بواسطة `php -l`.
- لا توجد أخطاء نحوية في الملفات المعدلة.
