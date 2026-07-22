# AI Architecture Overview

## Introduction

Bendaharaku V4 menggunakan **AI microservice berbasis Python FastAPI** untuk memproses natural language input dari user (via Telegram Bot atau Web Chat) dan mengubahnya menjadi transaksi finansial terstruktur.

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                         User Input Layer                            │
│  "Beli mi instan 15k dari Indomaret pakai BCA"                      │
└────────────────────────────────┬────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      Laravel Backend (PHP)                          │
│                                                                     │
│  ┌──────────────────┐    ┌─────────────────┐                       │
│  │ Chat Controller  │───▶│  NLP Service    │                       │
│  └──────────────────┘    │  (HTTP Client)  │                       │
│                          └────────┬─────────┘                       │
│                                   │                                 │
└───────────────────────────────────┼─────────────────────────────────┘
                                    │ HTTP POST /parse
                                    │ + user context (wallets, categories)
                                    ▼
┌─────────────────────────────────────────────────────────────────────┐
│                   Python AI Microservice (FastAPI)                  │
│                                                                     │
│  ┌────────────────────────────────────────────────────────────────┐│
│  │                      NLP Parser                                ││
│  │  - Intent Detection (Expense, Income, Transfer, Debt, etc)    ││
│  │  - Entity Extraction (amount, wallet, category, subject)      ││
│  │  - Fuzzy Matching (wallet/category keywords)                  ││
│  │  - Date/Time Recognition                                       ││
│  └────────────────────────────────────────────────────────────────┘│
│                                   │                                 │
│                                   │ Returns: ParsedTransaction      │
│                                   ▼                                 │
└───────────────────────────────────┼─────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      Laravel Backend (PHP)                          │
│                                                                     │
│  ┌──────────────────┐    ┌─────────────────┐    ┌────────────────┐│
│  │ Transaction      │───▶│ Local Rule      │───▶│  Transaction   ││
│  │ Resolver         │    │ Engine          │    │  Action        ││
│  │                  │    │ (Fallback)      │    │  (DB Write)    ││
│  └──────────────────┘    └─────────────────┘    └────────────────┘│
│          │                                                          │
│          ▼                                                          │
│  ┌──────────────────────────────────────────────────────────────┐ │
│  │             Database Transaction Created                      │ │
│  │  - Wallet balance updated                                     │ │
│  │  - Transaction log recorded                                   │ │
│  └──────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────┘
```

## Core Components

### 1. NLP Service (Laravel → Python)
**File:** `app/Services/AI/NLPService.php`

**Purpose:** HTTP client yang mengirim text + user context ke Python AI microservice

**Request:**
```php
$nlpService->parseTransaction($text, $userId);
```

**Payload:**
```json
{
  "text": "Beli mi instan 15k dari Indomaret pakai BCA",
  "user_id": 123,
  "wallets": [
    {"id": 1, "name": "BCA", "keyword": "bca"},
    {"id": 2, "name": "Gopay", "keyword": "gopay"}
  ],
  "categories": [
    {"id": 5, "name": "Belanja", "keyword": "indomaret, alfamart"},
    {"id": 6, "name": "Transportasi", "keyword": "grab, gojek"}
  ]
}
```

**Response (from Python):**
```json
{
  "success": true,
  "transaction_type": "EXPENSE",
  "amount": 15000,
  "source_wallet": "BCA",
  "category": "Belanja",
  "subject": "mi instan",
  "merchant": "Indomaret",
  "confidence": 0.85,
  "date": "2026-07-22"
}
```

---

### 2. Transaction Resolver
**File:** `app/Services/AI/TransactionResolver.php`

**Purpose:** Mentranslasi output AI (text-based) menjadi entity ID database

**Input:** `ParsedTransaction` DTO dari AI
**Output:** `ResolvedTransaction` dengan wallet_id, category_id, amount

**Key Logic:**
```php
// Resolve wallet by fuzzy matching
$wallet = $this->searchWallet($parsed->sourceWallet, $user->wallets());

// Resolve category by fuzzy matching
$category = $this->searchCategory($parsed->category, $user->categories());

