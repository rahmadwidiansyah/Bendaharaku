#!/usr/bin/env bash
set -euo pipefail

echo "Starting local dev environment for Bendaharaku Chat..."

echo "Bringing up Docker Compose stack (Postgres, app)..."
docker compose up -d

echo "Installing PHP dependencies inside container..."
docker compose exec app composer install --no-interaction

echo "Installing JS dependencies inside container..."
docker compose exec app npm install --silent

echo "Running migrations and seeders..."
docker compose exec app php artisan migrate --seed --force

echo "Running targeted tests (TelegramAdapterTest)..."
docker compose exec app php artisan test --filter TelegramAdapterTest

echo "Dev setup complete. Open http://localhost and login with seeded user (see ENVIRONMENT_SETUP.md)."}