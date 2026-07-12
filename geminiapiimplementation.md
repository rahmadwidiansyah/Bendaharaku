# Spesifikasi Implementasi: Gemini API & Telegram Bot — Bendaharaku

> **Tanggal Analisis:** 12 Juli 2026
> **Proyek:** Bendaharaku — Aplikasi Keuangan Personal (Laravel + Inertia/Vue)
> **Scope:** AI Gemini API untuk parsing transaksi keuangan + Integrasi Telegram Bot

---

## 1. Gambaran Arsitektur Sistem

Sistem AI di Bendaharaku menggunakan pola **dual-circuit-breaker** dengan dua lapis pengolahan:

```
Input Teks (Telegram / Web)
        |
        v
+-----------------------------------+
|    ChatTransactionOrchestrator    |  (Entry point)
+-----------------------------------+
        |
        +---> [Circuit 1] PythonNLPProvider (Lokal/Gratis)
        |           Skor >= 0.85? -> Selesai OK
        |           Gagal/ragu? -> fallback
        |
        +---> [Circuit 2] LLM Eksternal (Gemini / OpenAI / DeepSeek)
                    Dipilih per-user berdasarkan UserAiPreference
```

### Komponen Utama

| Komponen | File | Peran |
|---|---|---|
| `AIManager` | `app/Services/AI/AIManager.php` | Orkestrator AI utama, mengelola circuit breaker |
| `GeminiProvider` | `app/Services/AI/Providers/GeminiProvider.php` | Implementasi HTTP call ke Gemini API |
| `OpenAIProvider` | `app/Services/AI/Providers/OpenAIProvider.php` | Implementasi HTTP call ke OpenAI |
| `DeepSeekProvider` | `app/Services/AI/Providers/DeepSeekProvider.php` | Implementasi HTTP call ke DeepSeek |
| `AiProviderFactory` | `app/Services/AI/AiProviderFactory.php` | Factory pattern untuk inisiasi provider |
| `AiCredentialManager` | `app/Services/AI/AiCredentialManager.php` | Manajemen API key per-user |
| `AiPreferenceManager` | `app/Services/AI/AiPreferenceManager.php` | Manajemen pilihan model & provider aktif per-user |
| `TransactionPromptBuilder` | `app/Services/AI/Prompt/TransactionPromptBuilder.php` | Membangun payload prompt JSON ke LLM |
| `TransactionResolver` | `app/Services/AI/TransactionResolver.php` | Translasi output AI ke ID entitas database |
| `ConfidenceScoringEngine` | `app/Services/AI/Scoring/ConfidenceScoringEngine.php` | Kalkulasi skor kepercayaan final (matematis) |
| `UserMemoryService` | `app/Services/AI/Memory/UserMemoryService.php` | Sistem memori personal pengguna (RAG sederhana) |
| `ChatTransactionOrchestrator` | `app/Services/Chat/ChatTransactionOrchestrator.php` | Alur lengkap dari input teks ke simpan DB |
| `TelegramWebhookController` | `app/Http/Controllers/TelegramWebhookController.php` | Entry point webhook Telegram |

---

## 2. Detail Implementasi: Gemini API

### 2.1 Konfigurasi Endpoint

```php
// GeminiProvider.php — Line 27
$url = "https://generativelanguage.googleapis.com/v1beta/models/{$request->model}:generateContent";

// Auth via query param (bukan Bearer token)
$url . '?key=' . $request->apiKey
```

- **API Version:** `v1beta`
- **Auth Method:** API Key via query parameter `?key=`
- **Default Model:** `gemini-2.5-flash` (didefinisikan di `AiProvider::Gemini->defaultModel()`)
- **HTTP Timeout:** 15 detik
- **Response Format:** Dipaksa JSON via `responseMimeType: 'application/json'`

### 2.2 Struktur Request Payload

```json
{
  "contents": [
    {
      "parts": [
        {
          "text": "<JSON prompt dari TransactionPromptBuilder>"
        }
      ]
    }
  ],
  "generationConfig": {
    "responseMimeType": "application/json"
  }
}
```

### 2.3 Struktur Prompt (TransactionPromptBuilder)

Prompt dikirimkan sebagai **satu JSON string** yang berisi:

