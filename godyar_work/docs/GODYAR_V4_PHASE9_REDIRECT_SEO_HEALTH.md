
- Redirect CRUD center at `/v4/admin/redirects`
- SEO Audit dashboard at `/v4/admin/seo-audit`
- Media Trash restore at `/v4/admin/media-trash`
- System Health dashboard at `/v4/admin/system-health`
- Duplicate media detector
- Broken internal links scanner

- `app/V4/Controllers/AdminGovernanceController.php`
- `app/V4/Repositories/RedirectRepository.php`
- `app/V4/Repositories/SeoAuditRepository.php`
- `app/V4/Repositories/MediaCleanupQueueRepository.php`
- `app/V4/Repositories/MediaRepository.php`
- `app/V4/Services/SeoAuditService.php`
- `app/V4/Services/SystemHealthService.php`
- `app/V4/Services/LegacyRedirectService.php`

- Redirects work with DB when table exists, with JSON fallback in `storage/v4/redirects.json`
- SEO audit is heuristic and intentionally safe-first
- Media restore returns archived files from `storage/trash/media/*` to their original paths
- Broken link scan is lightweight and should be refined later with route-aware validation
