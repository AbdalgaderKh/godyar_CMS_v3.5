# Phase 5 Explained

This phase turns v4 into a richer editorial layer.

## What works now
### Authors
You can open an author archive through:
- `/ar/author/{slug}`

### Tags
You can open a tag archive through:
- `/ar/tag/{slug}`

### News page improvements
News pages now support:
- author link
- tag pills
- revision timeline block
- media gallery block
- WebP candidate detection

## Database compatibility
The code tries multiple likely table names before falling back.
That keeps the project safer while migrating from the current schema.

## Safe fallback behavior
If the project does not yet have all the required tables:
- the page still renders
- article image still works
- author/tag pages still render with fallback values
- revisions can be read from JSON snapshots later

## Suggested admin follow-up
The next logical build is:
- tag manager
- author manager
- revision browser
- media manager
