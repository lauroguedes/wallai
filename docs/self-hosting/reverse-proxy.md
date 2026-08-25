# HTTPS and reverse proxies

## Bundled automatic HTTPS

Set a domain in `.env`:

```dotenv
APP_URL=https://wallai.example.com
WALLAI_DOMAIN=wallai.example.com
SESSION_SECURE_COOKIE=true
TRUSTED_HOSTS=wallai.example.com
```

Point DNS to the server and allow TCP 80/443. `./bin/wallai up` automatically enables the Caddy HTTPS profile and obtains a Let's Encrypt certificate.

## Existing reverse proxy

Leave `WALLAI_DOMAIN` empty and proxy to `127.0.0.1:8080`. Your proxy must:

- terminate HTTPS;
- send `Host`, `X-Forwarded-For`, and `X-Forwarded-Proto`;
- permit Livewire requests and downloads;
- use request timeouts appropriate for normal web requests.

Set:

```dotenv
APP_URL=https://wallai.example.com
WALLAI_BIND_ADDRESS=127.0.0.1
SESSION_SECURE_COOKIE=true
TRUSTED_PROXIES=127.0.0.1
TRUSTED_HOSTS=wallai.example.com
```

For a proxy running in another container, use its subnet or an exact container-network address in `TRUSTED_PROXIES`.

Do not expose port 8080 publicly when a reverse proxy is used, because that would allow clients to bypass proxy access controls and TLS.
