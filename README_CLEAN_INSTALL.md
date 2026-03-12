
This package was cleaned for fresh installation:
- Removed live data from the provided database dump
- Removed views, triggers, and definer clauses from install/schema.sql
- Removed sensitive keys and secrets from installer schema
- Replaced installer with a clean step-based installer
- Removed uploaded content from uploads/news, keeping storage structure only

1. Upload files to your hosting root.
2. Create an empty database and assign the database user in hosting panel.
3. Open /install.php
4. Complete installer steps.
5. Delete install.php and keep install.lock after installation.

- If you reinstall on a non-empty database, enable overwrite in the installer.
- The installer writes a fresh .env file during setup.

## Privacy note
This distribution ships with a sanitized schema only. No live content, admin accounts, contact details, secrets, or production dump data are included.

