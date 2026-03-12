# Godyar CMS v4 — Phase 8

## Scope
This phase adds three safe-first capabilities on top of the existing v4 layer:

1. **Revision Restore (draft-only)**
2. **Media Usage Map**
3. **SEO Preview Center**

## What was added
- `POST /v4/admin/revisions/restore`
- `GET /v4/admin/media-usage`
- `GET /v4/admin/seo-preview`

## Revision Restore
- Restore works from `storage/revisions/news-{id}.json` snapshots.
- Restore is **safe-first**: it writes a draft restore file to:
  - `storage/v4/restores/news-{id}-draft.json`
- It also appends an audit line to:
  - `storage/reports/revision_restore.log`
- It does **not** overwrite live DB content directly.

## Media Usage Map
- Scans indexed media plus filesystem media.
- Matches media usage against:
  - news content
  - news featured image
  - page content
- Flags files as used / apparently unused.
- Lists references when found.

## SEO Preview Center
- Previews SEO metadata for:
  - news
  - pages
  - categories
- Shows:
  - title
  - description
  - canonical
  - Open Graph preview
  - JSON-LD
  - lightweight checks for metadata quality

## Files changed
- `app/V4/Controllers/AdminContentController.php`
- `app/V4/Config/routes.php`
- `app/V4/Repositories/RevisionRepository.php`
- `app/V4/Repositories/MediaRepository.php`
- `app/V4/Repositories/PageRepository.php`
- `app/V4/Repositories/CategoryRepository.php`
- `app/V4/Services/SeoService.php`
- `themes/default/pages/admin-revisions.php`
- `themes/default/pages/admin-media-usage.php`
- `themes/default/pages/admin-seo-preview.php`
- `themes/default/layout/master.php`
- `themes/default/assets/css/core.css`

## Notes
- No live-content overwrite was introduced in this phase.
- No destructive cleanup was introduced.
- This phase is meant for safer editorial review before enabling stronger write-back actions later.
