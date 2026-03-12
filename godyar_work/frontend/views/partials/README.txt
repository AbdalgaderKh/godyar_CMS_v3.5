Godyar Head Cleanup Patch v1
================================

Fixes:
- Removes duplicate theme CSS includes.
- Ensures only ONE theme-*.css is loaded (besides theme-core).
- Removes inline <style> blocks overriding --primary variables.
- Cleans head structure for predictable theme behavior.

After upload:
1) Replace your existing files with these.
2) Hard refresh (Ctrl+F5).
3) Purge CDN cache if enabled.
