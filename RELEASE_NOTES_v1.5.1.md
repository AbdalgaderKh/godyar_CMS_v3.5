
- Installer improvements for shared hosting:
  - Safer handling for existing databases (Existing DB / Overwrite).
  - Better compatibility for repeated install attempts (drops helper procedures/triggers when needed).
- Footer social links: now reads from the `settings` table via `settings_get()` as a reliable fallback.
- Open Source readiness:
  - Added standard repository files (LICENSE, SECURITY, CONTRIBUTING, CODE_OF_CONDUCT, templates).
  - Added GitHub Actions CI for basic PHP syntax checks.

- Do **not** commit `.env`.
- Keep `install.lock` and remove `install.php` + `install/` after install.

- Website: example.com
- Email: [redacted@example.com]
- Phone/WhatsApp: [redacted] / [redacted]

