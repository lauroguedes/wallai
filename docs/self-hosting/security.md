# Security

## Required production settings

- Use HTTPS.
- Set `APP_DEBUG=false`.
- Set `SESSION_SECURE_COOKIE=true` under HTTPS.
- Restrict `TRUSTED_HOSTS` to the public domain.
- Restrict `TRUSTED_PROXIES` to the reverse proxy when possible.
- Configure SMTP instead of sharing invitation links from logs.
- Keep `.env`, `.secrets/`, and backups readable only by the operator.
- Keep Docker and the host operating system patched.

## Container defaults

The application containers run as a non-root user with a read-only root filesystem, dropped Linux capabilities, and `no-new-privileges`. Only explicit temporary and persistent paths are writable. Redis is authenticated and is not exposed on a host port.

## Credentials

Provider API keys entered through WallAI are encrypted in the database. Encryption depends on `.secrets/app_key`; losing it loses access to those stored credentials. Rotating `APP_KEY` without first re-encrypting stored values has the same effect.

Do not paste `.env`, backups, container environment output, or diagnostic logs into public support requests.

## Horizon

The Horizon dashboard is available only to active administrators in authenticated installations. It is intentionally unavailable through the browser in session-only installations; use `./bin/wallai status` and logs instead.

## Ollama and outbound access

Only trusted administrators should configure an Ollama URL. Treat a remote Ollama endpoint like an internal service and protect it with network controls. WallAI also needs outbound HTTPS access to the configured AI and mail providers.

## Security updates

Release CI audits Composer and npm dependencies, scans images, creates an SBOM, and publishes build provenance. Operators should still review release notes and deploy security releases promptly.
