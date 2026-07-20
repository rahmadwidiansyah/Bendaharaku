# Environment Setup - Chat Bendaharaku (Developer Guide)

Goal: Developers should be able to run the full Chat stack locally without production dependencies.

Prereqs:
- Docker & Docker Compose
- PHP 8.x, Composer
- Node.js & npm/yarn
- Git

Quickstart (example):

1. Clone repo
   - git clone <repo>
2. Copy env
   - cp .env.example .env
   - Fill DB credentials, TELEGRAM_BOT_TOKEN (mock), AI_PROVIDER (mock)
3. Start services
   - docker compose up -d
4. Install PHP deps
   - docker compose exec app composer install --no-interaction
5. Install JS deps
   - docker compose exec app npm install
6. Migrate & seed
   - docker compose exec app php artisan migrate --seed
7. Run dev servers
   - docker compose exec app npm run dev
8. Login (seeded user)
   - credentials: user@example.test / password
9. Start AI mock
   - php artisan chat:ai-mock --port=xxxx  (or run local stub service)
10. Start Telegram mock
   - run provided telegram-mock script or use ngrok to expose local webhook

Verification steps:
- Open http://localhost and log in
- Open Chat and send `/saldo` and `/ringkasan`
- Verify AI responses come from mock (check request logs)
- Verify Telegram messages via mock interface

Notes:
- Provide script `scripts/dev-setup.sh` for repeatable setup (future task)
- Document known blockers and workarounds below

Known blockers:
- Missing Postgres / PHP pdo_pgsql in local shell — tests require a running DB. Use Docker Compose test stack.
- Telegram webhook cannot be tested without ngrok or local webhook mock.

Run unit tests (recommended):

```bash
# Start stack (provides Postgres + PHP with pdo_pgsql)
docker compose up -d

# Install deps once
docker compose exec app composer install --no-interaction

# Run targeted tests
docker compose exec app php artisan test --filter TelegramAdapterTest
```

Add these commands to README or scripts/dev-setup.sh for reproducible dev setup.

