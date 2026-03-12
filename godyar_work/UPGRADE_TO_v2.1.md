
1) خذ نسخة احتياطية من قاعدة البيانات.
2) خذ نسخة احتياطية من ملفات الموقع.

```bash
php tools/migrate.php
```

افتح:
- /admin/upgrade.php
ثم اضغط "تشغيل الترقية الآن".

- سكربت الترقية يدعم ملفات:
  - database/migrations/*.sql
  - database/migrations/*.php
- يتم تسجيل الملفات التي تم تشغيلها في جدول `migrations`.
