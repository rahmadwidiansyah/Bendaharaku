# Dokumentasi Implementasi: Chat AI Pipeline — Bendaharaku

> **Tanggal Update:** 16 Juli 2026
> **Proyek:** Bendaharaku — Aplikasi Keuangan Personal (Laravel + Inertia/Vue)
> **Scope:** Alur lengkap chat dari Telegram → Laravel → Python AI → LLM Fallback → Database

---

## 1. Gambaran Arsitektur Sistem (Bird's Eye View)

```
[User kirim chat di Telegram]
        |
        v
POST /api/telegram/webhook
        |
        v
TelegramWebhookController@handle()
        |
        +---> Cek perintah? (/saldo, /web, /help) --> balas langsung
        |
        +---> Teks bebas? --> kirim "⏳ Siap, lagi dicerna AI..."
                |
                v
        ChatTransactionOrchestrator::process()
                |
                +---> 1. Ambil wallets + categories dari DB (milik user)
                +---> 2. Tarik activeMemories dari UserMemoryService (RAG)
                +---> 3. AIManager::parseTransaction()
                |           |
                |           +---> [CB1] PythonNLPProvider  --> FastAPI lokal
                |           |       confidence >= 0.85? --> return hasil
                |           |       gagal/rendah?        --> fallback ke LLM
                |           |
                |           +---> [CB2] LLM Provider (Gemini/OpenAI/DeepSeek)
                |                   dipilih berdasarkan UserAiPreference user
                |
                +---> 4. Validasi dasar (amount, category, hashtag untuk debt)
                +---> 5. TransactionResolver::resolve() --> nama -> ID database
                +---> 6. ConfidenceScoringEngine::calculateFinalScore()
                +---> 7. AiParseLogService::createLog() --> tabel ai_parse_logs
                +---> 8. ProcessTransactionAction::create() --> tabel transaction_logs
                +---> 9. event(TransactionPosted) --> async memory + dataset update
                |
                v
        TelegramWebhookController --> sendMessage() --> reply ke user
```

---

## 2. Struktur Folder yang Terlibat

```
app/
├── Http/Controllers/
│   └── TelegramWebhookController.php       ← Entry point webhook Telegram
│
├── Services/
│   ├── Chat/
│   │   └── ChatTransactionOrchestrator.php ← Orkestrasi alur lengkap
│   │
│   └── AI/
│       ├── AIManager.php                   ← Circuit breaker + routing provider
│       ├── AiCredentialManager.php         ← Kelola API key per-user (DB)
│       ├── AiPreferenceManager.php         ← Kelola provider aktif per-user (DB)
│       ├── AiProviderFactory.php           ← Factory: pilih instance provider
│       ├── AiParseLogService.php           ← Catat log parsing AI ke DB
│       ├── TransactionResolver.php         ← Translate nama string → ID DB
│       ├── TransactionValidationService.php← Hard validation + confidence guard
│       │
│       ├── Providers/
│       │   ├── PythonNLPProvider.php       ← HTTP call ke FastAPI Python lokal
│       │   ├── GeminiProvider.php          ← HTTP call ke Google Gemini API
│       │   ├── OpenAIProvider.php          ← HTTP call ke OpenAI API
│       │   └── DeepSeekProvider.php        ← HTTP call ke DeepSeek API
│       │
│       ├── Prompt/
│       │   └── TransactionPromptBuilder.php← Bangun JSON prompt untuk LLM
│       │
│       ├── Memory/
│       │   ├── UserMemoryService.php       ← RAG: simpan & ambil memori user
│       │   └── MemoryDecayEngine.php       ← Hitung peluruhan bobot memori
│       │
│       └── Scoring/
│           ├── ConfidenceScoringEngine.php ← Hitung skor kepercayaan final
│           └── Matchers/
│               ├── CategoryMatchService.php← Validasi kategori ada di DB
│               ├── WalletMatchService.php  ← Validasi dompet ada di DB
│               └── MemoryMatchService.php  ← Hitung skor cocok memori
│
├── DTO/
│   ├── AiProviderRequest.php               ← Data yang dikirim ke provider AI
│   ├── AIParseResult.php                   ← Hasil balikan dari semua provider
│   ├── ParsedTransaction.php               ← Data transaksi hasil parse AI
│   ├── ResolvedTransaction.php             ← Data transaksi + ID hasil resolving
│   └── ConfidenceScoreContext.php          ← Konteks scoring engine (baru dibuat)
│
├── Actions/
│   └── ProcessTransactionAction.php        ← Mutasi saldo + simpan ke DB
│
└── Events/
    └── TransactionPosted.php               ← Event async pasca transaksi tersimpan

routes/
└── api.php                                 ← Route: POST /api/telegram/webhook

config/
├── services.php                            ← Konfigurasi: telegram, python_ai
└── bendaharaku.php                         ← Konfigurasi: threshold, weights, memory

/home/widi/Belajar/python/script_pencatat_keuangan/
└── main.py                                 ← Python FastAPI NLP service (port 3987)
```

