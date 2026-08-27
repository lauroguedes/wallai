# WallAI

[![Build and tests](https://github.com/lauroguedes/wallai/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/lauroguedes/wallai/actions/workflows/ci.yml)
[![Latest release](https://img.shields.io/github/v/release/lauroguedes/wallai?display_name=tag)](https://github.com/lauroguedes/wallai/releases/latest)
[![Laravel 13](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![PHP 8.5](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php&logoColor=white)](https://www.php.net)
[![Docker amd64 and arm64](https://img.shields.io/badge/Docker-amd64%20%7C%20arm64-2496ED?logo=docker&logoColor=white)](https://github.com/lauroguedes/wallai/pkgs/container/wallai)
[![License: MIT](https://img.shields.io/github/license/lauroguedes/wallai)](LICENSE)

WallAI is a self-hosted AI wallpaper generator built with Laravel 13, Livewire 4, and the Laravel AI SDK. It supports OpenAI and Google for text and image generation, plus Ollama for local text models.

## Features

- Generate mobile and desktop wallpapers in 21 curated styles.
- Configure providers, models, and personal API keys in the application.
- Run with authenticated multi-user workspaces or private browser sessions.
- Invite and manage users when authentication is enabled.
- Process image generation and invitations through dedicated Horizon queues.
- Install on any Docker host without installing PHP, Composer, Node.js, or Redis.

## Docker

Requirements: Docker Engine 20.10 or newer, Docker Compose 2.15 or newer, and at least 2 GB of available memory.

### Run the published image

```bash
git clone https://github.com/lauroguedes/wallai.git
cd wallai
./bin/wallai install
```

Open `http://localhost:8080` and complete the first-run setup. The default port only listens on the host loopback interface.

### Build the current checkout locally

```bash
./bin/wallai install --local
```

Local mode creates an ignored `.env.docker`, builds the source with Docker, and opens WallAI at `http://wallai.localhost:8080`. The `.localhost` hostname resolves to the local machine without DNS or a hosts-file entry. Later commands automatically reuse `.env.docker`.

| Local value | Purpose |
| --- | --- |
| `WALLAI_BUILD_LOCAL=true` | Builds the current checkout instead of pulling GHCR. |
| `WALLAI_IMAGE=wallai`, `WALLAI_VERSION=local` | Gives the local image a predictable name. |
| `WALLAI_PROJECT_NAME=wallai-local` | Keeps local containers and volumes isolated. |
| `APP_URL=http://wallai.localhost:8080` | Provides a zero-configuration browser URL. |
| `WALLAI_DOMAIN=` | Disables the public HTTPS proxy locally. |
| `SESSION_SECURE_COOKIE=false` | Allows the local HTTP session cookie. |

Use `--port`, `--project-name`, or `--bind-address` when the defaults conflict with another local service:

```bash
./bin/wallai install --local --port 8090 --project-name wallai-dev
```

### Install on a server

Point the domain to the server, allow inbound TCP ports 80 and 443, then run:

```bash
./bin/wallai install --domain wallai.example.com --version 2.1.0
```

The installer derives the secure production settings automatically:

```dotenv
APP_URL=https://wallai.example.com
WALLAI_DOMAIN=wallai.example.com
SESSION_SECURE_COOKIE=true
TRUSTED_HOSTS=wallai.example.com
WALLAI_VERSION=2.1.0
```

WallAI will start its automatic HTTPS proxy and obtain a certificate from Let's Encrypt.

Additional install options include `--image`, `--env-file`, `--port`, `--bind-address`, and `--project-name`. Run `./bin/wallai install --help` for the complete list. SMTP and external database settings remain editable in the generated environment file; see the [configuration reference](docs/self-hosting/configuration.md).

## Management

```bash
./bin/wallai status
./bin/wallai logs
./bin/wallai doctor
./bin/wallai backup
./bin/wallai update
```

Run `./bin/wallai help` for every available command.

## Documentation

- [Self-hosting quick start](docs/self-hosting/quick-start.md)
- [Configuration reference](docs/self-hosting/configuration.md)
- [Architecture and persisted data](docs/self-hosting/architecture.md)
- [HTTPS and reverse proxies](docs/self-hosting/reverse-proxy.md)
- [Ollama](docs/self-hosting/ollama.md)
- [Backup and restore](docs/self-hosting/backup-restore.md)
- [Upgrading](docs/self-hosting/upgrading.md)
- [Security](docs/self-hosting/security.md)
- [Troubleshooting](docs/self-hosting/troubleshooting.md)
- [Development](docs/development.md)
- [Maintainer release process](docs/releasing.md)
- [Changelog](CHANGELOG.md)
- [Security policy](SECURITY.md)

## Local development

Native development requires PHP 8.4 or newer, Node.js, and Redis. Docker source builds require only Docker. See the [development guide](docs/development.md).

## License

WallAI is released under the [MIT License](LICENSE).
