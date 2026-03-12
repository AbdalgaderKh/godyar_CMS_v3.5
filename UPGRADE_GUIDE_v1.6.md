
This release introduces a **/public** web root (DocumentRoot) for improved security and a cleaner deployment layout.

- New directory: `public/`
  - `public/index.php` forwards to the existing root `index.php`.
  - `public/.htaccess` handles routing.
- `public/` contains **symlinks** to your existing web directories:
  - `public/assets -> ../assets`
  - `public/admin  -> ../admin`
  - `public/frontend -> ../frontend`
  - `public/install -> ../install`

This keeps the current runtime intact while allowing you to set the server DocumentRoot to `/public`.

Set your DocumentRoot to:

- `/path/to/project/public`

Make sure `AllowOverride All` is enabled so `.htaccess` works.

Point root to `/public` and route requests to `index.php`.

Replace symlinks by moving directories into `public/`:

- Move `assets/` to `public/assets/`
- Move `admin/` to `public/admin/`
- Move `frontend/` to `public/frontend/`
- Move `install/` to `public/install/`

Then update any hard-coded filesystem paths if your custom code depends on them.

- Installer remains available at `/install`.
- After installation, delete `install.php` and/or keep `install.lock`.
