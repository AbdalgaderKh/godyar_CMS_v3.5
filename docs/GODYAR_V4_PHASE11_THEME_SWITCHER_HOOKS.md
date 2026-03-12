# Phase 11: Theme Switcher + Hook Bus

## Admin URLs
- `/v4/admin/themes`
- `/v4/admin/hooks`

## Runtime files
- `storage/v4/theme_state.json`
- `storage/v4/runtime_settings.json`

## Hook events
- `theme.head`
- `theme.footer`
- `theme.rendering`
- `theme.rendered`

## Notes
- Theme switching currently changes the active visual CSS layer safely.
- Layout/view rendering remains on the unified default v4 layout to avoid breaking templates.
- Plugin boot scripts can now register listeners through the global hook bus.
