---
paths:
  - 'app/**'
---

# App

## Ollama is a text-only provider
Laravel AI 0.11 supports Ollama for text generation and embeddings, but not image generation. Keep Ollama out of image-generation capabilities; use a server-reachable base URL and allow arbitrary installed local model names.
