
Godyar CMS v4.0.4.1 Admin Sidebar Force Expand Patch

Purpose:
- Force the admin sidebar to remain expanded on desktop
- Restore hidden text labels next to icons
- Remove any collapsed/minimized state from body classes and localStorage

Files:
- admin/assets/css/admin_sidebar_force_expand.css
- admin/assets/js/admin_sidebar_force_expand.js
- admin/layout/include_patch_example.php

Install:
1. Upload the files.
2. Add in the REAL admin layout file used by /admin/index.php:
   - In <head>:
     <link rel="stylesheet" href="/admin/assets/css/admin_sidebar_force_expand.css">
   - Before </body>:
     <script src="/admin/assets/js/admin_sidebar_force_expand.js"></script>

If it still does not work:
- There is likely a different admin layout file than expected, or another JS plugin re-collapses the sidebar after load.
- In that case, inspect which layout file renders the sidebar wrapper and include these files there.
