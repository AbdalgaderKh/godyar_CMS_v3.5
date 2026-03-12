
This package adds three practical admin-side capabilities on top of the v4 layer available in this environment:

1. **Live Restore + Draft Restore** for news snapshots stored in `storage/revisions/news-{id}.json`
2. **SEO Overrides + Preview Center** stored in `storage/v4/seo_overrides.json`
3. **Safe Media Cleanup Queue** that moves files to `storage/trash/media/...` instead of deleting them directly

- `GET /v4/admin/revisions`
- `POST /v4/admin/revisions/restore`
- `POST /v4/admin/revisions/live-restore`
- `GET /v4/admin/seo-preview`
- `POST /v4/admin/seo-preview/save`
- `GET /v4/admin/media-cleanup`
- `POST /v4/admin/media-cleanup/queue`
- `POST /v4/admin/media-cleanup/unqueue`
- `POST /v4/admin/media-cleanup/archive`

- Draft restore writes only to `storage/v4/restores/`
- Live restore writes to the DB only when a compatible table is reachable and creates a JSON backup first in `storage/v4/backups/`
- Media cleanup archives files into `storage/trash/media/` instead of deleting them permanently

- `app/V4/Controllers/AdminAdvancedController.php`
- `app/V4/Repositories/RevisionRepository.php`
- `app/V4/Repositories/SeoOverrideRepository.php`
- `app/V4/Repositories/MediaRepository.php`
- `app/V4/Repositories/MediaCleanupQueueRepository.php`
- `app/V4/Services/SeoService.php`
- `app/V4/Config/routes.php`
- `themes/default/pages/admin-revisions.php`
- `themes/default/pages/admin-seo-preview.php`
- `themes/default/pages/admin-media-cleanup.php`
- `themes/default/assets/css/core.css`
- `app/V4/Support/helpers.php`
- `app/V4/Controllers/Controller.php`

- This package was built on the v4 layer available in the current workspace.
- It does not replace your current site entrypoint.
- It keeps a safe-first approach for destructive operations.
