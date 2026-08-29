# Development

## Native development

Requirements: PHP 8.4+, Composer, Node.js 26+, and Redis.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
composer run dev
```

Run checks:

```bash
php artisan test --compact
vendor/bin/pint --format agent
npm run build
composer audit
npm audit
```

## Build the production stack locally

Keep Docker settings separate from the native `.env`:

```bash
./bin/wallai install --local
```

The build override compiles the current checkout instead of pulling GHCR.

## Services

- `web`: FrankenPHP application server
- `horizon`: queue supervisors
- `scheduler`: scheduled commands
- `init`: one-shot migrations and deployment diagnostics
- `redis`: queue, session, cache, and scheduler coordination
- `proxy`: optional automatic HTTPS when `WALLAI_DOMAIN` is configured

Do not commit `.env`, `.env.docker`, `.secrets/`, backups, generated assets, or Docker volume data.

## Releases

See the [maintainer release guide](releasing.md) for versioning, release checks, Git tags, GHCR publication, and operator upgrade instructions.
