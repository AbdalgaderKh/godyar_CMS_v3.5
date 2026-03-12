
- Optional v4 bootstrap and front controller
- MenuRepository + MenuService
- SeoService with canonical, Open Graph, Twitter card, JSON-LD
- SitemapService with:
  - /sitemap.xml
  - /sitemap-pages.xml
  - /sitemap-news.xml
  - /sitemap-categories.xml
- Default theme layout with unified header/footer
- Safe DB-backed repositories with fallback content

- bootstrap_v4.php
- public/index.v4.php
- public/.htaccess.v4-snippet
- app/V4/*
- themes/default/*

1. Test `public/index.v4.php` directly first.
2. When stable, merge the rewrite snippet into the public entry .htaccess.
3. Keep legacy routes active until traffic and logs confirm the new routes are stable.

- Wire contact submissions into `contact_messages`
- Add RedirectRepository + admin Redirect Manager
- Add SearchController and autosuggest endpoint
- Add Menu Manager in admin
- Add MediaService for image normalization and WebP generation