---

## 3. Alur Detail Langkah per Langkah

### 3.1 Entry Point: Telegram Webhook

**File:** `app/Http/Controllers/TelegramWebhookController.php`
**Route:** `POST /api/telegram/webhook` (di `routes/api.php`, bebas CSRF)

```json
// Payload dari Telegram Server:
{
  "message": {
    "chat": { "id": 123456789 },
    "text": "Beli bensin 25k cash"
  }
}
```

**Yang dilakukan controller:**
1. Ambil `chat_id` dan `text` dari payload Telegram
2. `User::where('telegram_id', $chatId)->first()` — jika tidak ditemukan → tolak & balas pesan error
3. Cek perintah sistem:
   - `/saldo` → `handleSaldoCommand()` — query wallets Asset/Liquid, format tabel ascii
   - `/web` → balas link dashboard
   - `/start`, `/help`, `hai`, `halo`, dll → `handleHelpCommand()`
4. Teks bebas → kirim "⏳ Siap, lagi dicerna AI..." lalu panggil Orchestrator

---

### 3.2 Orchestrator: Siapkan Konteks

**File:** `app/Services/Chat/ChatTransactionOrchestrator.php`
**Method:** `process(User $user, string $text, string $source = 'TEL')`

#### Step 1 — Ambil Data Konteks dari DB

```php
$wallets    = $user->wallets()->get(['id', 'name', 'group_type', 'keyword'])->toArray();
$categories = $user->categories()->get(['id', 'category_name', 'type_id', 'keyword'])->toArray();
```

| Field Wallet | Kegunaan |
|---|---|
| `id` | Untuk resolving nama → ID |
| `name` | Nama dompet (BCA, Dana, Cash) |
| `group_type` | Asset / Liquid / System — Python skip System wallet |
| `keyword` | Alias (misal: "bca,bank central") untuk cocokkan teks user |

| Field Kategori | Kegunaan |
|---|---|
| `id` | Untuk resolving nama → ID |
| `category_name` | Nama kategori (Makanan, Transport, BBM) |
| `type_id` | ID tipe (income/expense) |
| `keyword` | Alias (misal: "bensin,pertamax,solar") untuk kategori BBM |

#### Step 2 — Tarik Memori Personal (RAG)

```php
$activeMemories = $this->memoryService->getTopRelevantMemories($user->id, $text);
```

- Cache memori user dari tabel `user_ai_memories` selama **5 menit** (`ai-mem-v2-{userId}`)
- Filter: hanya memori yang keyword-nya cocok `\bkeyword\b` di teks input
- Kembalikan maks **5 memori** paling relevan (bobot tertinggi)

```php
// Format memori yang dikembalikan:
[
  ['keyword' => 'budi',      'category' => 'Hutang'],
  ['keyword' => 'pertamina', 'category' => 'BBM'],
]
```

---

### 3.3 AIManager: Circuit Breaker Dual-Layer

**File:** `app/Services/AI/AIManager.php`

#### Circuit Breaker 1: Python NLP Lokal

Sebelum kirim ke Python, keyword memori di-inject ke daftar keyword kategori:

```php
foreach ($activeMemories as $memory) {
    foreach ($pythonCategories as &$cat) {
        if ($cat['category_name'] === $memory['category']) {
            $cat['keyword'] .= ',' . $memory['keyword'];
        }
    }
}
```

