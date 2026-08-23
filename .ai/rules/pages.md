---
paths:
  - 'app/Http/Middleware/**'
  - 'resources/views/pages/**'
  - 'routes/web.php'
---

# Pages

## Installation mode is selected once
On first run, persist exactly one ApplicationSetting (id 1) with authenticated or session mode. Do not expose a UI switch after installation. Authenticated mode uses closed registration: the first user is admin and later users join only through admin invitations.

## Do not rotate sessions while rendering setup
Never regenerate the session ID or CSRF token from a page component mount/render. Multiple setup tabs share the browser cookie; rotating it during GET makes one tab invalidate another and causes Livewire 419 reload loops. Regenerate only after authentication or the committed installation action.
