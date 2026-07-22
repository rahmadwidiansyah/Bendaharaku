# Architecture

## Overview

Bendaharaku is a Laravel + Vue (Inertia) financial tracking application with an integrated AI Parser service for natural language transaction parsing.

```
User (Browser / Telegram)
        │
        ▼
┌───────────────┐     ┌──────────────┐
│   Nginx :80   │────▶│  Laravel App │
└───────────────┘     │  PHP 8.4     │
                      │  Apache      │
                      └──────┬───────┘
                             │
               ┌─────────────┴─────────────┐
               ▼                           ▼
        ┌──────────┐             ┌──────────────────┐
        │ PostgreSQL│             │   AI Parser       │
        │ (Persist) │             │   Python 3.11     │
        └──────────┘             │   FastAPI :3987   │
                                 └──────────────────┘
```

### Key Principles

- **Single Docker image** containing both Laravel + Python runtimes
- **Process isolation** via Docker Compose — each container runs one process
- **Circuit breaker pipeline**: LocalRuleEngine → Python NLP → LLM (Gemini/OpenAI/DeepSeek)
- **No internal API key** — communication secured via Docker bridge network

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.4, Laravel, Apache |
| Frontend | Vue 3, Inertia, Vite, Tailwind CSS |
| Database | PostgreSQL 15 |
| AI Parser | Python 3.11, FastAPI, thefuzz |
| LLM | Gemini, OpenAI, DeepSeek |
| Container | Docker |
| CI/CD | GitHub Actions, GHCR |
