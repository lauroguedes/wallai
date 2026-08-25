# Releasing WallAI

This guide is for project maintainers publishing a Git release and the matching multi-architecture container image.

## Release model

WallAI follows Semantic Versioning:

- Patch releases, such as `2.0.1`, contain backward-compatible fixes.
- Minor releases, such as `2.1.0`, contain backward-compatible features.
- Major releases, such as `3.0.0`, may contain breaking changes.

Pushing a tag matching `v*.*.*` starts the [release container workflow](../.github/workflows/release.yml). For a stable `v2.1.0` release, the workflow publishes:

```text
ghcr.io/lauroguedes/wallai:2.1.0
ghcr.io/lauroguedes/wallai:2.1
ghcr.io/lauroguedes/wallai:latest
ghcr.io/lauroguedes/wallai:sha-<commit>
```

The Git tag includes the `v`, but `WALLAI_VERSION` uses the container tag without it. Prerelease tags such as `v2.2.0-rc.1` do not update `latest`.

## One-time registry setup

The release workflow publishes to GitHub Container Registry using its repository-scoped `GITHUB_TOKEN`. Ensure that:

1. GitHub Actions is enabled for the repository.
2. The workflow has permission to write packages.
3. The `ghcr.io/lauroguedes/wallai` package is public so anonymous Docker installations can pull it.

Private packages require every operator to run `docker login ghcr.io` before installing or updating WallAI.

## 1. Prepare the release

Start from an up-to-date branch and a clean working tree. Move the relevant entries in [CHANGELOG.md](../CHANGELOG.md) from `Unreleased` into a dated version section:

```markdown
## 2.1.0 - 2026-08-25
```

Release notes must call out:

- new features and important fixes;
- breaking configuration or behavior changes;
- required environment variables;
- database migration or rollback limitations;
- whether operators must update host-side files.

Host-side files are not contained in an image update. Check whether the release changes files such as `compose.yaml`, `bin/wallai`, `docker/Caddyfile.proxy`, or `.env.docker.example`:

```bash
git diff --name-only v2.0.0..HEAD -- compose.yaml bin/wallai docker/Caddyfile.proxy .env.docker.example
```

If this command produces output, include the host-tooling upgrade instructions from this guide in the release notes.

## 2. Run release checks

Run the same essential checks before tagging:

```bash
composer install --no-interaction --prefer-dist
npm ci
vendor/bin/pint --format agent
php artisan test --compact
npm run build
composer audit
npm audit --audit-level=high
```

For changes affecting the image or deployment lifecycle, also test a local production image:

```bash
cp .env.docker.example .env.docker
WALLAI_ENV_FILE=.env.docker WALLAI_BUILD_LOCAL=true ./bin/wallai install
WALLAI_ENV_FILE=.env.docker WALLAI_BUILD_LOCAL=true ./bin/wallai doctor
WALLAI_ENV_FILE=.env.docker WALLAI_BUILD_LOCAL=true ./bin/wallai down
```

Do not use production secrets for this local verification.

## 3. Commit and merge

Commit the final changelog and release changes, then merge them into `main`:

```bash
git add CHANGELOG.md
git commit -m "chore: prepare release 2.1.0"
git push origin main
```

Wait for the main CI workflow to pass. The release workflow must exist in the commit that will be tagged.

## 4. Create the release tag

Create an annotated tag from the verified commit and push it:

```bash
git switch main
git pull --ff-only
git tag -a v2.1.0 -m "WallAI v2.1.0"
git push origin v2.1.0
```

Do not move or reuse a published version tag. If a published release needs a correction, create a new patch version.

## 5. Monitor and verify publication

The release workflow:

1. installs dependencies and runs the test suite;
2. builds the frontend assets;
3. builds Linux `amd64` and `arm64` images;
4. publishes the version, minor, commit, and applicable `latest` tags;
5. publishes an SBOM and build provenance attestation.

Wait for the `Release container` workflow to complete, then inspect and pull the immutable version tag:

```bash
docker buildx imagetools inspect ghcr.io/lauroguedes/wallai:2.1.0
docker pull ghcr.io/lauroguedes/wallai:2.1.0
```

Confirm that both supported platforms appear in the image manifest.

## 6. Test the published upgrade

Use a staging installation with a recent backup. Set the immutable version in its `.env` file:

```dotenv
WALLAI_VERSION=2.1.0
```

Then exercise the same path operators will use:

```bash
./bin/wallai update
./bin/wallai doctor
./bin/wallai status
```

Verify first-run access, authentication, image generation, queues, persisted wallpapers, and any release-specific behavior.

## 7. Publish the GitHub release

Create a GitHub Release for `v2.1.0` and publish the reviewed release notes. This is separate from publishing the container image. It can be done through GitHub or the GitHub CLI:

```bash
gh release create v2.1.0 --title "WallAI 2.1.0" --generate-notes
```

Prefer curated notes when the release requires operator action or has rollback limitations.

## Operator upgrade instructions

### Standard pinned-version upgrade

Operators using the recommended immutable version update `.env`:

```dotenv
WALLAI_VERSION=2.1.0
```

They then run:

```bash
./bin/wallai update
./bin/wallai doctor
```

The update command creates a backup, pulls the selected image, stops the application services, runs migrations, recreates the services, and waits for health checks.

### Release with host-tooling changes

The image cannot update `compose.yaml`, `bin/wallai`, or the host-mounted proxy configuration. When those files change, operators must first check out the matching Git release:

```bash
git fetch --tags
git checkout v2.1.0
```

Their ignored `.env`, `.secrets`, and backup files remain in place. After setting `WALLAI_VERSION=2.1.0`, they run:

```bash
./bin/wallai update
./bin/wallai doctor
```

Release notes must explicitly identify this requirement.

### Installations following `latest`

For installations configured with `WALLAI_VERSION=latest`, a stable release is received by running:

```bash
./bin/wallai update
./bin/wallai doctor
```

Pinned versions remain recommended because they make deployments reproducible and rollback decisions explicit.

## Rollback

Every update creates a backup before replacing the running image. If the release notes confirm that its migrations are backward compatible, an operator may restore the previous `WALLAI_VERSION` and run `./bin/wallai up`.

Otherwise, restore the pre-upgrade backup:

```bash
./bin/wallai restore backups/wallai-YYYYMMDDTHHMMSSZ.tar.gz
```

Always test the rollback procedure for releases containing destructive or irreversible migrations.

## Release checklist

- [ ] Changelog and curated release notes are complete.
- [ ] Host-tooling changes are identified.
- [ ] Tests, formatting, builds, and dependency audits pass.
- [ ] Main CI passes on the release commit.
- [ ] The annotated `vX.Y.Z` tag points to the intended commit.
- [ ] The release workflow publishes both supported architectures.
- [ ] The immutable image tag can be pulled anonymously.
- [ ] A staging installation upgrades successfully.
- [ ] The GitHub Release is published with upgrade and rollback instructions.
