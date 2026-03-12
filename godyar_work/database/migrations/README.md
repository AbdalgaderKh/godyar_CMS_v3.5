
هذا مجلد مخصص لملفات الـ **migrations**.

> ملاحظة: في الإصدار v1.5.x هذه الملفات اختيارية وغير مطبقة تلقائيًا.

- اسم الملف: `YYYYMMDD_HHMMSS_description.php`
- يُرجع الملف مصفوفة تحتوي على:
  - `id` (فريد)
  - `up` (SQL أو callable)

يمكن تشغيلها عبر:

```bash
php tools/migrate.php
```