```json
{
  "instruction": "Extract financial transaction. Return strictly JSON schema: {amount, transactionType, category, sourceWallet, destinationWallet, subject, notes, isCleared, confidence}",
  "text": "<input user>",
  "available_wallets": ["BCA", "Dana", "Cash"],
  "available_categories": ["Makanan", "Transport"],
  "historical_patterns_guidance": "Use these mappings as strong hints...",
  "user_historical_patterns": [
    { "keyword": "budi", "target_category": "Hutang" }
  ]
}
```

> **Catatan:** Memori personal (RAG) hanya dikirim ke LLM eksternal, **tidak** ke Python NLP lokal.

### 2.4 Skema JSON yang Diharapkan dari Gemini

```json
{
  "amount": 15000,
  "transactionType": "expense | income | transfer | debt | receivable",
  "category": "Makanan",
  "sourceWallet": "BCA",
  "destinationWallet": null,
  "subject": "User/Merchant",
  "notes": "Teks asli atau catatan tambahan",
  "isCleared": true,
  "confidence": 0.92
}
```

### 2.5 Mapping Token Usage

| Field Gemini Response | Dipetakan ke |
|---|---|
| `usageMetadata.promptTokenCount` | `usage['prompt']` |
| `usageMetadata.candidatesTokenCount` | `usage['completion']` |
| `usageMetadata.totalTokenCount` | `usage['total']` |

Kemudian disimpan ke tabel `ai_usage_logs` via `AiUsageLog::create(...)`.

### 2.6 Error Handling Gemini

| HTTP Status | Pesan yang Dikembalikan |
|---|---|
| `429` | "Token API Gemini habis/limit. Cek kuota Google AI Studio." |
| `408, 503, 504` | "Server Gemini sedang sibuk/Timeout." |
| Lainnya | `"Gemini API Error ({status}): {body}"` |
| `ConnectionException` | "Waktu request ke API Gemini habis (Timeout)." |

---

## 3. Detail Implementasi: Telegram Bot

### 3.1 Konfigurasi

```env
# .env
TELEGRAM_BOT_TOKEN=your_telegram_bot_token
```

```php
// config/services.php
'telegram' => [
    'token' => env('TELEGRAM_BOT_TOKEN'),
],
```

### 3.2 Alur Webhook

```
POST /webhook-telegram
    |
    v
TelegramWebhookController@handle()
    |
    +---> Ambil chat_id & text dari update Telegram
    +---> Lookup User berdasarkan telegram_id
    |
    +---> [Perintah Sistem]
    |       /saldo  -> handleSaldoCommand()
    |       /web    -> Reply link web dashboard
    |       /start, /help, hai, halo, dll -> handleHelpCommand()
    |
    +---> [Input Transaksi Bebas]
            sendMessage(chatId, "Siap, lagi dicerna AI...")
            ChatTransactionOrchestrator::process($user, $text, 'TEL')
            +---> Reply sukses / gagal ke Telegram
```

### 3.3 Perintah Bot yang Tersedia

| Perintah | Fungsi |
|---|---|
| `/saldo` | Menampilkan saldo semua dompet Asset/Liquid dalam format tabel |
| `/web` | Mengirim link shortcut ke web dashboard |
| `/start`, `/help`, `hai`, `halo`, `hello`, `p`, `ping`, `tes`, `test` | Menampilkan panduan penggunaan |
| *Teks bebas* | Diproses sebagai input transaksi ke AI pipeline |

### 3.4 Format Reply Sukses

```
OK *TRANSAKSI BERHASIL*
_Pengeluaran_

Ref ID    : TRX-XXXX
Nominal   : Rp 15.000
Kategori  : Makanan
Sumber    : BCA
Tujuan    : Merchant System
Pihak     : Nama User

Pesan Asli:
_beli nasi goreng 15k bca_
```

### 3.5 Format Reply Draft (Kepercayaan Rendah)

```
MASUK DRAFT (Butuh Cek Web)
```

---

## 4. Alur Data Lengkap (End-to-End)

