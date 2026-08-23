---
paths:
  - 'app/Http/Middleware/**'
  - 'resources/views/pages/**'
  - 'routes/web.php'
---

# Pages

## Installation mode is selected once
On first run, persist exactly one ApplicationSetting (id 1) with authenticated or session mode. Do not expose a UI switch after installation. Authenticated mode uses closed registration: the first user is admin and later users join only through admin invitations.
