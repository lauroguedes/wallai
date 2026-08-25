---
paths:
  - 'app/Services/**'
  - app/Models/AiProviderSetting.php
  - app/Models/Wallpaper.php
  - 'resources/views/components/**'
  - app/Models/User.php
---

# Components

## Resolve all user data through WorkspaceContext
In authenticated installations, AI settings, wallpaper records, cache registries, and storage paths belong to user:{id}. In session installations, preserve browser-session ownership. Never accept a client-selected workspace owner; resolve it server-side through WorkspaceContext.

## Invitations control active user access
New invitations start with is_active=false and become active when accepted. Deactivated users must be rejected at login and logged out by AuthenticateWhenEnabled. Admins may manage only non-admin accounts and may never deactivate or delete themselves.

## Restrict Ollama endpoints to configured hosts
Validate every saved and runtime Ollama URL against ai.ollama_allowed_hosts. Keep the Livewire field validation and AiProviderSettings runtime enforcement together so session-mode users cannot turn the server into an arbitrary outbound request proxy.
