---
paths:
  - 'docker/**'
  - 'docker/*.sh'
  - docker/entrypoint.sh
---

# Docker

## Keep the production image immutable and application processes non-root
Production services use the same multi-stage PHP 8.5 image with a read-only root filesystem. After the secret-loading entrypoint drops privileges, application processes run as www-data; only explicit tmpfs and the SQLite/wallpaper volumes are writable. Never bake .env files or runtime secrets into the image.

## Recreate Laravel tmpfs directories
The read-only Compose stack mounts framework, logs, and bootstrap cache paths as empty tmpfs filesystems. The application entrypoint must recreate Laravel's cache, session, view, and log directories after mounts are attached.

## Drop privileges before application startup
Load file-backed secrets before any application code, then immediately re-exec the entrypoint through setpriv as www-data. Laravel, Horizon, the scheduler, and FrankenPHP must never run as the temporary startup user.

## Bootstrap Laravel only for container startup
Run package discovery and optimization only when the entrypoint is PID 1. Health checks and maintenance commands invoke the same entrypoint to load secrets and drop privileges, but must not re-bootstrap Laravel or pollute command output.
