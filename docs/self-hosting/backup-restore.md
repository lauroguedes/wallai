# Backup and restore

## Create a backup

```bash
./bin/wallai backup
```

Backups and matching SHA-256 checksum files are written to `backups/` by default with restrictive permissions. A backup contains:

- the database volume;
- generated wallpapers;
- the active environment file;
- `APP_KEY` and the Redis password.

The command temporarily enables maintenance mode and stops Horizon and the scheduler to produce a consistent archive.

Specify another destination for off-server storage:

```bash
./bin/wallai backup /secure/location/wallai.tar.gz
```

Keep at least one encrypted off-server copy. Test restores periodically.

Operational commands refuse to generate a replacement when `APP_KEY` is missing. Restore the original `.secrets/app_key`; a newly generated key cannot decrypt provider credentials already stored in the database.

## Restore

Restoration replaces all current application data and credentials:

```bash
./bin/wallai restore /secure/location/wallai.tar.gz
```

Use `--force` only in automation after validating the backup path:

```bash
./bin/wallai restore /secure/location/wallai.tar.gz --force
```

The command validates the archive structure and checksum, stops services when an installation exists, restores data and secrets, reruns migrations, and waits for health checks. It can also restore onto a fresh checkout that does not yet contain `.env` or `.secrets/`.

## External databases

The built-in archive captures the bundled SQLite volume. If `DB_CONNECTION` points to PostgreSQL, MySQL, or MariaDB, use the database vendor's consistent backup tool in addition to `./bin/wallai backup`. Restore that database before starting the updated WallAI services.
