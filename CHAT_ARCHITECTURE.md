# Chat Architecture — Bendaharaku V4

Platform-agnostic chat engine yang memisahkan business logic dari presentasi,
sehingga Telegram, Web Chat, WhatsApp, Discord, dan platform lain
menggunakan AI engine yang sama tanpa mengubah domain layer.

---

## Daftar Isi

1. [Prinsip Desain](#1-prinsip-desain)
2. [Layer Architecture](#2-layer-architecture)
3. [Struktur Folder](#3-struktur-folder)
4. [Request Flow](#4-request-flow)
5. [Response Flow](#5-response-flow)
6. [DTO Reference](#6-dto-reference)
7. [Component System](#7-component-system)
8. [Error System](#8-error-system)
9. [Localization](#9-localization)
10. [AI Flow](#10-ai-flow)
11. [Sequence Diagram](#11-sequence-diagram)
12. [Menambah Platform Baru](#12-menambah-platform-baru)
13. [Menambah AI Provider Baru](#13-menambah-ai-provider-baru)
14. [Implementation Phases](#14-implementation-phases)
15. [Best Practices](#15-best-practices)

---

## 1. Prinsip Desain

### Platform Agnostic
Business logic tidak mengetahui Telegram, WhatsApp, Discord, atau Web.
`ChatTransactionOrchestrator`, `AIManager`, `TransactionResolver`, dan
`ProcessTransactionAction` tidak mengandung satu pun referensi ke platform.

### Provider Agnostic
AI engine mendukung Python NLP, Gemini, OpenAI, DeepSeek, dan provider
lain di masa depan. Semua provider menghasilkan output yang sama (`AIParseResult`).

### Language Agnostic
Seluruh teks berasal dari translation files (`lang/id/chat.php`, `lang/en/chat.php`).
Formatter tidak meng-hardcode satu kalimat pun.

### Strangler Fig Pattern
Arsitektur baru dibangun di atas arsitektur lama secara bertahap.
`ChatTransactionOrchestrator` tidak diubah pada Tahap 1.
`ChatApplicationService` membungkus Orchestrator dan mengkonversi outputnya.

---

## 2. Layer Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     PLATFORM LAYER                          │
│                                                             │
│   TelegramAdapter    WebChatAdapter    WhatsAppAdapter      │
│   (parse Update)     (parse HTTP)      (parse Webhook)      │
│                                                             │
│   Tahu tentang: Telegram API, HTTP, WebSocket               │
│   Tidak tahu:   AI, transaksi, business rule                │
└──────────────────────────┬──────────────────────────────────┘
                           │  ChatRequest
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                   APPLICATION LAYER                         │
│                                                             │
│                  ChatApplicationService                     │
│                  handleMessage(ChatRequest)                 │
│                                                             │
│   Tahu tentang: ChatRequest, ChatResponse, ErrorDetail      │
│   Tidak tahu:   Telegram, WhatsApp, Web, Markdown           │
└──────────────────────────┬──────────────────────────────────┘
                           │  delegate
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                    DOMAIN LAYER                             │
│                                                             │
│   ChatTransactionOrchestrator                               │
│     ├── AIManager (Python NLP / Gemini / OpenAI)            │
│     ├── TransactionResolver (nama → DB ID)                  │
│     └── ProcessTransactionAction (DB + saldo)               │
│                                                             │
│   Tidak tahu tentang: platform, formatter, locale           │
└──────────────────────────┬──────────────────────────────────┘
                           │  ChatResponse (structured, no markdown)
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                  PRESENTATION LAYER                         │
│                                                             │
│   TelegramFormatter   WebFormatter   WhatsAppFormatter      │
│   (Telegram Markdown) (JSON array)   (Plain text)           │
│                                                             │
│   Tahu tentang: ChatResponse, trans(), platform syntax      │
│   Tidak tahu:   AI, database, business rule                 │
└─────────────────────────────────────────────────────────────┘
```

---

## 3. Struktur Folder

```
app/
├── Chat/                                  # Namespace chat engine
│   ├── Adapters/
│   │   ├── TelegramAdapter.php            # Parse Telegram Update → ChatRequest
│   │   ├── WebChatAdapter.php             # (Tahap 3) Parse HTTP POST → ChatRequest
│   │   └── WhatsAppAdapter.php            # (Tahap 4)
│   │
│   ├── Contracts/
│   │   └── ChatFormatterInterface.php     # format(ChatResponse, ChatContext): string|array
│   │
│   ├── Components/                        # Blok konten platform-agnostic
│   │   ├── ChatComponentInterface.php     # type(): string, toArray(): array
│   │   ├── TextComponent.php              # Teks dengan translation key
│   │   ├── DividerComponent.php           # Pemisah visual
│   │   ├── TransactionCardComponent.php   # Detail satu transaksi
│   │   ├── SummaryCardComponent.php       # Ringkasan batch multi-transaction
│   │   ├── ErrorComponent.php             # Error satu item (inline)
│   │   ├── WarningComponent.php           # Peringatan non-fatal
│   │   ├── SuggestionComponent.php        # Saran tindakan
│   │   └── QuickReplyComponent.php        # Tombol pilihan cepat
│   │
│   ├── DTOs/
│   │   ├── ChatContext.php                # Metadata platform (traceId, locale, dll)
│   │   ├── ChatRequest.php                # Input: rawMessage + user + context
│   │   └── ChatResponse.php              # Output: components[] + errors[] + metadata
│   │
│   ├── Errors/
│   │   └── ErrorDetail.php               # Error terstruktur (code, messageKey, params)
│   │
│   ├── Formatters/
│   │   ├── TelegramFormatter.php          # ChatResponse → Telegram Markdown
│   │   ├── WebFormatter.php               # (Tahap 3) ChatResponse → JSON array
│   │   └── WhatsAppFormatter.php          # (Tahap 4) ChatResponse → plain text
│   │
│   └── ChatApplicationService.php        # Entry point: handleMessage(ChatRequest)
│
├── Services/Chat/
│   ├── ChatTransactionOrchestrator.php   # Domain: AI + Resolve + DB (tidak diubah)
│   └── MultiTransactionRouter.php        # Routing single vs multi
│
├── Services/AI/                           # AI providers (tidak diubah)
│   ├── AIManager.php
│   ├── TransactionResolver.php
│   └── ...
│
├── Enums/
│   ├── ChatPlatform.php                  # telegram, whatsapp, web, discord, ...
│   ├── ChatIntent.php                    # single_transaction, multi_transaction, command, ...
│   └── ChatErrorSeverity.php             # info, warning, error, critical
│
└── Http/Controllers/
    └── TelegramWebhookController.php     # Thin: terima HTTP, delegate ke TelegramAdapter

lang/
├── id/chat.php                           # Teks Bahasa Indonesia
└── en/chat.php                           # Teks English
```

---

## 4. Request Flow

```
1. User kirim pesan ke Telegram
   │
2. Telegram POST ke /api/telegram/webhook
   │
3. TelegramWebhookController::handle()
   │  (hanya terima request, delegate, return JSON)
   │
4. TelegramAdapter::handle(array $update)
   │  - Ekstrak chatId, text, messageId dari Telegram payload
   │  - Resolve User dari telegram_id
   │  - Resolve locale: user->locale ?? platform_language_code ?? 'id'
   │  - Cek perintah (/saldo, /help, /web) → handle langsung
   │  - Bangun ChatContext::make(platform: Telegram, conversationId: chatId, ...)
   │  - Bangun ChatRequest::make(rawMessage: text, user: user, context: context)
   │
5. ChatApplicationService::handleMessage(ChatRequest)
   │  - Log dengan traceId
   │  - Panggil ChatTransactionOrchestrator::process()
   │  - Konversi array result → ChatResponse
   │
6. ChatTransactionOrchestrator::process()
   │  - Route: single vs multi
   │  - Panggil AI (Python NLP / LLM)
   │  - Resolve entitas ke DB ID
   │  - Simpan transaksi
   │  - Return array (tidak berubah)
   │
7. ChatApplicationService konversi result → ChatResponse
   │  - Single success → ChatResponse::singleSuccess([TransactionCardComponent, ...])
   │  - Multi result  → ChatResponse::multiResult([SummaryCardComponent, ...])
   │  - Failure       → ChatResponse::failure([ErrorDetail, ...])
   │
8. TelegramAdapter format & kirim
   │  - TelegramFormatter::format(ChatResponse, ChatContext)
   │  - Semua teks via trans($key, $params, $locale)
   │  - sendMessage(chatId, formatted_string)
```

---

## 5. Response Flow

```
ChatResponse (platform-agnostic)
├── intent: ChatIntent::SingleTransaction
├── success: true
├── components:
│   ├── [0] TransactionCardComponent
│   │       transaction: TransactionLog
│   │       showDetails: true
│   ├── [1] DividerComponent
│   └── [2] TextComponent
│           translationKey: 'chat.transaction.label_ai_provider'
│           params: ['provider' => 'Gemini', 'confidence' => '94%']
└── metadata:
    ├── trace_id: '01JX...'
    ├── provider: 'gemini'
    ├── model: 'gemini-2.0-flash'
    ├── confidence: 0.94
    └── latency_ms: 1243
        │
        ▼
TelegramFormatter::format()
        │
        ├── renderTransactionCard() → "✅ *TRANSAKSI BERHASIL*\n_Pengeluaran 🔴_\n..."
        ├── renderDivider()         → "─────────────────────"
        └── renderText()            → trans('chat.transaction.label_ai_provider', ...)
        │
        ▼
String Telegram Markdown siap kirim
```

---

## 6. DTO Reference

### ChatContext
| Field          | Type           | Deskripsi |
|----------------|----------------|-----------|
| `platform`     | `ChatPlatform` | Enum platform asal |
| `conversationId` | `string`     | chat_id / session_id / phone |
| `locale`       | `string`       | BCP-47: 'id', 'en', 'ja' |
| `timezone`     | `string`       | IANA: 'Asia/Jakarta' |
| `traceId`      | `string`       | ULID unik per pesan |
| `messageId`    | `?string`      | ID pesan di platform |
| `replyTo`      | `?string`      | ID pesan yang di-reply |
| `sessionId`    | `?string`      | Session Web Chat |
| `metadata`     | `array`        | Metadata platform-specific |

### ChatRequest
| Field          | Type           | Deskripsi |
|----------------|----------------|-----------|
| `rawMessage`   | `string`       | Teks mentah dari user |
| `user`         | `User`         | Eloquent model |
| `context`      | `ChatContext`  | Metadata platform |
| `timestamp`    | `Carbon`       | Waktu pesan diterima |
| `attachments`  | `array`        | Future: gambar, voice note |

### ChatResponse
| Field          | Type                    | Deskripsi |
|----------------|-------------------------|-----------|
| `success`      | `bool`                  | Apakah ada hasil positif |
| `intent`       | `ChatIntent`            | Jenis respons |
| `components`   | `ChatComponentInterface[]` | Ordered list untuk render |
| `errors`       | `ErrorDetail[]`         | Error terstruktur |
| `metadata`     | `array`                 | provider, confidence, latency, traceId |

---

## 7. Component System

Setiap komponen merepresentasikan satu blok konten yang bisa dirender
berbeda di setiap platform. Komponen yang tidak didukung platform
cukup dilewati oleh Formatter.

| Komponen              | Deskripsi | Telegram | Web | WhatsApp | Discord |
|-----------------------|-----------|----------|-----|----------|---------|
| `TextComponent`       | Teks dengan translation key | ✅ | ✅ | ✅ | ✅ |
| `DividerComponent`    | Pemisah visual | ✅ (garis) | ✅ (hr) | ❌ (skip) | ✅ (newline) |
| `TransactionCardComponent` | Detail transaksi | ✅ | ✅ (card) | ✅ | ✅ (embed) |
| `SummaryCardComponent` | Ringkasan batch | ✅ | ✅ | ✅ | ✅ |
| `ErrorComponent`      | Error satu item | ✅ | ✅ | ✅ | ✅ |
| `WarningComponent`    | Peringatan | ✅ | ✅ | ✅ | ✅ |
| `SuggestionComponent` | Saran tindakan | ✅ | ✅ (button) | ✅ | ✅ |
| `QuickReplyComponent` | Tombol pilihan | ✅ (keyboard) | ✅ (chips) | ⚠️ (list) | ✅ (button) |

---

## 8. Error System

Error tidak pernah berupa string. Selalu `ErrorDetail` dengan:

```php
new ErrorDetail(
    code:        'WALLET_NOT_FOUND',           // kode mesin
    messageKey:  'chat.wallet.not_found',       // translation key
    params:      ['name' => 'spay'],            // substitusi
    rawValue:    'spay',                        // nilai dari user
    suggestion:  'chat.suggestion.add_wallet',  // saran (opsional)
    severity:    ChatErrorSeverity::Error,
    recoverable: true,
)
```

Named constructors tersedia untuk kasus umum:
```php
ErrorDetail::walletNotFound('spay')
ErrorDetail::categoryNotFound('gaming')
ErrorDetail::invalidAmount()
ErrorDetail::sameWallet()
ErrorDetail::aiNotConfigured()
ErrorDetail::aiRateLimit('Gemini')
ErrorDetail::aiTimeout('OpenAI')
ErrorDetail::systemError()
```

---

## 9. Localization

### Priority Order
```
1. users.locale (DB)          → Pilihan user di Settings
2. Platform language_code     → Telegram: from.language_code
                                Web: Accept-Language header
3. config('app.locale')       → Default: 'id'
```

### Cara Formatter Menggunakan Teks
```php
// Formatter SELALU seperti ini:
trans('chat.transaction.success', [], $locale)
trans('chat.multi.partial', ['success' => 3, 'failed' => 1], $locale)

// Formatter TIDAK BOLEH seperti ini:
"Berhasil mencatat 3 transaksi"  // hardcode
```

### Struktur Translation Key
```
chat.general.*         pesan umum (processing, error, unauthorized)
chat.transaction.*     single transaction (label, status, type)
chat.multi.*           multi-transaction (header, partial, failed)
chat.validation.*      validasi input (missing_amount, same_wallet)
chat.wallet.*          error dompet
chat.category.*        error kategori
chat.ai.*              error AI provider
chat.error.*           error sistem
chat.command.*         perintah bot (/saldo, /help, /web)
chat.suggestion.*      saran tindakan
```

### Menambah Bahasa Baru
Buat `lang/ja/chat.php` dengan struktur identik ke `lang/id/chat.php`.
Tidak ada kode yang perlu diubah.

---

## 10. AI Flow

```
ChatApplicationService
        │
        ▼
ChatTransactionOrchestrator::process()
        │
        ├── MultiTransactionRouter::isMultiTransaction()
        │         │
        │         ├── YES → processMulti()
        │         │         ├── AiPreferenceManager → LLM preference
        │         │         ├── provider->parseMultiTransaction()
        │         │         ├── foreach item: resolver->resolve() + transactionAction->create()
        │         │         └── return MultiTransactionResult
        │         │
        │         └── NO  → processSingle()
        │                   ├── AIManager::parseTransaction()
        │                   │     ├── Circuit 1: PythonNLPProvider
        │                   │     └── Circuit 2: Fallback LLM (Gemini/OpenAI/DeepSeek)
        │                   ├── TransactionResolver::resolve()
        │                   ├── ConfidenceScoringEngine::calculateFinalScore()
        │                   └── ProcessTransactionAction::create()
        │
        ▼
ChatApplicationService konversi → ChatResponse
```

---

## 11. Sequence Diagram

### Single Transaction Sukses

```
User → Telegram → WebhookController → TelegramAdapter
                                            │
                                     buildChatContext()
                                     buildChatRequest()
                                            │
                                     ChatApplicationService
                                            │
                                     Orchestrator.process()
                                            │
                                       AIManager
                                            │
                                       Python NLP ──→ (confidence > 0.85) ──→ return
                                            │         (confidence < 0.85) ──→ fallback
                                       LLM Provider ──→ ParsedTransaction
                                            │
                                       TransactionResolver ──→ ResolvedTransaction
                                            │
                                       ProcessTransactionAction ──→ TransactionLog
                                            │
                                     ChatApplicationService
                                     convertSingleSuccess()
                                     → ChatResponse{
                                         components: [TransactionCard, Divider, Text]
                                       }
                                            │
                                     TelegramFormatter.format()
                                     trans() per komponen
                                     → "✅ *TRANSAKSI BERHASIL*\n..."
                                            │
                                     TelegramAdapter.sendMessage()
                                            │
                                     Telegram → User
```

---

## 12. Menambah Platform Baru

Contoh: menambah **Discord**.

### Langkah 1 — Tambah enum value

```php
// app/Enums/ChatPlatform.php
case Discord = 'discord';

public function sourcePrefix(): string {
    return match ($this) {
        ...
        self::Discord => 'DSC',
    };
}
```

### Langkah 2 — Buat Formatter

```php
// app/Chat/Formatters/DiscordFormatter.php
class DiscordFormatter implements ChatFormatterInterface
{
    public function supports(string $platform): bool
    {
        return $platform === 'discord';
    }

    public function format(ChatResponse $response, ChatContext $context): string
    {
        $locale = $context->locale;
        $lines  = [];

        foreach ($response->components as $component) {
            $lines[] = match ($component->type()) {
                'text'             => trans($component->translationKey, $component->params, $locale),
                'transaction_card' => $this->renderEmbed($component, $locale),
                'summary_card'     => $this->renderSummary($component, $locale),
                'error'            => '> ❌ ' . trans($component->messageKey, $component->params, $locale),
                'divider'          => '',
                default            => null,
            };
        }

        return implode("\n", array_filter($lines));
    }
}
```

### Langkah 3 — Buat Adapter

```php
// app/Chat/Adapters/DiscordAdapter.php
class DiscordAdapter
{
    public function __construct(
        private ChatApplicationService $chatService,
        private DiscordFormatter $formatter,
    ) {}

    public function handle(array $interaction): array
    {
        $userId  = $interaction['member']['user']['id'];
        $text    = $interaction['data']['options'][0]['value'] ?? '';

        $user    = User::where('discord_id', $userId)->firstOrFail();
        $locale  = ChatContext::resolveLocale($user->locale, $interaction['locale'] ?? null);

        $context = ChatContext::make(
            platform:       ChatPlatform::Discord,
            conversationId: $interaction['channel_id'],
            locale:         $locale,
        );

        $request  = ChatRequest::make($text, $user, $context);
        $response = $this->chatService->handleMessage($request);
        $message  = $this->formatter->format($response, $context);

        // POST ke Discord webhook/interaction endpoint
        Http::post($this->discordWebhookUrl(), ['content' => $message]);

        return ['status' => 'ok'];
    }
}
```

### Langkah 4 — Buat Controller & Route

```php
// routes/api.php
Route::post('/discord/interactions', [DiscordInteractionController::class, 'handle']);
```

**Tidak ada satu baris pun di domain layer yang perlu diubah.**

---

## 13. Menambah AI Provider Baru

Contoh: menambah **Claude (Anthropic)**.

### Langkah 1 — Tambah enum value

```php
// app/Enums/AiProvider.php
case Claude = 'claude';

public function defaultModel(): string {
    return match ($this) {
        ...
        self::Claude => 'claude-3-5-sonnet-20241022',
    };
}
```

### Langkah 2 — Buat Provider

```php
// app/Services/AI/Providers/ClaudeProvider.php
class ClaudeProvider implements AIProviderInterface
{
    public function parseTransaction(AiProviderRequest $request): AIParseResult
    {
        $prompt = $this->promptBuilder->build(...);
        $res    = Http::withHeaders(['x-api-key' => $request->apiKey])
                      ->post('https://api.anthropic.com/v1/messages', [...]);
        // parse response → return AIParseResult
    }

    public function parseMultiTransaction(AiProviderRequest $request): AIParseResultMulti
    {
        // ...
    }
}
```

### Langkah 3 — Daftarkan di Factory

```php
// app/Services/AI/AiProviderFactory.php
public function make(AiProvider $provider): AIProviderInterface
{
    return match ($provider) {
        ...
        AiProvider::Claude => app(ClaudeProvider::class),
    };
}
```

**Tidak ada perubahan di Orchestrator, ChatApplicationService, Adapter, atau Formatter.**

---

## 14. Implementation Phases

### Tahap 1 — Foundation (SELESAI)
- [x] Migration: `users.locale`, `users.timezone`
- [x] Enum: `ChatPlatform`, `ChatIntent`, `ChatErrorSeverity`
- [x] DTO: `ChatContext`, `ChatRequest`, `ChatResponse`
- [x] Component System: 8 komponen
- [x] `ErrorDetail` dengan named constructors
- [x] `ChatFormatterInterface`
- [x] `TelegramFormatter`
- [x] `ChatApplicationService` (wrapper Orchestrator)
- [x] `TelegramAdapter`
- [x] `TelegramWebhookController` refactor (44 baris)
- [x] `lang/id/chat.php` + `lang/en/chat.php`

### Tahap 2 — Migrate Error Strings
- [ ] Pindahkan semua Telegram Markdown dari `ChatTransactionOrchestrator` ke `ErrorDetail`
- [ ] `processSingle()` return `ErrorDetail` bukan string
- [ ] Hapus `detectErrorFromMessage()` dari `ChatApplicationService`
- [ ] Semua catch block di Orchestrator melempar exception bertipe, bukan string

### Tahap 3 — Web Chat
- [ ] `WebChatAdapter` (parse HTTP POST)
- [ ] `WebFormatter` (return JSON array, bukan string)
- [ ] `WebChatController` + route `POST /api/chat`
- [ ] Vue component: bubble chat, typing indicator, transaction card

### Tahap 4 — Extend Platforms
- [ ] `WhatsAppAdapter` + `WhatsAppFormatter`
- [ ] `DiscordAdapter` + `DiscordFormatter`
- [ ] Integration tests per platform

---

## 15. Best Practices

### Jangan di Business Logic
```php
// SALAH — string Telegram di domain layer
return ['message' => "⚙️ *AI Belum Dikonfigurasi*\n\n..."];

// BENAR — lempar exception, biarkan ChatApplicationService tangkap
throw new AiConfigurationException("...");
// ChatApplicationService konversi ke ErrorDetail::aiNotConfigured()
```

### Jangan di Formatter
```php
// SALAH — hardcode teks di Formatter
return "Berhasil mencatat 3 transaksi";

// BENAR — ambil dari translation file
return trans('chat.multi.all_success', ['count' => 3], $locale);
```

### Gunakan ErrorDetail, Bukan String
```php
// SALAH
['error' => 'Dompet spay tidak ditemukan']

// BENAR
ErrorDetail::walletNotFound('spay')
// Formatter yang memanggil trans('chat.wallet.not_found', ['name' => 'spay'])
```

### Trace ID di Semua Log
```php
// Setiap log harus menyertakan traceId dari ChatContext
Log::warning('Wallet not found', [
    'trace_id' => $context->traceId,
    'user_id'  => $user->id,
    'wallet'   => $walletName,
]);
```

### Platform Tidak Masuk Domain
```php
// SALAH — ChatApplicationService tahu tentang Telegram
if ($context->platform === ChatPlatform::Telegram) {
    return "Format ini khusus Telegram";
}

// BENAR — buat component, biarkan Formatter yang handle perbedaan platform
$components[] = new TextComponent('chat.some.key');
// TelegramFormatter render dengan Markdown
// WebFormatter render dengan HTML
```
