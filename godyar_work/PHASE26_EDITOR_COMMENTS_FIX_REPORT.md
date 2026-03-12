# Phase 26 — Editor Visible Fix + Comments & News View Repair

## What was fixed
- Ensured the editor JS is loaded explicitly in create/edit pages.
- Added resilient editor initialization on DOMContentLoaded, load, delayed retries, and MutationObserver.
- Fixed broken form field markup in news create/edit pages.
- Fixed the content label to target the actual editor field.
- Removed conflicting inline WYSIWYG styles that forced white text inside the editor.
- Rebuilt `frontend/views/news_single_legacy.php` to stop HTML leakage into the comments block.
- Improved article body rendering for Word/Office content:
  - preserves headings, paragraphs, lists, images, blockquotes, and tables
  - wraps plain-text content into readable paragraphs/lists when needed
- Improved comments card markup and styling.

## Key files changed
- `admin/news/create.php`
- `admin/news/edit.php`
- `assets/admin/editor/gdy-editor.js`
- `admin/assets/editor/gdy-editor.js`
- `frontend/views/news_single_legacy.php`

## Validation
- `php -l admin/news/create.php`
- `php -l admin/news/edit.php`
- `php -l frontend/views/news_single_legacy.php`
- `node --check assets/admin/editor/gdy-editor.js`
- `node --check admin/assets/editor/gdy-editor.js`

All passed.