**Payload dikirim ke Python:**

```json
{
  "text": "Beli bensin 25k cash",
  "wallets": [
    { "name": "BCA",             "group_type": "Asset",  "keyword": "bca,bank central" },
    { "name": "Cash",            "group_type": "Liquid", "keyword": "cash,tunai" },
    { "name": "Merchant System", "group_type": "System", "keyword": null }
  ],
  "categories": [
    { "category_name": "BBM",     "keyword": "bensin,pertamax,solar" },
    { "category_name": "Makanan", "keyword": "makan,nasi,warteg" }
  ]
}
```

> Python abaikan wallet ber-`group_type = "System"` saat matching. ID diselesaikan oleh Laravel, bukan Python.

**Threshold keberhasilan:** `confidence >= 0.85`
- Ya → return hasil Python langsung, **tidak panggil LLM**
- Tidak → lanjut ke Circuit Breaker 2

#### Circuit Breaker 2: LLM Eksternal

1. `AiPreferenceManager::getActivePreference($user)` → ambil provider aktif dari `user_ai_preferences`
2. `AiCredentialManager::getCredential($user, $provider)` → ambil API key dari `user_ai_credentials`
3. `AiProviderFactory::make($provider)` → buat instance Gemini/OpenAI/DeepSeek
4. Kirim ke LLM dengan memori aktif ikut masuk ke prompt

Jika user belum setup LLM DAN Python down → throw `AiConfigurationException` → Telegram reply instruksi setup Settings AI.

---

### 3.4 Python NLP Provider

**File:** `app/Services/AI/Providers/PythonNLPProvider.php`
**Endpoint:** `POST {PYTHON_AI_URL}/analyze`
**Auth:** Header `X-API-KEY`
**Timeout:** 8 detik

**Config di `config/services.php`:**
```php
'python_ai' => [
    'url' => env('PYTHON_AI_URL'),   // contoh: http://localhost:3987
    'key' => env('PYTHON_AI_KEY', 'kunci-rahasia-v4'),
],
```

**Python FastAPI (`main.py`) — Cara Kerja:**

| Langkah | Proses |
|---|---|
| 1. Ekstrak nominal | Regex suffix: `25k`→25000, `1.5jt`→1500000, atau angka murni >= 1000 |
| 2. Cocokkan dompet | Regex `\bkeyword\b` per wallet, backup fuzzy >= 85%, skip System wallet |
| 3. Cocokkan kategori | Regex `\bkeyword\b`, fallback fuzzy >= 60% |
| 4. Tebak intent | Keyword-based: transfer / hutang (debt) / piutang / income / expense |
| 5. Hitung confidence | +0.3 nominal, +0.3 kategori, +0.3 dompet, +0.1 subject |
| 6. Tentukan is_cleared | `nominal AND kategori AND dompet AND confidence >= 0.8` |

**Response Python → Laravel:**
```json
{
  "success": true,
  "amount": 25000.0,
  "transaction_type": "expense",
  "category": "BBM",
  "source_wallet": "Cash",
  "destination_wallet": null,
  "subject": null,
  "notes": "Beli bensin 25k cash",
  "is_cleared": true,
  "confidence": 0.9
}
```

---

### 3.5 Gemini Provider (LLM Fallback)

**File:** `app/Services/AI/Providers/GeminiProvider.php`
**Endpoint:** `POST https://generativelanguage.googleapis.com/v1/models/{model}:generateContent?key={apiKey}`
**Timeout:** 15 detik, retry 2x (1 detik delay)

**Prompt dikirim (dibuat `TransactionPromptBuilder::build()`):**
```json
{
  "instruction": "Extract financial transaction. Return strictly JSON schema: {amount, transactionType, category, sourceWallet, destinationWallet, subject, notes, isCleared, confidence}",
  "text": "Beli bensin 25k cash",
  "available_wallets": ["BCA", "Cash", "Dana"],
  "available_categories": ["BBM", "Makanan", "Transport"],
  "historical_patterns_guidance": "Use these mappings as strong hints if keyword matches.",
  "user_historical_patterns": [
    { "keyword": "pertamina", "target_category": "BBM" }
  ]
}
```

