# Self-hosting architecture

The same immutable WallAI image runs four roles:

- `init` runs database migrations and deployment checks once.
- `web` serves Laravel through FrankenPHP on port 8080.
- `horizon` processes wallpaper and notification queues.
- `scheduler` runs Laravel scheduled tasks.

Redis provides queues, sessions, cache, Horizon metadata, scheduler locks, and a scheduler heartbeat. The optional `proxy` service provides automatic HTTPS through Caddy.

## Persistent data

| Volume | Contents | Required in backups |
| --- | --- | --- |
| `wallai_data` | SQLite database | Yes |
| `wallai_wallpapers` | Generated wallpaper files | Yes |
| `wallai_redis` | Queued jobs, sessions, cache, Horizon state | Usually no; drain workers before maintenance |
| `wallai_caddy_data` | TLS certificates | Optional; certificates can be issued again |

The host `.secrets/app_key` file is also required. The database contains encrypted provider API keys that cannot be decrypted without the original application key.

## Networks

Redis has no published host port. Application services communicate over the private `wallai` bridge network. The direct web port binds to `127.0.0.1` by default. The HTTPS proxy is the only public service when enabled.

## Startup ordering

Redis must pass its authenticated health check before `init` runs. `web`, `horizon`, and `scheduler` start only after migrations and deployment diagnostics succeed. This prevents traffic or jobs from reaching an outdated schema.

## Queue separation

Wallpaper generation uses dedicated mobile and desktop queues. Invitations use a separate notification supervisor so long-running image requests cannot block account emails.
