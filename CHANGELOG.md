# Changelog

All notable changes to WallAI are documented here. Releases follow Semantic Versioning.

## Unreleased

### Added

- Zero-configuration local Docker source builds through `./bin/wallai install --local`.
- Installer options for production domains, versions, ports, bind addresses, project names, images, and environment files.

### Changed

- Docker management commands automatically reuse `.env.docker` when no `.env` exists.
- The README now includes project status badges and concise local and production Docker instructions.

### Security

- `.env.docker` is ignored to prevent deployment configuration or credentials from being committed.

## 2.1.0 - 2026-08-25

### Added

- Docker-based self-hosting for `amd64` and `arm64`.
- Separate web, Horizon, scheduler, Redis, and initialization services.
- Optional automatic HTTPS with Caddy.
- Installation, diagnostics, backup, restore, update, and reset commands.
- Authenticated multi-user and browser-session first-run modes.
- OpenAI, Google, and Ollama provider settings.
- Multi-architecture GHCR release automation, SBOMs, vulnerability scanning, and provenance attestations.
