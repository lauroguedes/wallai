---
paths:
  - bin/wallai
---

# Wallai

## Back up encrypted settings with APP_KEY
Self-hosted backups must preserve the database, generated wallpapers, environment configuration, and .secrets/app_key together. Provider API keys are encrypted with APP_KEY and are unrecoverable if that key is lost or replaced.

## Keep Docker installation self-configuring
Use `install --local` to create and persist an ignored `.env.docker`, build the current checkout, and serve `wallai.localhost` without DNS or TLS. Use `install --domain` only for public HTTPS and derive APP_URL, secure cookies, trusted hosts, and loopback-only direct binding from that domain.

## Separate local and public runtime modes
`install --local` must persist APP_ENV=local and APP_DEBUG=true. `install --domain` must explicitly restore APP_ENV=production and APP_DEBUG=false so a reused environment file cannot expose production debug output.

## Prefer Docker-specific configuration
When .env.docker exists, manager commands must prefer it over the native Laravel .env. Local diagnostics must omit production-only deployment checks; production diagnostics must continue enforcing them.