```
[Telegram Chat]
    | teks: "beli nasi goreng 15k bca"
    v
TelegramWebhookController::handle()
    | lookup user by telegram_id
    v
ChatTransactionOrchestrator::process()
    |
    +-- 1. Ambil wallets & categories user dari DB
    +-- 2. getTopRelevantMemories(user_id, text) -> inject memori aktif (cache 5 menit)
    +-- 3. AIManager::parseTransaction()
    |       +-- [CB1] PythonNLPProvider -> jika confidence >= 0.85 -> return
    |       +-- [CB2] GeminiProvider -> HTTP POST ke Google API
    |               +-- TransactionPromptBuilder::build() -> JSON prompt + memori
    |
    +-- 4. Validasi dasar (amount, category, subject untuk hutang)
    +-- 5. TransactionResolver::resolve() -> translate nama ke ID DB
    |       +-- searchCategory() -> match by nama atau keyword
    |       +-- searchWalletToken() -> match by nama atau keyword
    |
    +-- 6. ConfidenceScoringEngine::calculateFinalScore()
    |       +-- ai_base (40%)        -> skor confidence dari LLM
    |       +-- memory_match (25%)   -> apakah keyword ada di memori user?
    |       +-- category_match (15%) -> apakah kategori valid di DB user?
    |       +-- wallet_match (20%)   -> apakah dompet valid sesuai tipe transaksi?
    |
    +-- 7. AiParseLogService::createLog()    -> simpan ke ai_parse_logs
    +-- 8. ProcessTransactionAction::create() -> simpan ke transaction_logs
    +-- 9. event(TransactionPosted)           -> async: update memori & dataset
```

---

## 5. Sistem Confidence Scoring

### Formula Bobot

```
FinalScore = (ai_base x 0.40) + (memory_match x 0.25) + (category_match x 0.15) + (wallet_match x 0.20)
```

| Komponen | Bobot | Sumber Data |
|---|---|---|
| `ai_base` | 40% | `confidence` field dari response JSON Gemini |
| `memory_match` | 25% | `MemoryMatchService` — keyword regex match di user_ai_memories |
| `category_match` | 15% | `CategoryMatchService` — validasi kategori ada di DB user |
| `wallet_match` | 20% | `WalletMatchService` — validasi dompet sesuai TransactionIntent |

**Threshold auto-clear:** `0.85` (dikonfigurasi di `config/bendaharaku.php`)
- Skor >= 0.85 -> `is_cleared = true` (transaksi langsung valid)
- Skor < 0.85  -> `is_cleared = false` (masuk DRAFT, butuh konfirmasi manual di web)

---

## 6. Sistem Memori Personal (RAG Sederhana)

- **Tabel:** `user_ai_memories`
- **Trigger:** Event `TransactionPosted` -> Listener -> `UserMemoryService::upsertMemory()`
- **Pola yang Diingat:** Subject/merchant dari transaksi yang berhasil diverifikasi
- **Bobot Awal:** 0.0, bertambah +1.0 per penggunaan, max 5.0
- **Decay:** Bobot berkurang seiring waktu (rate: 0.05/hari), prune jika < 0.20
- **Cache:** Memory di-cache 5 menit per user (`ai-mem-{userId}`)
- **Batas Inject ke Prompt:** Max 5 memori paling relevan (agar token LLM efisien)
- **Matching:** Regex `\bkeyword\b` dari text input (exact word boundary)

---

## 7. Manajemen Kredensial API per User

Setiap user menyimpan API key-nya **sendiri** di tabel `user_ai_credentials`.

- **API Key:** Disimpan per provider (`gemini`, `openai`, `deepseek`)
- **Status Validasi:** Field `is_valid` — jika bermasalah bisa di-mark invalid
- **Model Preferensi:** Disimpan di `user_ai_preferences`, `is_active_provider` hanya boleh satu yang aktif
- **Pengaturan:** Bisa diubah via web UI di route `/settings/ai`

---

## 8. Temuan & Isu yang Perlu Ditindaklanjuti

### [BUG-01] KRITIS — Signature Mismatch: ConfidenceScoringEngine

**File:** `app/Services/AI/Scoring/ConfidenceScoringEngine.php` (Line 22)
**File:** `app/Services/Chat/ChatTransactionOrchestrator.php` (Line 107–115)

```php
// Di ConfidenceScoringEngine.php — definisi method:
public function calculateFinalScore(User $user, string $inputText, AIParseResult $parseResult, array $activeMemories): float

// Di ChatTransactionOrchestrator.php — cara dipanggil:
$scoreContext = new ConfidenceScoreContext(...);
$finalConfidence = $this->scoringEngine->calculateFinalScore($scoreContext);
// ERROR: 1 argumen, padahal butuh 4!
```