**Response yang diharapkan dari Gemini:**
```json
{
  "amount": 25000,
  "transactionType": "expense",
  "category": "BBM",
  "sourceWallet": "Cash",
  "destinationWallet": null,
  "subject": null,
  "notes": "Beli bensin 25k cash",
  "isCleared": true,
  "confidence": 0.92
}
```

| HTTP Status | Handling |
|---|---|
| `429` | throw `AiRateLimitException('Gemini')` |
| `408, 503, 504` | throw `AiTimeoutException('Gemini')` |
| `401, 403` | throw `AiProviderException` — API Key invalid |
| `ConnectionException` | throw `AiTimeoutException` |

---

### 3.6 Validasi Hasil AI

**`TransactionValidationService::validateAndGuard()`:**
- `amount <= 0` → return `AIParseResult::failure()`
- `transactionType` null → return `AIParseResult::failure()`
- `confidence < 0.80` → paksa `isCleared = false` (DRAFT, tapi tetap lanjut proses)

**Validasi tambahan di Orchestrator:**
- Tidak ada `amount` → return false ke Telegram
- Tidak ada `category` → return false ke Telegram
- Transaksi debt/receivable tapi tidak ada `#hashtag` → return false ke Telegram

---

### 3.7 Resolving: Nama String → ID Database

**File:** `app/Services/AI/TransactionResolver.php`

Pencarian dilakukan secara berurutan:
1. **Exact match** — `strtolower(nama) === strtolower(input)`
2. **Keyword token match** — split keyword dengan `,|;`, cek apakah input ada di token

Alokasi dompet berdasarkan `TransactionIntent`:

| Intent | sourceWalletId | destinationWalletId |
|---|---|---|
| `expense` | Dompet user dari teks | System: *Merchant System* |
| `income` | System: *External System* | Dompet user dari teks |
| `transfer` | Dompet asal dari teks | Dompet tujuan dari teks |
| `debt` | Dompet user dari teks | System: *System Hutang* |
| `receivable` | Dompet user dari teks | System: *System Piutang* |

System wallet dikonfigurasi di `config/bendaharaku.php`:
```php
'system_wallets' => [
    'merchant'   => env('SYSTEM_WALLET_MERCHANT', 'Merchant System'),
    'external'   => env('SYSTEM_WALLET_EXTERNAL', 'External System'),
    'debt'       => env('SYSTEM_WALLET_DEBT', 'System Hutang'),
    'receivable' => env('SYSTEM_WALLET_RECEIVABLE', 'System Piutang'),
],
```

**Jika kategori/dompet tidak ditemukan:**
- `CategoryNotFoundException` atau `WalletNotFoundException` di-catch Orchestrator
- Buat `ResolvedTransaction` dengan semua ID `null`, `isCleared = false`
- Simpan parse log, kemudian **return pesan DRAFT ke Telegram** (tidak simpan ke DB — FK constraint)

---

### 3.8 Confidence Scoring Engine

**File:** `app/Services/AI/Scoring/ConfidenceScoringEngine.php`
**Input:** `ConfidenceScoreContext` (user, inputText, AIParseResult, ResolvedTransaction, activeMemories)

**Formula:**
```
FinalScore = (ai_base × 0.40) + (memory_match × 0.25) + (category_match × 0.15) + (wallet_match × 0.20)
```

| Komponen | Bobot | Dari Mana |
|---|---|---|
| `ai_base` | 40% | Field `confidence` dari response JSON provider |
| `memory_match` | 25% | `MemoryMatchService` — ada keyword memori di teks input? |
| `category_match` | 15% | `CategoryMatchService` — kategori terdaftar di DB user? |
| `wallet_match` | 20% | `WalletMatchService` — dompet sesuai intent transaksi? |

**Threshold:** `config('bendaharaku.ai.confidence.threshold_auto_clear', 0.85)`
- `>= 0.85` → `is_cleared = true` (transaksi langsung valid, saldo termutasi)
- `< 0.85`  → `is_cleared = false` (DRAFT, user konfirmasi via web)

