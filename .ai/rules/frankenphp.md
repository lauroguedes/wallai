---
paths:
  - 'docker/frankenphp/**'
---

# Frankenphp

## Keep the FrankenPHP entry point minimal
The custom entry point exists so WallAI can control and patch the embedded Go dependency graph. Do not add optional Caddy modules without a documented runtime requirement and a clean image vulnerability scan.
