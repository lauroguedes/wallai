---
paths:
  - Dockerfile
---

# Dockerfile

## Support Docker Engine 20.10 builds
Pin Dockerfile frontend 1.4 because the project uses no newer-only instructions and frontend 1.7 fails on Engine 20.10 / Buildx 0.10. Install Composer dependencies from the extension-complete PHP runtime stage so Horizon's pcntl requirement is validated against production.

## Build a security-patched FrankenPHP binary
Build FrankenPHP from the version-matched official builder and pin security-sensitive Go dependency upgrades. Keep the resulting runtime image clean under the repository's HIGH/CRITICAL Trivy gate.