> Scoring engine hanya berjalan jika `TransactionResolver` sukses (IDs tidak null).

---

### 3.9 Simpan ke Database

**File:** `app/Actions/ProcessTransactionAction.php`

```php
// Data dikirim ke action:
[
    'date'                  => now()->format('Y-m-d'),
    'category_id'           => $resolved->categoryId,          // int
    'source_wallet_id'      => $resolved->sourceWalletId,      // int
    'destination_wallet_id' => $resolved->destinationWalletId, // int
    'amount'                => $resolved->amount,
    'subject'               => $finalSubject,   // #hashtag atau nama user
    'notes'                 => $text . ($isCleared ? '' : ' [DRAFT AI]'),
    'is_cleared'            => $resolved->isCleared,
]
```

Action:
- Validasi `amount > 0` dan `source_wallet_id != destination_wallet_id`
- Lock wallet rows dengan `lockForUpdate()` (concurrency safe)
- **Mutasi saldo hanya jika `is_cleared = true`** — DRAFT tidak ubah saldo
- Simpan ke `transaction_logs` dengan `reference_number = TEL-{ULID}`

---

### 3.10 Event Async: Sistem Belajar

```php
event(new TransactionPosted($user, $transactionLog, $parseLogId));
```

Listener yang bereaksi:
- **`LinkParseLogToTransaction`** → update `ai_parse_logs.transaction_log_id`, set `status = 'posted'/'draft'`
- **`UpdateUserMemoryOnTransaction`** → `UserMemoryService::upsertMemory()` — rekam pola subject → kategori/dompet ke `user_ai_memories`

---

### 3.11 Reply ke Telegram

**Sukses (`is_cleared = true`):**
```
✅ *TRANSAKSI BERHASIL*
_Pengeluaran 🔴_

🏷 *Ref ID    :* `TEL-01J...`
💰 *Nominal :* Rp 25.000
📂 *Kategori :* BBM
📤 *Sumber  :* Cash
📥 *Tujuan  :* Merchant System
👤 *Pihak     :* WIDI

💬 *Pesan Asli:*
_Beli bensin 25k cash_
```

**DRAFT — kategori/dompet dikenali tapi skor rendah:**
```
📝 *MASUK DRAFT (Butuh Cek Web)*
_Pengeluaran 🔴_
[... detail transaksi ...]
```

**DRAFT — kategori/dompet TIDAK dikenali (null IDs, tidak disimpan ke DB):**
```
📝 *MASUK DRAFT (Butuh Cek Web)*

AI tidak dapat mengenali kategori atau dompet dari: _Beli bensin 25k cash_

Coba sebutkan nama dompet (contoh: bca, dana, cash) dan kategori yang sudah terdaftar.
Atau cek & lengkapi transaksi draft-nya di 👉 *Dashboard Web*.
```

---

## 4. Alur Data Lengkap (Contoh: "Beli bensin 25k cash")

