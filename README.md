# WallAI

WallAI is a self-hosted AI wallpaper generator built with Laravel 13, Livewire 4, and the Laravel AI SDK. It supports OpenAI and Google for text and image generation, plus Ollama for local text models.

## Features

- Generate mobile and desktop wallpapers in 21 curated styles.
- Configure providers, models, and personal API keys in the application.
- Run with authenticated multi-user workspaces or private browser sessions.
- Invite and manage users when authentication is enabled.
- Process image generation and invitations through dedicated Horizon queues.
- Install on any Docker host without installing PHP, Composer, Node.js, or Redis.

## Docker quick start

Requirements: Docker Engine 20.10 or newer, Docker Compose 2.15 or newer, and at least 2 GB of available memory.

```bash
git clone https://github.com/lauroguedes/wallai.git
cd wallai
./bin/wallai install
```

Open `http://localhost:8080` and complete the first-run setup. The default port only listens on the host loopback interface.

For a public server, configure a domain before installing:

```bash
cp .env.docker.example .env
```

Set these values in `.env`:

```dotenv
APP_URL=https://wallai.example.com
WALLAI_DOMAIN=wallai.example.com
SESSION_SECURE_COOKIE=true
TRUSTED_HOSTS=wallai.example.com
```

Point the domain to the server, allow TCP ports 80 and 443, then run:

```bash
./bin/wallai install
```

WallAI will start its automatic HTTPS proxy and obtain a certificate from Let's Encrypt.

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

The application requires PHP 8.4 or newer, Node.js, and Redis for local development. See the [development guide](docs/development.md).

## License

WallAI is released under the [MIT License](LICENSE).
