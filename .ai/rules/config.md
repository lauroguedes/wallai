---
paths:
  - config/horizon.php
  - config/session.php
---

# Config

## Run invitation mail on a dedicated Horizon queue
UserInvitation routes mail to the notifications queue. Keep supervisor-notifications separate from the auto-balanced wallpaper supervisors so long image jobs cannot block invitations. Maintain job timeout < supervisor timeout < Redis retry_after.

## Keep a stable WallAI session cookie
Use the explicit `wallai_session` cookie name rather than deriving it from APP_NAME. This isolates self-hosted WallAI sessions from stale Laravel defaults and prevents session-cookie changes during framework upgrades.
