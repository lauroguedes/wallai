# Changelog

All notable changes to WallAI are documented here. Releases follow Semantic Versioning.

## Unreleased

## 2.1.1 - 2026-08-29

### Added

- Zero-configuration local Docker source builds through `./bin/wallai install --local`.
- Installer options for production domains, versions, ports, bind addresses, project names, images, and environment files.
- Persistent light and dark themes through the Mary UI theme toggle.
- A complete branded favicon and web app manifest set for browsers, Apple devices, Android devices, and installed web apps.

### Changed

- Docker management commands automatically reuse `.env.docker` when no `.env` exists.
- The README now includes project status badges and concise local and production Docker instructions.
- Frontend builds now use Node.js 26 and include Mary UI vendor templates during Tailwind compilation.
- Local Docker installs run with `APP_ENV=local` and debug mode enabled, while public-domain installs enforce production-safe values.
- The WallAI logo now uses the new adaptive primary, secondary, and foreground SVG artwork.
- Sidebar patterns, preview mockups, and device selection controls now render correctly in both light and dark themes.

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
