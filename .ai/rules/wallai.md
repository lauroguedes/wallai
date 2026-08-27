---
paths:
  - bin/wallai
---

# Wallai

## Back up encrypted settings with APP_KEY
Self-hosted backups must preserve the database, generated wallpapers, environment configuration, and .secrets/app_key together. Provider API keys are encrypted with APP_KEY and are unrecoverable if that key is lost or replaced.

## Keep Docker installation self-configuring
Use `install --local` to create and persist an ignored `.env.docker`, build the current checkout, and serve `wallai.localhost` without DNS or TLS. Use `install --domain` only for public HTTPS and derive APP_URL, secure cookies, trusted hosts, and loopback-only direct binding from that domain.
