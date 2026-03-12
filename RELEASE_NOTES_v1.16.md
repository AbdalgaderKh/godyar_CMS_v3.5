
- Removed secrets from distribution package (.env removed; .env.example added).
- Added Apache rules to deny access to .env files.
- Cleaned logs and push send logs from package.
- Ensured storage/logs is denied via .htaccess.

- Improved site logo upload: better MIME handling (SVG fallback), and save as relative URL.

- Header search: compact pill style + fixed height.
- Hide duplicated home hero search panel to prevent oversized overlay.
