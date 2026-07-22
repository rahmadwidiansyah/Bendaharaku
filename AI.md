# AI Parser

## Architecture

The AI Parser lives at `script_pencatat_keuangan/` inside the Bendaharaku repository. It runs as a Python FastAPI service.

```
User input text
      │
      ▼
LocalRuleEngine (PHP, regex/keyword)
      │
      ├── matched → return (zero latency)
      │
      ▼
Python NLP (FastAPI + thefuzz)
      │
      ├── confidence >= 0.85 → return
      │
      ▼
LLM Provider (Gemini / OpenAI / DeepSeek)
      │
      └── return
```

## Service

```
script_pencatat_keuangan/
├── app/
│   ├── main.py              # FastAPI app, startup, health probes
│   ├── config.py            # Env-based configuration
│   ├── schemas.py           # Pydantic request/response models
│   ├── services/
│   │   ├── amount.py        # Nominal amount extraction
│   │   ├── intent.py        # Transaction intent classification
│   │   ├── subject.py       # Hashtag subject extraction
│   │   ├── wallet.py        # Fuzzy wallet matching
│   │   ├── category.py      # Fuzzy category matching
│   │   └── confidence.py    # Confidence scoring
│   └── routers/
│       └── analyze.py       # POST /analyze endpoint
├── tests/
│   ├── conftest.py
│   └── test_analyze.py      # 30 unit + integration tests
├── requirements.txt
└── README.md
```

## Endpoints

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/health` | Overall health |
| `GET` | `/live` | Liveness probe |
| `GET` | `/ready` | Readiness probe |
| `POST` | `/analyze` | Parse transaction text |

## Communication

Laravel → Python via HTTP (internal Docker network), no API key.

Config: `AI_PARSER_URL=http://ai-parser:3987`

## Adding a New Parser

1. Create a new module in `script_pencatat_keuangan/app/services/`
2. Register the endpoint in `script_pencatat_keuangan/app/routers/`
3. Add tests in `script_pencatat_keuangan/tests/`
4. Update `AIManager.php` to include the new provider in the pipeline
