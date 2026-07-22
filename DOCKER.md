# Docker

## Image Structure

A single Docker image (`bendaharaku`) contains both runtimes:

```
bendaharaku:latest
├── PHP 8.4 + Apache (Laravel)
├── Python 3.11 + FastAPI (AI Parser)
├── Composer dependencies
├── Built Vue assets
└── Entrypoint (mode selector)
```

## Modes

The image supports two runtime modes via first command arg or `RUN_MODE` env:

| Mode | Command | Runs |
|------|---------|------|
| Apache only (default) | `docker run bendaharaku` | Laravel only |
| AI Parser only | `docker run bendaharaku ai-parser` | Python only |

Each container runs exactly one process, following Docker best practices.

## Docker Compose (Recommended)

```bash
# Start all services
docker compose up -d

# View logs
docker compose logs -f app
docker compose logs -f ai-parser

# Rebuild image
docker compose build --no-cache

# Stop
docker compose down
```

Three containers run from the same image:

| Container | Command | Port |
|-----------|---------|------|
| `app` | `apache-only` | :80 (via nginx :4000) |
| `ai-parser` | `ai-parser` | :3987 (internal only) |
| `nginx` | nginx | :4000 (public) |
| `db` | PostgreSQL | :5432 (internal) |

## Health Checks

| Service | Endpoint | Container |
|---------|----------|-----------|
| Laravel | `GET /health` | `app` |
| AI Parser | `GET /health` | `ai-parser` |
| AI Parser | `GET /live` | `ai-parser` |
| AI Parser | `GET /ready` | `ai-parser` |
| PostgreSQL | `pg_isready` | `db` |

## Development

```bash
# With hot-reload
docker compose run --rm node run dev

# AI Parser auto-reload (override command)
docker compose run --rm -p 3987:3987 ai-parser python3 -m uvicorn app.main:app --host 0.0.0.0 --port 3987 --reload
```