**Perbaikan — Opsi A:** Ubah signature engine agar menerima DTO:
```php
public function calculateFinalScore(ConfidenceScoreContext $context): float
```

**Perbaikan — Opsi B:** Ubah cara panggil di Orchestrator:
```php
$finalConfidence = $this->scoringEngine->calculateFinalScore(
    $user, $text, $aiResult, $activeMemories
);
```

---

### [BUG-02] KRITIS — Undefined Variable di ChatTransactionOrchestrator

**File:** `app/Services/Chat/ChatTransactionOrchestrator.php` (Line 89 vs Line 115)

```php
// Di dalam catch block (Line 89) — $finalConfidence BELUM dideklarasi!
$resolved->isCleared = ($finalConfidence >= $threshold);

// Baru dideklarasi di Line 115 (setelah catch block)
$finalConfidence = $this->scoringEngine->calculateFinalScore($scoreContext);
```

**Perbaikan:** Deklarasikan default di atas try-catch:
```php
$finalConfidence = 0.0; // Default sebelum scoring
try {
    // ...
} catch (CategoryNotFoundException | WalletNotFoundException $e) {
    $finalConfidence = 0.0; // Eksplisit reset di catch
    // ...
}
```

---

### [WARN-01] PENTING — Missing Argument di getTopRelevantMemories

**File:** `app/Services/Chat/ChatTransactionOrchestrator.php` (Line 47)

```php
// Method butuh 2 argumen:
public function getTopRelevantMemories(int $userId, string $inputText): array

// Tapi dipanggil dengan 1 argumen:
$activeMemories = $this->memoryService->getTopRelevantMemories($user->id);
// KURANG: $text
```

**Perbaikan:**
```php
$activeMemories = $this->memoryService->getTopRelevantMemories($user->id, $text);
```

---

### [WARN-02] PENTING — PythonNLPProvider.php Tidak Ditemukan

**File:** `app/Services/AI/AIManager.php` (Line 12)
```php
use App\Services\AI\Providers\PythonNLPProvider;
```

File `PythonNLPProvider.php` **tidak ada** di `app/Services/AI/Providers/`. Ini akan menyebabkan class not found error saat app di-boot.

**Perbaikan:** Buat file `PythonNLPProvider.php` dengan implementasi HTTP call ke `PYTHON_AI_URL`.

---

### [WARN-03] PENTING — Memory Tidak Terinjeksi ke Prompt Gemini

**File:** `app/Services/AI/Providers/GeminiProvider.php` (Line 28)

```php
// activeMemories ADA di request, tapi TIDAK diteruskan ke builder:
$prompt = $this->promptBuilder->build($request->text, $request->wallets, $request->categories);

// Seharusnya:
$prompt = $this->promptBuilder->build(
    $request->text,
    $request->wallets,
    $request->categories,
    $request->activeMemories  // INI yang hilang!
);
```

Akibatnya fitur RAG (memori personal) **tidak berfungsi** untuk Gemini. Cek juga DeepSeekProvider dan OpenAIProvider untuk masalah yang sama.

---

### [WARN-04] — Variabel .env python_ai Tidak Terhubung ke Config

**File:** `.env` (Line 76–77)
```
PYTHON_AI_URL=your_python_ai_url
PYTHON_AI_KEY=your_key
```

Tidak ada entri di `config/services.php`. Risiko kegagalan saat `php artisan config:cache`.

**Perbaikan:** Tambahkan ke `config/services.php`:
```php
'python_ai' => [
    'url' => env('PYTHON_AI_URL'),
    'key' => env('PYTHON_AI_KEY'),
],
```

---

### [WARN-05] — AiAnalyticsController.php Kosong di Lokasi Salah

File kosong ditemukan di:
- `app/Services/AI/AiAnalyticsController.php` (0 bytes)

File sebenarnya ada di:
- `app/Http/Controllers/AiAnalyticsController.php` (1604 bytes)

**Perbaikan:** Hapus file kosong di `Services/AI/` untuk menghindari autoload confusion.

---

### [WARN-06] — Route Webhook Telegram Tidak Terlihat

