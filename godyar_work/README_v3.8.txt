Godyar CMS v3.8 Smart Translation Engine

This pack adds a production-friendly scaffold for a smart translation workflow:
- translation jobs queue table
- translation helper/service
- admin page to enqueue content for translation
- secure worker endpoint with TRANSLATION_CRON_KEY
- patch language keys for the new UI
- SQL migration for translation engine tables

What this pack DOES:
- gives you a safe structure to manage translation jobs
- stores suggested translations in the database
- supports manual approval workflow
- works even if no AI provider is configured (manual mode)

What this pack DOES NOT do by itself:
- it does not call external APIs automatically until you wire a provider key
- it does not overwrite existing translations without approval

Recommended next step:
1) Upload files.
2) Run the migration from /admin/upgrade.php
3) Add TRANSLATION_CRON_KEY to .env
4) Open /admin/translations/engine.php
