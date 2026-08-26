# Ollama

Ollama is supported for text prompt generation. Image generation must use OpenAI or Google because the installed Laravel AI SDK does not provide Ollama image generation.

## Ollama on the Docker host

WallAI maps `host.docker.internal` on Linux, macOS, and Windows. Configure Ollama to listen on an address reachable from Docker, then use:

```dotenv
OLLAMA_BASE_URL=http://host.docker.internal:11434
```

Select Ollama in WallAI settings and enter any text model installed on the Ollama server.

## Ollama on another server

Use an internal URL such as:

```dotenv
OLLAMA_BASE_URL=http://ollama.internal:11434
OLLAMA_ALLOWED_HOSTS=ollama.internal
```

Only exact hosts in `OLLAMA_ALLOWED_HOSTS` can be saved or contacted. Do not expose an unauthenticated Ollama endpoint to the public internet. Restrict it with a private network, firewall, VPN, or authenticated gateway.

## Troubleshooting

Run the connectivity check from the web container:

```bash
docker compose exec web wallai-entrypoint php -r 'var_dump(file_get_contents(getenv("OLLAMA_BASE_URL")."/api/tags"));'
```

If the host endpoint is refused, verify Ollama's listen address and host firewall. GPU configuration belongs to the Ollama deployment and is intentionally not forced into WallAI's base stack.
