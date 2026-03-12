
This pack adds curated newsroom-ready translation keys for Arabic, English, and French.

Files added:
- languages/ar_patch.php
- languages/en_patch.php
- languages/fr_patch.php

Why patch files?
The CMS already merges `{lang}_patch.php` automatically after loading the base language file.
This keeps your original files intact and makes future updates safer.

Suggested keys included:
- Brand and tagline
- Header/navigation labels
- Breaking news UI
- Homepage newsroom blocks
- Section labels
- Article metadata labels
- Search/newsletter/footer CTAs
- Status badges

Examples:
- __('brand.name')
- __('brand.tagline')
- __('breaking.label')
- __('home.editors_pick')
- __('search.placeholder_newsroom')
- __('footer.editorial_policy')

Recommended integration points:
1. Header logo area -> brand.name / brand.tagline
2. Breaking ticker -> breaking.label
3. Homepage blocks -> home.* keys
4. Article sidebar/meta -> article.* keys
5. Footer -> footer.* keys

Compatibility:
- PHP 5.6+
- No database migration required
