---
paths:
  - 'compose*.yaml'
---

# General

## Run migrations once before long-lived services
The self-hosted stack uses one immutable image for web, Horizon, scheduler, and init. Keep migrations and deployment checks in the one-shot init service; web, Horizon, and scheduler must depend on its successful completion and Redis health.

## Preserve least-privilege service startup
FrankenPHP must have its unnecessary privileged-port file capability removed before app services drop all capabilities. Redis must read its secret as root, then use setpriv to become redis with only SETUID/SETGID; scheduler overrides FrankenPHP's inherited HTTP healthcheck with wallai:doctor --runtime.
