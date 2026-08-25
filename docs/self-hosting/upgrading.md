# Upgrading

Use a numbered release in production:

```dotenv
WALLAI_VERSION=1.2.0
```

Review the release notes, set the new version, then run:

```bash
./bin/wallai update
```

The update command:

1. creates a backup;
2. pulls the selected image;
3. stops the web, Horizon, and scheduler services cleanly while Redis remains available;
4. runs migrations through the one-shot init service;
5. recreates services and waits for health checks;
6. interrupts any scheduler process still running old code.

Automatic unattended image updates are not enabled. Database migrations may make downgrading unsafe, so keep the pre-upgrade backup.

## Host-tooling updates

The container image does not replace host-side files such as `compose.yaml`, `bin/wallai`, or the proxy configuration. When the release notes identify host-tooling changes, check out the matching release before updating:

```bash
git fetch --tags
git checkout v1.2.0
```

Then set `WALLAI_VERSION=1.2.0` in `.env` and run `./bin/wallai update`. The ignored `.env`, `.secrets`, and backup files remain in place.

Project maintainers should follow the [release guide](../releasing.md) when publishing new versions.

## Roll back

If the release notes allow a direct rollback, restore the old `WALLAI_VERSION` and run `./bin/wallai up`. Otherwise restore the backup created before the upgrade:

```bash
./bin/wallai restore backups/wallai-YYYYMMDDTHHMMSSZ.tar.gz
```
