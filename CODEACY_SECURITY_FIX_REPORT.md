# Codacy Security Remediation Report

تم تنفيذ حزمة إصلاحات أمنية استباقية لمعالجة أبرز الأنماط التي تظهر عادةً ضمن تقارير Codacy Security، مع التركيز على الملفات ذات الخطورة الأعلى في المشروع.

## ما تم إصلاحه

### 1) تأمين endpoint رفع الصور
الملف: `admin/news/upload_image.php`

تم استبدال الرفع المباشر غير الآمن الذي كان يسمح برفع الملف باسمه الأصلي ودون تحقق كافٍ، إلى رفع آمن يعتمد على `SafeUploader` مع:
- التحقق من تسجيل الدخول
- فرض `POST` فقط
- التحقق من CSRF
- السماح فقط بامتدادات الصور المعروفة
- التحقق من MIME و `getimagesize`
- إنشاء أسماء عشوائية للملفات بدل اسم الملف الأصلي
- إضافة `.htaccess` لحظر تنفيذ ملفات PHP داخل مجلد الرفع

### 2) إزالة fallback ضعيف لتوليد CSP nonce
الملف: `includes/security_headers.php`

تم حذف fallback الذي كان يعتمد على `mt_rand()` واستبداله بـ:
- `random_bytes()`
- ثم `openssl_random_pseudo_bytes()` عند الحاجة
- ثم fallback مبني على `hash('sha256', ...)` بدون `mt_rand()` أو `uniqid()`

### 3) تقوية توليد CSRF token
الملف: `admin/_admin_guard.php`

تم حذف fallback الضعيف المبني على `uniqid(mt_rand())`، واستبداله بمسار أقوى يعتمد على:
- `random_bytes()`
- أو `openssl_random_pseudo_bytes()`
- أو fallback مبني على `sha256`

### 4) استبدال MD5 / SHA1 في مفاتيح وcache identifiers
الملفات:
- `includes/functions.php`
- `app/V4/Services/ResponseCacheService.php`
- `admin/settings/theme.php`
- `app/V4/Repositories/RevisionRepository.php`

تم استبدال الاستخدامات غير الضرورية لـ `md5` و `sha1` في مفاتيح cache والمعرفات غير الأمنية بخوارزمية `sha256` لتقليل التحذيرات الأمنية وتحسين الاتساق.

## تحقق بعد الإصلاح
- تم فحص الملفات المعدلة بـ `php -l`
- تم تشغيل فحص syntax على ملفات PHP بالمشروع بعد التعديلات ولم يظهر خطأ syntax

## ملاحظة مهمة
تعذر الوصول المباشر إلى صفحة Codacy التفاعلية من البيئة الحالية لأن الصفحة تتطلب JavaScript وتسجيل وصول، لذلك تم تنفيذ الإصلاحات على أساس الأنماط الأمنية الظاهرة في الكود نفسه والأكثر احتمالًا لظهورها في تقرير Codacy Security.

إذا أرسلت export من التقرير نفسه (CSV / screenshot كامل للأسماء / SARIF)، يمكن تنفيذ جولة مطابقة دقيقة بندًا ببند.
