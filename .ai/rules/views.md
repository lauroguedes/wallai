---
paths:
  - 'resources/js/**, resources/views/**'
---

# Views

## Recover expired Livewire sessions without native dialogs
Keep the global Livewire request interceptor that prevents the built-in browser confirm on HTTP 419 and reloads automatically. Confirmation modals that only reveal UI should open client-side so stale sessions do not issue a request before the user confirms.
