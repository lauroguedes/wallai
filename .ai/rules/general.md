---
paths:
  - 'compose*.yaml'
---

# General

## Run migrations once before long-lived services
The self-hosted stack uses one immutable image for web, Horizon, scheduler, and init. Keep migrations and deployment checks in the one-shot init service; web, Horizon, and scheduler must depend on its successful completion and Redis health.

## Preserve least-privilege secret startup
File-backed Compose secrets remain host-owned and mode 0600. Application services start the entrypoint with only DAC_READ_SEARCH, SETUID, and SETGID; Redis uses the same read-then-drop pattern. Health checks and maintenance commands must go through wallai-entrypoint, while entrypoint-overridden backup/restore helpers must explicitly use UID/GID 33.
