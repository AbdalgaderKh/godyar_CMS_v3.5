
شكراً لرغبتك في المساهمة ❤️

- اقرأ: `README.md` و `INSTALL.md`
- راجع Issues المفتوحة قبل إنشاء Issue جديدة.

1. صف المشكلة بوضوح.
2. أرفق خطوات إعادة الإنتاج.
3. أرفق لقطات شاشة أو Logs إن أمكن (مع إزالة أي بيانات حساسة).

- اشرح لماذا الميزة مهمة.
- اقترح UX أو سلوك متوقع.

- PHP 8.1+
- MySQL/MariaDB
- Composer

1. انسخ ملف البيئة:
   - انسخ `.env.example` إلى `.env` وعدّل القيم.
2. ثبّت الاعتمادات:
   - `composer install`
3. شغّل التثبيت من المتصفح:
   - `http://localhost/install.php`

- استخدم `strict_types` في الملفات الجديدة.
- استخدم `h()` للهروب في القوالب (HTML escaping).
- لا تضف أسرار أو بيانات حساسة في المستودع.

- Lint: `composer run lint`
- تنسيق: `composer run cs`
- Static analysis: `composer run stan` و `composer run psalm`
- اختبارات: `composer run test`

- اجعل PR صغيرًا قدر الإمكان.
- اشرح التغيير ولماذا.
- أضف اختبارات إن كان ذلك مناسبًا.