Di `routes/web.php` dan `routes/api.php` tidak ditemukan route untuk Telegram webhook secara eksplisit. Pastikan:
1. Route sudah terdaftar di tempat yang tepat
2. Route dikecualikan dari CSRF middleware (jika di `web.php`)

**Rekomendasi:** Gunakan `routes/api.php` (sudah bebas CSRF) dengan path eksplisit:
```php
Route::post('/telegram/webhook/{secret}', [TelegramWebhookController::class, 'handle']);
```

---

## 9. Rekomendasi Improvement

### [REC-01] Gunakan system_instruction Gemini

Gemini mendukung `system_instruction` terpisah dari `contents`. Memindahkan instruksi tetap ke sana menghemat token:

```json
{
  "system_instruction": {
    "parts": [{ "text": "You are a financial transaction extractor. Always return valid JSON..." }]
  },
  "contents": [
    { "parts": [{ "text": "<data transaksi user>" }] }
  ]
}
```

### [REC-02] Aktifkan thinkingConfig untuk Kasus Kompleks (Gemini 2.5)

```json
"generationConfig": {
  "responseMimeType": "application/json",
  "thinkingConfig": { "thinkingBudget": 1024 }
}
```

### [REC-03] Tambahkan Retry Logic

```php
$response = Http::timeout(15)
    ->retry(2, 1000) // 2x retry, delay 1 detik
    ->post($url . '?key=' . $request->apiKey, [...]);
```

### [REC-04] Tambahkan GEMINI_API_KEY Sistem di .env

Untuk mode demo/trial tanpa user perlu input API key sendiri:
```env
GEMINI_API_KEY=your_system_gemini_key
GEMINI_DEFAULT_MODEL=gemini-2.5-flash
```

### [REC-05] Secret Token Validation untuk Webhook Telegram

```php
// Gunakan secret token saat register webhook:
// POST https://api.telegram.org/bot{TOKEN}/setWebhook
// Body: { url: "...", secret_token: "rahasia123" }

// Validasi di middleware:
if ($request->header('X-Telegram-Bot-Api-Secret-Token') !== config('services.telegram.secret')) {
    abort(403);
}
```

---

## 10. Ringkasan Status Implementasi

| Fitur | Status | Catatan |
|---|---|---|
| GeminiProvider HTTP Call | OK Implemented | API v1beta, JSON mode |
| Confidence Scoring | WARN Ada Bug Signature | Perlu perbaikan [BUG-01] |
| Telegram Webhook Handler | OK Implemented | Perintah /saldo, /web, /help |
| Chat Orchestrator | WARN Ada Bug | [BUG-02], [WARN-01] |
| Memory / RAG Injection ke Gemini | WARN Tidak Terinjeksi | [WARN-03] — memory tidak dikirim |
| PythonNLPProvider | FAIL File Tidak Ditemukan | [WARN-02] |
| AI Usage Logging | OK Implemented | Tabel ai_usage_logs |
| AI Parse Logging | OK Implemented | Tabel ai_parse_logs |
| Feedback Loop | OK Implemented | AiFeedbackService + weighted divergence |
| Dataset Export | OK Implemented | DatasetExportService |
| Memory Decay | OK Implemented | MemoryDecayEngine |
| Per-user API Key | OK Implemented | UserAiCredential model |
| Multi-provider Support | OK Implemented | Gemini, OpenAI, DeepSeek |

---

## 11. Action Items (Prioritas)

| Prioritas | File | Aksi |
|---|---|---|
| KRITIS | `app/Services/AI/Providers/PythonNLPProvider.php` | Buat file — implementasi HTTP ke Python service |
| KRITIS | `app/Services/AI/Scoring/ConfidenceScoringEngine.php` | Perbaiki signature method [BUG-01] |
| KRITIS | `app/Services/Chat/ChatTransactionOrchestrator.php` | Perbaiki [BUG-02] undefined variable & [WARN-01] missing arg |
| KRITIS | `app/Services/AI/Providers/GeminiProvider.php` | Teruskan `activeMemories` ke prompt builder [WARN-03] |
| PENTING | `config/services.php` | Tambah config `python_ai` [WARN-04] |
| PENTING | `app/Services/AI/AiAnalyticsController.php` | Hapus file kosong [WARN-05] |
| PENTING | `routes/api.php` | Konfirmasi & amankan route Telegram webhook [WARN-06] |