```
Telegram: "Beli bensin 25k cash"
    │
    ▼
POST /api/telegram/webhook
TelegramWebhookController
    │ cek User by telegram_id → found
    │ kirim "⏳ Siap, lagi dicerna AI..."
    ▼
ChatTransactionOrchestrator::process()
    │
    ├─ wallets    = [{name:"BCA",  keyword:"bca"},
    │                {name:"Cash", keyword:"cash,tunai"}, ...]
    ├─ categories = [{category_name:"BBM",    keyword:"bensin,pertamax"},
    │                {category_name:"Makanan",keyword:"makan,nasi"}, ...]
    ├─ memories   = []  (belum ada memori "bensin")
    │
    ▼ AIManager::parseTransaction()
    │
    ├─ [CB1] PythonNLPProvider
    │    POST http://python-ai:3987/analyze
    │    payload: {text, wallets, categories}
    │    response: {amount:25000, category:"BBM", source_wallet:"Cash", confidence:0.9}
    │    0.9 >= 0.85 ✓ → RETURN PYTHON RESULT (LLM tidak dipanggil)
    │
    ▼ AIParseResult
    │   success=true, confidence=0.9, provider='python-nlp'
    │   transaction: {amount:25000, type:expense, category:"BBM", sourceWallet:"Cash"}
    │
    ▼ TransactionValidationService::validateAndGuard()
    │   amount>0 ✓, type ada ✓, confidence>0.80 ✓ → PASS
    │
    ▼ Validasi Orchestrator
    │   amount ada ✓, category ada ✓, bukan debt ✓
    │
    ▼ TransactionResolver::resolve()
    │   "BBM"            → category_id: 3
    │   "Cash"           → source_wallet_id: 2
    │   "Merchant System"→ destination_wallet_id: 99
    │
    ▼ ConfidenceScoringEngine::calculateFinalScore()
    │   ai_base        = 0.9  × 0.40 = 0.360
    │   memory_match   = 0.0  × 0.25 = 0.000  (belum ada memori "bensin")
    │   category_match = 1.0  × 0.15 = 0.150  (BBM ada di DB user)
    │   wallet_match   = 1.0  × 0.20 = 0.200  (Cash ada di DB, sesuai expense)
    │   finalScore = 0.71 < 0.85 → is_cleared = FALSE (DRAFT)
    │
    ▼ AiParseLogService::createLog()
    │   ai_parse_logs: id=42, provider='python-nlp', confidence=0.71
    │
    ▼ ProcessTransactionAction::create()
    │   saldo TIDAK dimutasi (is_cleared=false)
    │   transaction_logs: ref=TEL-01J..., amount=25000, is_cleared=false
    │
    ▼ event(TransactionPosted)
    │   LinkParseLogToTransaction: ai_parse_logs[42].status = 'draft'
    │   UpdateUserMemoryOnTransaction: subject="-" → skip (tidak ada hashtag)
    │
    ▼ sendMessage() ke Telegram
       "📝 *MASUK DRAFT (Butuh Cek Web)*"
```

> **Kenapa DRAFT?** Score 0.71 < 0.85 karena tidak ada memory_match.
> Setelah user konfirmasi di web → memori direkam → besok "bensin" dapat
> memory_match 0.25 → total score 0.96 → auto-cleared langsung!

---

## 5. Siklus Memori Personal (RAG)

**Tabel:** `user_ai_memories`

| Field | Tipe | Keterangan |
|---|---|---|
| `user_id` | int | Pemilik memori |
| `keyword_pattern` | string | Keyword yang dipelajari (misal: "pertamina", "budi") |
| `category_id` | int FK | Kategori yang terkait |
| `wallet_id` | int FK | Dompet yang terkait |
| `weight` | float | Bobot kepercayaan (0.0 – 5.0) |
| `hit_count` | int | Berapa kali keyword ini muncul |
| `last_applied_at` | timestamp | Kapan terakhir dipakai |

**Siklus:**
- **Lahir:** Transaksi berhasil dengan subject → `upsertMemory()` — weight = 1.0
- **Tumbuh:** Setiap dipakai lagi: +1.0 bobot (cap 5.0)
- **Meluruh:** Setiap hari bobot berkurang 0.05 (`MemoryDecayEngine`)
- **Prune:** Bobot < 0.20 → dihapus
- **Cache:** 5 menit per user di Redis/file (`ai-mem-v2-{userId}`)

---

## 6. Manajemen Kredensial & Provider per User

**Tabel `user_ai_credentials`:**
```
user_id | provider  | api_key   | is_valid
1       | gemini    | AIza...   | true
1       | openai    | sk-...    | true
```

**Tabel `user_ai_preferences`:**
```
user_id | provider | selected_model    | is_active_provider
1       | gemini   | gemini-2.5-flash  | true   ← Yang dipakai LLM fallback
1       | openai   | gpt-4o-mini       | false
```

Pengaturan via web: route `/settings/ai` — hanya satu provider bisa aktif sekaligus.

---

## 7. Konfigurasi Environment

```env
# Telegram Bot
TELEGRAM_BOT_TOKEN=your_telegram_bot_token

# Python NLP Service (Circuit Breaker 1)
PYTHON_AI_URL=http://localhost:3987
PYTHON_AI_KEY=kunci-rahasia-v4

# System Wallets (nama harus sama dengan yang ada di DB)
SYSTEM_WALLET_MERCHANT="Merchant System"
SYSTEM_WALLET_EXTERNAL="External System"
SYSTEM_WALLET_DEBT="System Hutang"
SYSTEM_WALLET_RECEIVABLE="System Piutang"
```

