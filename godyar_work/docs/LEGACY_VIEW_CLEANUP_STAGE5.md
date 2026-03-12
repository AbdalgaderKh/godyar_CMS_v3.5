# Stage 5 Legacy View Cleanup

## What changed
- Unified category rendering on a single canonical view: `frontend/views/category.php`.
- Converted `frontend/views/category_modern.php` into a compatibility alias that forwards to the canonical category view.
- Converted the unused duplicate homepage file `frontend/views/home_modern.php` into a compatibility alias that forwards to `frontend/home.php`.
- Neutralized old patch example/note files so they no longer carry executable duplicate logic:
  - `frontend/views/category_fix_example.php`
  - `frontend/views/category_fix_notes.php`
  - `frontend/views/news_report_fix_example.php`
  - `frontend/views/news_report_fix_notes.php`
- Removed the malformed duplicate partial filename under `frontend/views/partials/` that wrapped the header with an invalid encoded suffix.

## Why this is safer
- Keeps legacy file paths present where hidden includes may still reference them.
- Prevents rendering drift by ensuring category pages resolve through one real view only.
- Prevents old example files from being executed as pseudo-production code.

## Recommended next pass
- Review whether `frontend/views/news_single_legacy.php` should be renamed to a canonical article view now that it is actively used by the controller.
- Audit CSS bundle loading order to retire duplicate files such as `app.css`, `front.css`, and `style.css` after deployment verification.
