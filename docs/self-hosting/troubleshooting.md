# Troubleshooting

Start with:

```bash
./bin/wallai status
./bin/wallai doctor
./bin/wallai logs
```

## Init does not complete

Inspect its logs:

```bash
docker compose logs init
```

Common causes are an invalid database address, database permissions, a missing secret, or an unwritable volume.

## Scheduler heartbeat is stale

```bash
docker compose logs scheduler
docker compose exec scheduler php artisan schedule:list
```

The scheduler writes a Redis heartbeat every minute. Restart the scheduler after resolving Redis or configuration errors.

## Horizon is unhealthy

```bash
docker compose logs horizon
docker compose exec web php artisan horizon:status
```

Confirm Redis is healthy and `QUEUE_CONNECTION=redis`. Do not reduce `REDIS_QUEUE_RETRY_AFTER` below the Horizon worker timeout.

## Invitations do not arrive

Check the notification queue and mail logs:

```bash
docker compose logs horizon
docker compose logs web | grep -i invitation
```

With `MAIL_MAILER=log`, no email is sent; the invitation is written to logs. Configure SMTP for delivery.

## Images disappear after restart

Confirm `wallai_wallpapers` is mounted:

```bash
docker compose config
docker volume ls | grep wallai
```

Do not run `docker compose down --volumes` unless permanent deletion is intended.

## Browser shows 419 or repeated reloads

Verify `APP_URL`, proxy headers, cookie domain, and `SESSION_SECURE_COOKIE`. Changing `SESSION_COOKIE` invalidates existing browser-session workspaces.

## HTTPS certificate fails

Confirm DNS points to this server, ports 80/443 are reachable, and no other process owns them. Then inspect:

```bash
docker compose logs proxy
```

## Disk usage

Generated wallpapers are permanent until deleted by the user or factory reset. Monitor Docker volume and host disk usage. Back up important data before cleanup.