// Determine source & destination based on transaction type
[$sourceWalletId, $destinationWalletId] = match ($parsed->transactionType) {
    TransactionIntent::Expense => [
        $wallet->id,
        $this->getSystemWalletId($user, 'Merchant System')
    ],
    TransactionIntent::Income => [
        $this->getSystemWalletId($user, 'External System'),
        $wallet->id
    ],
    TransactionIntent::Transfer => [
        $this->searchWallet($parsed->sourceWallet, $user->wallets())->id,
        $this->searchWallet($parsed->destinationWallet, $user->wallets())->id
    ],
    // ... debt, receivable
};
```

**Wallet Resolution:**
- Exact name match → confidence 1.0
- Keyword fuzzy match → confidence 0.9
- AI fallback → confidence 0.7
- No match → throw `WalletNotFoundException`

**Category Resolution:**
- Keyword match → confidence 0.8
- AI prediction → confidence from AI model
- Fallback default category → confidence 0.5

---

### 3. Local Rule Engine (Fallback)
**File:** `app/Services/AI/LocalRuleEngine.php`

**Purpose:** Rule-based parser jika Python AI tidak tersedia (offline mode, rate limiting, error)

**Features:**
- Regex-based intent detection
- Keyword matching untuk wallet/category
- Currency amount extraction
- Date/time parsing (hari ini, kemarin, besok, tanggal eksplisit)

**Example Rules:**
```php
// Expense keywords
if (preg_match('/\b(beli|bayar|buat|belanja|makan|jajan)\b/i', $text)) {
    $intent = TransactionIntent::Expense;
}

// Income keywords
if (preg_match('/\b(terima|dapat|gajian|bonus|pendapatan)\b/i', $text)) {
    $intent = TransactionIntent::Income;
}

// Transfer keywords
if (preg_match('/\b(pindah|transfer|trf|kirim)\b/i', $text)) {
    $intent = TransactionIntent::Transfer;
}

// Amount extraction
preg_match('/(\d+(?:\.\d+)?)\s*k\b/i', $text, $matches);
$amount = (float)$matches[1] * 1000; // "15k" → 15000
```

**Priority:** Local Rule Engine hanya digunakan jika Python AI fail atau disabled.

---

### 4. User Context Builder
**File:** `app/Services/AI/UserContextBuilder.php`

**Purpose:** Build context payload untuk dikirim ke AI

**Output:**
```php
[
    'user_id' => 123,
    'wallets' => [
        ['id' => 1, 'name' => 'BCA', 'keyword' => 'bca', 'balance' => 100000],
        ['id' => 2, 'name' => 'Gopay', 'keyword' => 'gopay', 'balance' => 50000],
    ],
    'categories' => [
        ['id' => 5, 'name' => 'Belanja', 'keyword' => 'indomaret, alfamart, ...', 'type' => 'Expense'],
        ['id' => 6, 'name' => 'Transportasi', 'keyword' => 'grab, gojek', 'type' => 'Expense'],
    ],
    'recent_transactions' => [
        ['merchant' => 'Indomaret', 'category' => 'Belanja', 'amount' => 20000],
        // ... last 10 transactions
    ],
]
```

**Usage:** Sent to Python AI for context-aware parsing.

---

### 5. AI Manager
**File:** `app/Services/AI/AIManager.php`

**Purpose:** Central coordinator untuk AI operations

**Features:**
- Orchestrate NLPService + TransactionResolver
- Handle fallback ke LocalRuleEngine
- Logging & error handling
- Rate limiting & retry logic

---

## Transaction Flow

### Example: "Beli mi instan 15k dari Indomaret pakai BCA"

#### Step 1: Chat Input
```php
// WebChatController or TelegramAdapter
$text = "Beli mi instan 15k dari Indomaret pakai BCA";
$userId = $request->user()->id;
```

#### Step 2: Build Context
```php
$context = $userContextBuilder->build($user);
// Returns: wallets, categories, recent transactions
```

#### Step 3: NLP Parsing (Python AI)
```php
$parsed = $nlpService->parseTransaction($text, $user, $context);
```

**AI Response:**
```php
ParsedTransaction {
    transactionType: TransactionIntent::Expense,
    amount: 15000.0,
    sourceWallet: "BCA",
    destinationWallet: null,
    category: "Belanja",
    subject: "mi instan",
    merchant: "Indomaret",
    date: "2026-07-22",
    confidence: 0.85,
}
```

#### Step 4: Resolve Entities
```php
$resolved = $transactionResolver->resolve($user, $parsed);
```

**Resolved Output:**
```php
ResolvedTransaction {
    categoryId: 5,
    sourceWalletId: 1,  // BCA
    destinationWalletId: 37, // Merchant System
    amount: 15000.0,
    subject: "mi instan - Indomaret",
    date: "2026-07-22",
}
```

#### Step 5: Create Transaction
```php
$transaction = $processTransactionAction->create(
    data: [
        'transaction_type' => 'EXPENSE',
        'category_id' => $resolved->categoryId,
        'source_wallet_id' => $resolved->sourceWalletId,
        'destination_wallet_id' => $resolved->destinationWalletId,
        'amount' => $resolved->amount,
        'subject' => $resolved->subject,
        'date' => $resolved->date,
        'is_cleared' => true,
    ],
    userId: $user->id,
    sourcePrefix: 'AI',
);
```

**Result:** Transaction created, wallet balance updated, user receives confirmation.

---

## Python AI Microservice

### Repository
[script_pencatat_keuangan](https://github.com/rahmadwidiansyah/script_pencatat_keuangan.git)

### Tech Stack
- **FastAPI** — Web framework
- **Pydantic** — Data validation
- **TheFuzz** — Fuzzy string matching
- **RapidFuzz** — Fast fuzzy matching

### Key Endpoints

#### POST /parse
**Purpose:** Parse natural language transaction

**Request:**
```json
{
  "text": "Beli mi instan 15k dari Indomaret pakai BCA",
  "user_id": 123,
  "wallets": [...],
  "categories": [...]
}
```

**Response:**
```json
{
  "success": true,
  "transaction_type": "EXPENSE",
  "amount": 15000,
  "source_wallet": "BCA",
  "category": "Belanja",
  "subject": "mi instan",
  "merchant": "Indomaret",
  "confidence": 0.85
}
```

#### POST /predict_category
**Purpose:** AI category prediction (fallback untuk resolver)

**Request:**
```json
{
  "text": "Belanja di Indomaret",
  "categories": [
    {"id": 5, "name": "Belanja", "keyword": "indomaret"},
    {"id": 6, "name": "Transportasi", "keyword": "grab"}
  ]
}
```

**Response:**
```json
{
  "category_id": 5,
  "category_name": "Belanja",
  "confidence": 0.9
}
```

---

## Fallback Strategy

### Priority Chain
```
1. Python AI Service (primary)
   ↓ (if failed)
