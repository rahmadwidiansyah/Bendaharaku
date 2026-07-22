# Deployment

## CI/CD Pipeline

Push to `main` triggers:

```
Push/PR
  │
  ├── Lint (Pint + ESLint)
  ├── Laravel Tests (PHPUnit + PostgreSQL)
  ├── Python Tests (pytest, 30 tests)
  └── Integration Test (HTTP → FastAPI → JSON)
  │
  └── Docker Build & Publish → GHCR (tag: latest, sha-*)
       │
       └── n8n Webhook → Production Deploy
```

### Build Artifacts

Single image published to GitHub Container Registry:

```
ghcr.io/rahmadwidiansyah/bendaharaku
  ├── latest
  ├── sha-{commit}
  └── main-{branch}
```

### Rollback

```bash
docker pull ghcr.io/rahmadwidiansyah/bendaharaku:sha-PREVIOUS_COMMIT
docker compose up -d
```

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `APP_ENV` | `production` | Application environment |
| `APP_KEY` | — | Laravel app key (required) |
| `DB_HOST` | `db` | PostgreSQL host |
| `DB_DATABASE` | `laravel` | Database name |
| `DB_USERNAME` | `sail` | Database user |
| `DB_PASSWORD` | — | Database password |
| `AI_PARSER_URL` | `http://ai-parser:3987` | AI Parser internal URL |
| `LOG_CHANNEL` | `stdout` | Log channel (stdout for Docker) |

## Production Checklist

- [ ] Generate and set `APP_KEY`
- [ ] Set strong `DB_PASSWORD`
- [ ] Configure `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure Google OAuth credentials
- [ ] Set Telegram bot token
- [ ] Configure LLM API keys (Gemini/OpenAI/DeepSeek)
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Verify health: `GET /health` returns 200
