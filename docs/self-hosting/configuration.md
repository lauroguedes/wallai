# Configuration reference

WallAI reads deployment configuration from `.env`, or from `.env.docker` when installed with `./bin/wallai install --local`. The manager detects either file automatically. Restart services after changing it:

```bash
./bin/wallai down
./bin/wallai up
```

Keep the active environment file and `.secrets/` private and include both in backups.

## Application

| Variable | Default | Purpose |
| --- | --- | --- |
| `APP_URL` | `http://localhost:8080` | Canonical public URL used in generated links. |
| `APP_TIMEZONE` | `UTC` | PHP and Laravel timezone. |
| `WALLAI_IMAGE` | `ghcr.io/lauroguedes/wallai` | Container registry image. |
| `WALLAI_VERSION` | `latest` | Image version; use a numbered release in production. |
| `WALLAI_BUILD_LOCAL` | `false` | Build the current checkout through `compose.build.yaml` instead of pulling an image. |
| `WALLAI_PROJECT_NAME` | `wallai` | Compose project and volume prefix; change it to run multiple installations. |
| `WALLAI_BIND_ADDRESS` | `127.0.0.1` | Host interface for the direct HTTP port. |
| `WALLAI_PORT` | `8080` | Direct HTTP port. |
| `WALLAI_DOMAIN` | empty | Enables bundled automatic HTTPS when set. |
| `TRUSTED_PROXIES` | `*` | Comma-separated proxy addresses or CIDRs. |
| `TRUSTED_HOSTS` | empty | Comma-separated allowed hostnames; `*` wildcards are supported. |

Use a fixed release in production, for example `WALLAI_VERSION=1.2.0`.

## Persistence

SQLite is the default and needs no database service:

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=/var/lib/wallai/database.sqlite
DB_BUSY_TIMEOUT=5000
DB_JOURNAL_MODE=WAL
DB_SYNCHRONOUS=NORMAL
```

Write-ahead logging and a five-second busy timeout reduce lock contention between the web and Horizon containers.

For an external PostgreSQL, MySQL, or MariaDB server:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=database.example.internal
DB_PORT=5432
DB_DATABASE=wallai
DB_USERNAME=wallai
DB_PASSWORD=replace-me
```

The runtime image contains the necessary PDO drivers. Create the database and restricted database user before starting WallAI.

## Redis and queues

The bundled Redis service is private and authenticated. Its password is generated in `.secrets/redis_password`.

| Variable | Default |
| --- | --- |
| `WALLPAPER_QUEUE_PROCESSES` | `3` |
| `NOTIFICATION_QUEUE_PROCESSES` | `1` |
| `REDIS_QUEUE_RETRY_AFTER` | `240` seconds |

Reduce wallpaper processes on smaller servers. Each concurrent image job may consume substantial memory and outbound API capacity.

## Mail

`MAIL_MAILER=log` writes invitation URLs to container logs and is suitable only for testing. Configure SMTP for real users:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=wallai@example.com
MAIL_PASSWORD=replace-me
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=wallai@example.com
MAIL_FROM_NAME=WallAI
```

## AI providers

Provider keys should normally be entered in the WallAI settings interface, where they are encrypted in the database. Environment values are optional fallbacks:

```dotenv
GEMINI_API_KEY=
OPENAI_API_KEY=
OLLAMA_BASE_URL=http://host.docker.internal:11434
OLLAMA_ALLOWED_HOSTS=localhost,127.0.0.1,::1,host.docker.internal,ollama
```

Add an exact hostname to `OLLAMA_ALLOWED_HOSTS` before users can save or contact that endpoint. This prevents a public session-mode installation from using arbitrary internal URLs.

Never commit provider keys. Preserve `APP_KEY` because it encrypts keys stored in the database.

## Sessions and cookies

Redis-backed encrypted sessions are the Docker default. Set `SESSION_SECURE_COOKIE=true` whenever the public URL uses HTTPS. Keep `SESSION_COOKIE=wallai_session` unchanged during upgrades so existing browser workspaces remain accessible.
