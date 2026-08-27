# Self-hosting quick start

WallAI supports Linux on `amd64` and `arm64`. Docker Desktop may also be used on macOS and Windows.

## Requirements

- Docker Engine 20.10 or newer with Docker Compose 2.15 or newer
- 2 GB RAM minimum; 4 GB recommended when using concurrent image jobs
- Persistent disk space for generated wallpapers
- A domain and ports 80/443 for automatic HTTPS on a public server

| Component | Supported |
| --- | --- |
| CPU architecture | Linux `amd64`, Linux `arm64` |
| Docker Engine | 20.10 or newer |
| Docker Compose | 2.15 or newer |
| Database | SQLite, PostgreSQL, MySQL, MariaDB |
| Browser | Current Chrome, Firefox, Safari, or Edge |

## Install on a local machine

```bash
git clone https://github.com/lauroguedes/wallai.git
cd wallai
./bin/wallai install
```

Open `http://localhost:8080`. Choose authenticated mode or browser-session mode on the first-run screen.

To build and test the current checkout instead of pulling the published image:

```bash
./bin/wallai install --local
```

This creates `.env.docker`, builds the image, and serves `http://wallai.localhost:8080`. Subsequent management commands automatically reuse `.env.docker`.

## Install on a public server

Create an `A` or `AAAA` DNS record for the server and allow inbound TCP 80/443, then run:

```bash
git clone https://github.com/lauroguedes/wallai.git
cd wallai
./bin/wallai install --domain wallai.example.com --version 2.1.0
```

The installer configures `APP_URL`, `WALLAI_DOMAIN`, secure cookies, and trusted hosts. Configure SMTP or an external database in the generated `.env` when needed.

Run `./bin/wallai install --help` to set a custom image, version, port, bind address, project name, or environment file during installation.

## First-run choices

- **Authentication:** creates the first administrator. New users can only join through administrator invitations.
- **Browser sessions:** no login page. Settings and generated images are isolated by the browser session cookie.

The choice is permanent until `./bin/wallai reset` is run. Resetting deletes all application users, settings, sessions, and generated wallpapers.

## Verify the installation

```bash
./bin/wallai status
./bin/wallai doctor
./bin/wallai logs web
```

The liveness endpoint is `/up`; the dependency-aware readiness endpoint is `/ready`.
