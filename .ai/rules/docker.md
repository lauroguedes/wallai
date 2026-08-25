---
paths:
  - 'docker/**'
  - 'docker/*.sh'
---

# Docker

## Keep the production image immutable and non-root
Production containers run the same multi-stage PHP 8.5 image as www-data with a read-only root filesystem. Only explicit tmpfs and the SQLite/wallpaper volumes are writable; never bake .env files or runtime secrets into the image.

## Recreate Laravel tmpfs directories
The read-only Compose stack mounts framework, logs, and bootstrap cache paths as empty tmpfs filesystems. The application entrypoint must recreate Laravel's cache, session, view, and log directories after mounts are attached.