2. Local Rule Engine (regex-based)
   ↓ (if still failed)
3. Default category + manual wallet selection
```

### When AI Fails
```php
try {
    $parsed = $nlpService->parseTransaction($text, $user);
} catch (AIServiceException $e) {
    Log::warning('AI service unavailable, using local rules');
    $parsed = $localRuleEngine->parse($text, $user);
}
```

### Draft Mode (Unresolved)
Jika wallet/category tidak bisa di-resolve otomatis:
```php
$transaction = ProcessTransactionAction::create([
    'is_cleared' => false, // Draft mode
    'source_wallet_id' => $systemWallet->id, // Temporary placeholder
    'notes' => '[DRAFT AI: wallet belum dipilih]',
]);
```

User kemudian menerima notifikasi untuk memilih wallet/category secara manual.

---

## Configuration

### AI Service URL
```env
PYTHON_AI_URL=http://localhost:8001
PYTHON_AI_KEY=secret_key_here
```

### Config File
```php
// config/services.php
'ai' => [
    'url' => env('PYTHON_AI_URL'),
    'key' => env('PYTHON_AI_KEY'),
    'timeout' => 10, // seconds
    'retry' => 3,
],
```

---

## Error Handling

### AI Service Timeout
```php
try {
    $parsed = $nlpService->parseTransaction($text, $user, timeout: 10);
} catch (TimeoutException $e) {
    // Fallback to local rules
    $parsed = $localRuleEngine->parse($text, $user);
}
```

### Invalid Response
```php
if (!$parsed->isValid()) {
    throw new AIParseException('AI returned invalid transaction data');
}
```

### Missing Entities
```php
try {
    $resolved = $transactionResolver->resolve($user, $parsed);
} catch (WalletNotFoundException $e) {
    // Return draft with wallet selection required
    return ['status' => 'draft', 'message' => 'Pilih dompet secara manual'];
}
```

---

## Performance Metrics

### Typical Response Times
- Python AI parse: ~200-500ms
- Local Rule Engine: ~10-30ms
- Database entity resolution: ~50-100ms

**Total latency:** ~300-650ms from user input to transaction created

### Optimization Tips
- Cache user context (wallets, categories) for 5 minutes
- Use connection pooling for HTTP client
- Batch multiple transactions (multi-transaction text)

---

## Testing

### Mock AI Service
```php
// In tests
Http::fake([
    '*/parse' => Http::response([
        'success' => true,
        'transaction_type' => 'EXPENSE',
        'amount' => 15000,
        'source_wallet' => 'BCA',
    ]),
]);

$parsed = $nlpService->parseTransaction('beli 15k', $user);
```

### Test Local Rule Engine
```php
$parsed = $localRuleEngine->parse('beli mi instan 15k bca');

$this->assertEquals(TransactionIntent::Expense, $parsed->transactionType);
$this->assertEquals(15000, $parsed->amount);
$this->assertEquals('BCA', $parsed->sourceWallet);
```

---

## Next Steps

- [Transaction Resolver Deep Dive](./02-transaction-resolver.md)
- [Local Rule Engine](./03-local-rule-engine.md)
- [User Context Builder](./04-user-context-builder.md)
- [AI Provider Integration](./05-ai-provider-integration.md)
