# Godyar CMS v4 — Phase 4

## Added
- Contact persistence with DB table `contact_messages`
- Fallback log storage in `storage/reports/contact_messages.log`
- CSRF helper + validation
- Honeypot anti-spam field
- Simple file-based rate limiting for contact form
- Admin review pages:
  - `/v4/admin/redirects`
  - `/v4/admin/contact-messages`
- Search autosuggest JSON endpoint:
  - `/{lang}/search/suggest.json?q=...`

## Updated Files
- `bootstrap_v4.php`
- `app/V4/Bootstrap/App.php`
- `app/V4/Bootstrap/Response.php`
- `app/V4/Config/routes.php`
- `app/V4/Controllers/ContactController.php`
- `app/V4/Support/helpers.php`
- `themes/default/pages/contact.php`
- `themes/default/pages/search.php`
- `themes/default/assets/js/app.js`
- `themes/default/assets/css/core.css`

## New Files
- `app/V4/Repositories/ContactMessageRepository.php`
- `app/V4/Services/ContactService.php`
- `app/V4/Controllers/AdminToolsController.php`
- `app/V4/Controllers/SearchApiController.php`
- `themes/default/pages/admin-redirects.php`
- `themes/default/pages/admin-contact-messages.php`
- `database/migrations/2026_03_08_0003_contact_messages_admin_tools.sql`

## Notes
- صفحات الإدارة المضافة هنا صفحات مراجعة داخل طبقة v4 وليست دمجًا كاملًا مع إدارة المشروع القديمة.
- تم الإبقاء على التنفيذ آمنًا وتدريجيًا بدون استبدال مسار الدخول الحالي للموقع.