---

## 8. Tabel Database yang Terlibat

| Tabel | Diisi oleh | Keterangan |
|---|---|---|
| `users` | Registrasi | Kolom `telegram_id` wajib diisi untuk link Telegram |
| `wallets` | User via web | Dompet + `keyword` untuk AI matching |
| `categories` | User via web | Kategori + `keyword` untuk AI matching |
| `transaction_logs` | `ProcessTransactionAction` | Rekaman transaksi final |
| `ai_parse_logs` | `AiParseLogService` | Log setiap request AI |
| `ai_usage_logs` | `AIManager` | Log token LLM eksternal saja |
| `user_ai_memories` | `UserMemoryService` | Memori RAG per user |
| `user_ai_credentials` | Settings web | API key per user per provider |
| `user_ai_preferences` | Settings web | Provider & model aktif per user |

---

## 9. Status Implementasi

| Komponen | Status | File |
|---|---|---|
| Telegram Webhook | ✅ OK | `TelegramWebhookController.php` |
| Route API | ✅ OK | `routes/api.php` |
| Chat Orchestrator | ✅ Fixed | `ChatTransactionOrchestrator.php` |
| Python NLP Provider | ✅ OK | `PythonNLPProvider.php` |
| Gemini Provider | ✅ OK | `GeminiProvider.php` (v1, retry 2x) |
| OpenAI Provider | ✅ OK | `OpenAIProvider.php` |
| DeepSeek Provider | ✅ OK | `DeepSeekProvider.php` |
| Prompt Builder | ✅ OK | `TransactionPromptBuilder.php` (RAG inject) |
| Transaction Resolver | ✅ OK | `TransactionResolver.php` |
| Confidence Engine | ✅ Fixed | `ConfidenceScoringEngine.php` |
| ConfidenceScoreContext DTO | ✅ Dibuat | `app/DTO/ConfidenceScoreContext.php` |
| Memory Service (RAG) | ✅ OK | `UserMemoryService.php` |
| Memory Decay | ✅ OK | `MemoryDecayEngine.php` |
| Parse Log Service | ✅ OK | `AiParseLogService.php` |
| Process Transaction | ✅ OK | `ProcessTransactionAction.php` |
| Config `services.php` | ✅ OK | `python_ai`, `telegram` sudah ada |
| Config `bendaharaku.php` | ✅ OK | weights, threshold, memory config |

---

## 10. Bug yang Sudah Diperbaiki (16 Juli 2026)

| ID | File | Bug | Fix |
|---|---|---|---|
| BUG-01 | `ConfidenceScoringEngine.php` | Signature tidak cocok — dipanggil 1 arg, butuh 4 | Ubah terima `ConfidenceScoreContext` DTO |
| BUG-02 | `ChatTransactionOrchestrator.php` | `$threshold` undefined sebelum try-catch | Deklarasikan `$threshold` sebelum try-catch |
| BUG-03 | `ChatTransactionOrchestrator.php` | Scoring engine jalan setelah DRAFT fallback (IDs null) | Scoring hanya di dalam try block jika resolve sukses |
| BUG-04 | `ChatTransactionOrchestrator.php` | `$resolved` null bisa lolos ke DB action | Guard null + early return untuk DRAFT null-ID |
| BUG-05 | `ResolvedTransaction.php` | IDs non-nullable — crash saat buat DRAFT fallback | Ubah `categoryId`, `sourceWalletId`, `destinationWalletId` ke `?int` |
| WARN-01 | `ChatTransactionOrchestrator.php` | `getTopRelevantMemories()` dipanggil tanpa `$text` | Tambah argumen `$text` |
| WARN-03 | `GeminiProvider.php` | `activeMemories` tidak diteruskan ke prompt builder | Teruskan ke `TransactionPromptBuilder::build()` |
| MISSING | `app/DTO/ConfidenceScoreContext.php` | File DTO tidak ada → fatal class not found | Buat file baru |
