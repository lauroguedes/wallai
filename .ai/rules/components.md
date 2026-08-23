---
paths:
  - 'app/Services/**'
  - 'app/Models/AiProviderSetting.php'
  - 'app/Models/Wallpaper.php'
  - 'resources/views/components/**'
---

# Components

## Resolve all user data through WorkspaceContext
In authenticated installations, AI settings, wallpaper records, cache registries, and storage paths belong to user:{id}. In session installations, preserve browser-session ownership. Never accept a client-selected workspace owner; resolve it server-side through WorkspaceContext.
