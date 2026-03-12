# Godyar CMS v4 — Phase 6
## Admin Authors/Tags Manager + Revision Compare + Media Manager

### ما تم تنفيذه
- إضافة صفحات مراجعة إدارية انتقالية:
  - `/v4/admin/authors`
  - `/v4/admin/tags`
  - `/v4/admin/revisions`
  - `/v4/admin/media`

### Authors Manager
- قراءة الكتاب من أكثر من schema محتمل:
  - `authors`
  - `opinion_authors`
  - `users`
- عرض الاسم، الـ slug، النبذة، الصورة، ورابط الصفحة العامة.

### Tags Manager
- قراءة الوسوم من:
  - `tags`
  - `topic_tags`
- عرضها كبطاقات سريعة مع رابط الصفحة العامة.

### Revision Compare
- توسيع `RevisionRepository` لدعم:
  - عرض آخر المراجعات
  - مقارنة نسختين بواسطة معرفي المراجعة
  - fallback إلى آخر نسختين إذا لم يتم تحديد `left/right`
- المقارنة النصية تصبح أفضل عندما يحتوي ملف
  `storage/revisions/news-{id}.json`
  على حقول مثل:
  - `title`
  - `excerpt`
  - `summary`
  - `body`
  - `content`

### Media Manager
- توسيع `MediaRepository` لدعم:
  - قراءة أحدث الوسائط من الجدول إذا كان موجودًا
  - fallback إلى فحص الملفات داخل:
    - `uploads`
    - `assets/uploads`
    - `public/uploads`
- كشف أولي لملفات WebP المكافئة وعرض الحجم التقريبي.

### ملاحظة مهمة
هذه المرحلة **ليست CRUD كاملة** بعد، بل طبقة مراجعة وإدارة انتقالية آمنة حتى لا نكسر المشروع الحالي.

### الخطوة التالية المقترحة
- CRUD فعلي للكتاب والوسوم
- رفع وسائط من الإدارة
- توليد WebP عند الرفع
- Restore revision
- Admin auth guard لصفحات `/v4/admin/*`
