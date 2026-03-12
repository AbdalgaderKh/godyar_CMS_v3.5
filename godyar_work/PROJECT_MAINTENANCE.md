# PROJECT_MAINTENANCE.md

## أهم الملفات
- عرض الخبر:
  - `frontend/views/news_single_legacy.php`
  - `frontend/views/news_single.php`
  - `frontend/views/news_detail.php`
- المحرر:
  - `assets/admin/editor/gdy-editor.js`
  - `assets/admin/editor/gdy-editor.css`
  - `assets/admin/editor/editor-init.js`
- التعليقات:
  - `frontend/comment_add.php`
  - `admin/comments/index.php`
  - `admin/comments/moderate.php`
- المراجعات:
  - `includes/news_revisions.php`
  - `admin/revisions/index.php`
  - `admin/revisions/view_revision.php`
  - `admin/revisions/restore_revision.php`

## صفحات الفحص
- صحة النظام: `/admin/system/health.php`
- Sitemap: `/sitemap.php`

## Cron مقترح
- تنظيف cache يوميًا
- إعادة بناء sitemap يوميًا
- حذف autosave القديم أسبوعيًا

## عند تعطل المحرر
1. تأكد من تحميل:
   - `gdy-editor.js`
   - `editor-init.js`
2. تأكد أن `footer.php` يطبع `$pageScripts`
3. تأكد أن textarea تحتوي:
   - `data-gdy-editor="1"`

## عند تعطل التعليقات
1. راجع `frontend/comment_add.php`
2. تأكد من وجود أحد الجدولين:
   - `news_comments`
   - `comments`
3. راجع `/admin/comments/index.php`

## عند الحاجة لاسترجاع خبر
- افتح:
  `/admin/revisions/index.php?news_id=ID`

## حذف ملفات التحديث
بعد كل تحديث احذف:
- `update_phase31.php`
- `update_phase32.php`
- `update_phase33.php`
- `update_phase34.php`
- ملفات log التابعة لها