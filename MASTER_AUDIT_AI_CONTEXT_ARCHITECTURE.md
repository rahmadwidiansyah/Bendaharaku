# MASTER AUDIT — AI Context Architecture (Phase 3)

**Date**: 2026-07-26
**Revision**: 2 (2026-07-27)
**Status**: AUDIT ONLY — no implementation
**Baseline**: Phase 2 Architecture Refactor committed at `c52637b`

### Revision Notes

**Rev 2**: Separated `RuleContext` from `AIContext` to prevent deterministic engine (LocalRuleEngine) from depending on LLM-specific context. Added `ContextSnapshot` as shared snapshot: data fetched once, consumed by two specialized builders — `RuleContextBuilder` (for LocalRuleEngine) and `AIContextBuilder` (for LLM adapters). This prevents coupling between the regex-based parser and the evolving LLM context schema.

**Rev 3**: Renamed `ContextLayer` → `ContextSnapshot` to better reflect its role as a read-once data snapshot shared across builders, not a full architectural layer.

---

## C1 — Current AI Pipeline Map

### 1.1 End-to-End Flow

```
User Message (Web/Telegram)
    │
    ▼
WebChatController / TelegramWebhookController
    │
    ▼
Adapter (WebAdapter / TelegramAdapter)
    │
    ▼
ChatApplicationService::handleMessage()
    │
    ├── CommandRouter → command (non-AI)
    │
    └── ChatTransactionOrchestrator::process()
            │
            ├── MultiTransactionRouter → multi or single
            │
            ├── Single: AIManager::parseTransaction()
            │   │
            │   ├── 1. LocalRuleEngine::parse()
            │   │       ├── DB: categories→get()
            │   │       ├── DB: wallets→get()
            │   │       ├── extractAmount(), scoreSystemCategory(), matchCategory(), matchWallets()
            │   │       └── returns AIParseResult or null
            │   │
            │   ├── 2. PythonNLPProvider::parseTransaction()
            │   │       └── HTTP POST /analyze ← wallets + categories
            │   │
            │   └── 3. LLM Provider::parseTransaction()
            │           ├── PreferenceManager → getActivePreference()
            │           ├── CredentialManager → getCredential()
            │           ├── ProviderFactory → make(providerEnum)
            │           ├── TransactionPromptBuilder::build()
            │           │       ├── ContextBuilder::build()
            │           │       │       └── keyword detection, filters wallets/categories
            │           │       └── require resources/prompts/transaction-single.php
            │           │       └── json_encode($payload)
            │           └── HTTP POST to LLM API
            │
            └── Multi: MultiTransactionProcessor::process()
                    ├── MultiTransactionRouter → isMultiTransaction()
                    ├── PreferenceManager + CredentialManager + ProviderFactory
                    ├── MultiTransactionPromptBuilder::build()
                    │       └── require resources/prompts/transaction-multi.php
                    │       └── json_encode($payload)
                    └── LLM Provider::parseMultiTransaction()
```

### 1.2 Data Collection Points (Who gets what)

| Data | Collector | Layer | DB Queries |
|------|-----------|-------|------------|
| Wallets (non-System) | `ChatTransactionOrchestrator` | Service | `$user->wallets()->where('group_type','!=','System')->get()` |
| Categories | `ChatTransactionOrchestrator` | Service | `$user->categories()->get()` |
| Active Memories | `UserMemoryService::getTopRelevantMemories()` | Service | `UserAiMemory::where('user_id')->get()` + word boundary match |
| Wallets (all) | `LocalRuleEngine::parse()` | AI | `Wallet::where('user_id')->get()` |
| Categories (all) | `LocalRuleEngine::parse()` | AI | `Category::where('user_id')->with('type')->get()` |
| Wallets (for scoring) | `WalletMatchService::isMatch()` | AI | `$user->wallets()->get(['name','keyword'])` |
| Categories (for scoring) | `CategoryMatchService::isMatch()` | AI | `$user->categories()->get(['category_name','keyword'])` |
| Wallets (for scoring balance) | `ConfidenceScoringEngine::calculateWalletScore()` | AI | `$user->wallets()->get(['name','keyword','balance','group_type'])` |
| Wallets + locale + merchants | `UserContextBuilder::build()` | Prompt | `$user->wallets()->get()` + `$user->categories()->get()` + `TransactionLog::where('user_id')->limit(50)->pluck('notes')` |
| Wallet keywords + aliases | `UserContextBuilder::buildKeywordMap()` | Prompt | (derived from wallets/categories) |

### 1.3 Key Finding: Same Data Collected 5+ Times

For a single transaction request, the same wallets and categories are queried independently:

1. **Orchestrator** → `$user->wallets()->get()` (non-System) + `$user->categories()->get()`
2. **LocalRuleEngine** → `Wallet::where('user_id')->get()` + `Category::where('user_id')->get()`
3. **UserContextBuilder** → `$user->wallets()->get()` + `$user->categories()->get()` (called from... nowhere currently — dead path?)
4. **ConfidenceScoringEngine** → `$user->wallets()->get(['name','keyword','balance','group_type'])`
5. **CategoryMatchService** → `$user->categories()->get(['category_name','keyword'])`
6. **WalletMatchService** → `$user->wallets()->get(['name','keyword'])`
7. **TransactionResolver** → `$user->wallets()->get()` + `$user->categories()->get()`

**Impact**: ~5-7 duplicate DB queries per transaction. Caching exists only for memories (300s TTL).

---

## C2 — Prompt Builder Audit

### 2.1 TransactionPromptBuilder

```
TransactionPromptBuilder
    │
    ├── Constructs ContextBuilder internally (new ContextBuilder)
    │
    └── build($text, $wallets, $categories, $activeMemories)
            │
            ├── ContextBuilder::build()
            │       ├── Detects: isTransfer, isDebtOrReceivable, isTransaction
            │       ├── Filters wallets: strips balances (name + keyword only)
            │       ├── Filters categories: name + keyword (skips if transfer)
            │       ├── Filters memories: builds historical_patterns (skips if transfer)
            │       └── Returns array with available_wallets, available_categories, user_historical_patterns
            │
            ├── Unset reserved keys (instruction, text)
            ├── require resources/prompts/transaction-single.php
            ├── array_merge(['instruction' => ..., 'text' => $text] + $context)
            └── json_encode()
```

**Responsibilities (too many)**:
- Text intent detection (isTransfer, isDebtOrReceivable, isTransaction)
- Wallet data transformation (strip balances)
- Category data transformation (name+keyword)
- Memory data transformation (→ historical_patterns)
- Instruction loading (require prompt file)
- JSON serialization

**Does NOT query DB** — takes pre-collected arrays. Good.

### 2.2 MultiTransactionPromptBuilder

```
MultiTransactionPromptBuilder
    │
    └── build($text, $wallets, $categories, $activeMemories, $context = [])
            │
            ├── Transforms: wallets → [{name, balance}] (INCLUDES balances — different from single!)
            ├── Transforms: categories → flat string array (different format from single!)
            ├── Uses $context if provided: wallet_keywords, category_keywords, recent_merchants
            │       (but $context is NEVER passed by any caller — dead parameter!)
            ├── Transforms: memories → historical_patterns
            ├── require resources/prompts/transaction-multi.php
            ├── Builds payload: instruction + text + available_wallets + available_categories
            │       + wallet_keyword_aliases + category_keyword_aliases + historical_patterns
            │       + response_format + known_merchants (optional)
            └── json_encode()
```

**Problems**:
1. **`$context` parameter is dead code** — never passed by any caller (GeminiProvider, OpenAIProvider, DeepSeekProvider all call `$this->multiPromptBuilder->build($request->text, $request->wallets, $request->categories, $request->activeMemories)` without the 5th argument)
2. **wallet_keyword_aliases and category_keyword_aliases are always empty arrays** — because `$context` is never passed
3. **known_merchants is never sent** — same reason
4. **Wallet format differs from single** — multi shows balances, single hides them → inconsistency

### 2.3 ContextBuilder

```
ContextBuilder::build($text, $wallets, $categories, $activeMemories)
    │
    ├── Detects intent from text keywords (transfer? debt? transaction?)
    ├── If not a transaction → returns empty [] (out of scope)
    ├── If transfer → returns only available_wallets (no categories, no memories)
    ├── If transaction → returns wallets + categories + memories
    └── All wallet balances are STRIPPED (only name + keyword)
```

**Problems**:
1. **Keyword-based intent detection duplicates LocalRuleEngine logic** — ContextBuilder checks `str_contains(lowerText, 'transfer')` while LocalRuleEngine has a full NLP scoring engine. Two different heuristics for same question.
2. **Wallet balances stripped for single prompt** but **included for multi prompt** — inconsistent data sent to AI.
3. **Hardcoded intent keywords** — `['transfer', 'pindah', 'kirim', 'mutasi']` for transfer detection. Not configurable.

### 2.4 Prompt Instructions (resources/prompts/)

Both `transaction-single.php` and `transaction-multi.php`:
- Are plain PHP files returning a concatenated string of `PromptInstructions` constants
- The instruction is a single long string: `SCOPE_RULE + schema + WALLET_NULL_RULE + AMOUNT_RULE + AMOUNT_SHORTHAND`
- No template variables, no parameterization
- Mixed concerns: scope rule + format schema + null rules + amount rules all in one string

**Estimated token cost**: The instruction string is ~500-800 characters (~125-200 tokens) sent with every request.

### 2.5 Summary

| Builder | DB Queries? | Service Calls? | Responsibilities | Unique Logic |
|---------|-------------|----------------|------------------|--------------|
| TransactionPromptBuilder | No | No (uses ContextBuilder) | 5 (intent detect, wallet transform, category transform, memory transform, serialize) | Strips balances |
| MultiTransactionPromptBuilder | No | No | 6 (wallet transform, category transform, memory transform, keyword alias, merchant, serialize) | Includes balances, flat category names |
| ContextBuilder | No | No | 4 (intent detect, wallet filter, category filter, memory filter) | Keyword-based routing |

---

## C3 — AI Context Audit

### 3.1 Complete Inventory of Data Sent to LLM

#### Single Transaction Prompt

| Field | Source | Est. Tokens | Wajib? | Bisa diperkecil? |
|-------|--------|-------------|--------|-------------------|
| `instruction` | `resources/prompts/transaction-single.php` | ~150-200 | ✅ | Ya — lihat rekomendasi |
| `text` | User input | variable | ✅ | Tidak — user input |
| `available_wallets[]` | `ContextBuilder` (from Orchestrator) | ~20-50 per wallet | ❌ | Ya — hanya 5-10 teratas |
| `available_categories[]` | `ContextBuilder` (from Orchestrator) | ~10-30 per category | ❌ (skip for transfer) | Ya — hanya nama saja |
| `user_historical_patterns[]` | `ContextBuilder` (from memories) | ~10-20 per memory | ❌ | Ya — hanya jika match |

#### Multi Transaction Prompt

| Field | Source | Est. Tokens | Wajib? | Bisa diperkecil? |
|-------|--------|-------------|--------|-------------------|
| `instruction` | `resources/prompts/transaction-multi.php` | ~150-200 | ✅ | Ya — lihat rekomendasi |
| `text` | User input | variable | ✅ | Tidak — user input |
| `available_wallets[]` | Built directly from `$wallets` param | ~25-60 per wallet (with balance) | ❌ | Ya — strip balance, limit count |
| `available_categories[]` | Built from `$categories` param (flat names) | ~5-10 per category | ❌ | Tidak — sudah minimal |
| `wallet_keyword_aliases` | `$context['wallet_keywords']` (dead — always []) | 0 | ❌ | N/A — dead code |
| `category_keyword_aliases` | `$context['category_keywords']` (dead — always []) | 0 | ❌ | N/A — dead code |
| `historical_patterns[]` | Built from `$activeMemories` | ~10-20 per memory | ❌ | Ya — hanya jika match |
| `known_merchants[]` | `$context['recent_merchants']` (dead — always []) | 0 | ❌ | N/A — dead code |
| `response_format` | Hardcoded string in builder | ~5-10 | ✅ | Bisa di instruction saja |

### 3.2 Data That Should NOT Be Sent

1. **wallet_keyword_aliases** (multi prompt) → always empty, dead code. 0 tokens but wasting constructor time.
2. **category_keyword_aliases** (multi prompt) → always empty, dead code. Same.
3. **known_merchants** (multi prompt) → always empty, dead code. Same.
4. **wallet balances in multi prompt** → inconsistent with single prompt (which strips them). If AI doesn't need them for single, why send for multi?
5. **All wallets** → for users with 20+ wallets, sending all is wasteful. Only top 5-10 by recency/usage matter.
6. **All categories** → similar, 50+ categories with keywords is token-heavy.

### 3.3 Estimated Token Budget (Single Transaction)

| Component | Conservative | Typical | Heavy |
|-----------|-------------|---------|-------|
| Instruction | 150 | 180 | 200 |
| User text | 10 | 30 | 100 |
| 5 wallets × 40t | 200 | 200 | 200 |
| 20 categories × 20t | 200 | 400 | 600 |
| 3 memories × 15t | 30 | 45 | 60 |
| JSON overhead | 20 | 30 | 50 |
| **Total** | **~610** | **~885** | **~1,210** |

Multi transaction adds: balances (makes wallets heavier by ~15t each), plus keyword aliases (0t — dead).

### 3.4 Token Reduction Opportunities

| Opportunity | Saving | Risk |
|-------------|--------|------|
| Strip wallet balances from multi prompt | ~15t per wallet | Low — AI doesn't need balances for parsing |
| Limit wallets to top 10 | ~10-40 wallets × 40t = 400-1600t saved | Medium — AI might miss unusual wallet |
| Limit categories to top 20 | ~20-30 categories × 20t = 400-800t saved | Medium — AI might miss rare category |
| Merge instruction into single constant | ~5-10t | None |
| Remove historical_patterns from non-debt transactions | ~30-60t per request | Low — only matters for hutang/piutang |
| Use shorter keyword format | ~50-100t | Low |

**Maximum estimated saving**: 50-70% token reduction per request.

---

## C4 — Context Provider Audit

### 4.1 Current Services That Could Become Context Providers

| Service/Class | Data Provided | Currently Used By | Reusable? |
|--------------|---------------|-------------------|-----------|
| `WalletResolutionService` | Wallet data, system wallet detection | Orchestrator, Resolver, DraftConfirm | ✅ Yes |
| `CategoryResolutionService` | Category data, resolution | Resolver, LocalRuleEngine | ✅ Yes |
| `UserMemoryService::getTopRelevantMemories()` | Active memories with weights | Orchestrator (→ AIManager) | ✅ Yes |
| `UserContextBuilder::build()` | Wallets, categories, keywords, merchants, locale | Not currently called in AI flow | ⚠️ Orphaned |
| `ConfidenceScoringEngine` | Confidence calculation | Orchestrator | ⚠️ Different concern |
| `TransactionResolver` | Resolve parsed→IDs | Orchestrator | ❌ Post-AI, different stage |

### 4.2 Proposed Context Providers

| Provider | Data | Source | Format |
|----------|------|--------|--------|
| `WalletContextProvider` | Wallet names, IDs, keywords (no balances) | `WalletResolutionService` | `{name, keyword[], id}` |
| `CategoryContextProvider` | Category names, IDs, keywords | `CategoryResolutionService` | `{name, keyword[], id}` |
| `ConversationContextProvider` | Conversation history, last N messages | `ChatMessage` model | `{role, text}[]` |
| `MemoryContextProvider` | Active memories (RAG) | `UserMemoryService` | `{keyword, category, weight}` |
| `DateTimeContextProvider` | Today's date, timezone, locale | `User` + `Carbon` | `{date, tz, locale}` |
| `PreferenceContextProvider` | User preferences, currency format | `User` + `config` | `{currency, locale}` |
| `TransactionContextProvider` | Recent transactions summary | `TransactionLog` model | Limited, aggregated |

### 4.3 Key Insight: `UserContextBuilder` Already Exists but Is Orphaned

`UserContextBuilder::build()` already prepares most of what we need:
- wallets (with parsed keywords)
- categories (with parsed keywords)
- wallet_keywords (flat alias map)
- category_keywords (flat alias map)
- recent_merchants (top 20 unique)
- locale, timezone

But **nobody calls `UserContextBuilder::build()` in the current AI pipeline**. The `$context` parameter in `MultiTransactionPromptBuilder::build()` was clearly designed to receive `UserContextBuilder` output but was never wired.

---

## C5 — AIContext DTO Design

### 5.1 Proposed `AIContext` DTO

```php
readonly class AIContext
{
    public function __construct(
        // Core transaction data
        public string $userInput,           // The raw user message
        public ?string $conversationId,     // For multi-turn context

        // Financial entities (minimal)
        public array $wallets,              // [{id, name}] — NO balances
        public array $categories,           // [{id, name, type}]
        public array $keywordAliases,       // {keyword: entityName, ...} — flat map

        // RAG memories
        public array $activeMemories,       // [{keyword, category, weight}]

        // Temporal context
        public string $today,               // Y-m-d date
        public string $timezone,
        public string $locale,

        // Metadata (NOT sent in prompt)
        public array $metadata = [          // provider, model, token budget, etc.
            'provider' => '',
            'model' => '',
            'requestId' => '',
        ],
    ) {}
}
```

### 5.2 Design Principles

1. **No DB references** — no models, no collections, no query builders
2. **Primitives only** — strings, arrays, numbers. Easy to serialize.
3. **Provider-agnostic** — same context for Gemini, OpenAI, Claude, etc.
4. **Minimal** — only what the LLM needs to parse a transaction
5. **Extensible** — new providers just add metadata, not new context fields

### 5.3 What NOT to Include in AIContext

- Wallet balances → handled by backend, not AI's concern
- Full transaction history → too large, use aggregated summary if needed
- Raw database records → already transformed to primitives
- User credentials → never
- Conversation history → dedicated `ConversationContextProvider` for future use
- Confidence scores → post-LLM, different stage

---

## C6 — Token Efficiency Audit

### 6.1 Current Waste Sources

| Waste Source | Est. Tokens | % of Total | Root Cause |
|-------------|-------------|------------|------------|
| Wallet balances in multi prompt | ~15 × 10 wallets = 150 | ~15% | Inconsistency with single prompt |
| All wallets (not limited) | ~40 × 20 wallets = 800 | ~40% | No pruning strategy |
| All categories (not limited) | ~20 × 30 categories = 600 | ~30% | No pruning strategy |
| Instruction repetition | ~180 per request | ~18% | Static text sent every time |
| historical_patterns for non-debt | ~45 per request | ~5% | No conditional filtering |
| `known_merchants` (dead code) | 0 | 0% | Never passed |

### 6.2 Token Reduction Recommendations

| Recommendation | Saving | Implementation Complexity | Priority |
|---------------|--------|--------------------------|----------|
| 1. Strip wallet balances from multi prompt | ~150t | Trivial | High |
| 2. Limit wallets to top 10 by usage | ~400-800t | Low (query + sort) | High |
| 3. Limit categories to top 20 by usage | ~200-400t | Low (query + sort) | High |
| 4. Compact instruction format | ~30t | Low | Medium |
| 5. Skip historical patterns for non-debt | ~45t | Low | Medium |
| 6. Wire UserContextBuilder → fix dead $context | 0t (bugfix) | Medium | High |
| 7. Deduplicate DB queries across all collectors | 5-7 queries/request | High | Phase 3 |

### 6.3 Target Token Budget (After Optimization)

| Component | Current | Target | Saving |
|-----------|---------|--------|--------|
| Instruction | ~180 | ~120 | 33% |
| Wallet (top 10, no balance) | ~800 | ~300 | 62% |
| Categories (top 20) | ~600 | ~200 | 66% |
| Memories (conditional) | ~45 | ~20 | 55% |
| User text | ~30 | ~30 | 0% |
| Overhead | ~30 | ~20 | 33% |
| **Total** | **~1,685** | **~690** | **~59%** |

---

## C7 — Provider Independence Audit

### 7.1 Current Provider Structure

```
AIProviderInterface
    ├── parseTransaction(AiProviderRequest): AIParseResult
    └── parseMultiTransaction(AiProviderRequest): AIParseResultMulti

Implementations:
    GeminiProvider      — Google Gemini API
    OpenAIProvider      — OpenAI API
    DeepSeekProvider    — DeepSeek API
    PythonNLPProvider   — Local Python service
```

### 7.2 Gemini-Specific Code

| Feature | Gemini | OpenAI | DeepSeek | Provider-Specific? |
|---------|--------|--------|----------|-------------------|
| API URL | `generativelanguage.googleapis.com` | `api.openai.com` | `api.deepseek.com` | ✅ Provider-specific |
| Model in URL | Yes (URL path) | No (request body) | No (request body) | ✅ Gemini unique |
| Request format | `{contents: [{parts: [{text}]}]}` | `{model, messages, response_format}` | `{model, messages, response_format}` | ✅ Each unique |
| Response format | `candidates.0.content.parts.0.text` | `choices.0.message.content` | `choices.0.message.content` | ✅ Gemini unique |
| Response usage path | `usageMetadata.*TokenCount` | `usage.prompt_tokens` | `usage.prompt_tokens` | ✅ Gemini unique |
| Retry logic | Yes (2x, exponential backoff) | No | No | ✅ Gemini-only |
| Connect timeout | 10s (configurable) | default | default | ✅ Gemini-specific |
| Request timeout | 30s (configurable) | 15s/20s | 15s/20s | ✅ Different per provider |

### 7.3 Massive Duplication

All 3 LLM providers (Gemini, OpenAI, DeepSeek) share the **exact same logic structure**:

- Same error handling pattern (AiRateLimitException, AiTimeoutException, AiProviderException)
- Same `assertSuccessful()` method (identical code)
- Same `decodeJson()` method (identical code)
- Same `buildParsedTransaction()` method (identical code)
- Same `buildParsedTransactions()` method (identical code)
- Same `averageConfidence()` method (identical code)
- Same `parseMultiTransaction()` flow (identical code, different API paths)

**Total duplicated lines**: ~600 lines of effectively identical code across 3 files.

### 7.4 What Makes Providers Different (The Only Differences)

1. API endpoint URL
2. HTTP request body format (Gemini uses `contents[].parts[].text`, others use `messages[].content`)
3. Response parsing path (Gemini: `candidates.0.content.parts.0.text`, others: `choices.0.message.content`)
4. Usage token path (Gemini: `usageMetadata.*TokenCount`, others: `usage.*_tokens`)
5. Retry/Timeout config
6. Provider name string

### 7.5 Target: Clean Separation

```
AIContext
    │
    ▼
Prompt Template (provider-agnostic)
    │
    ▼
LLM Adapter (one per provider)
    │   ├── RequestFormatter  → builds provider-specific HTTP body
    │   ├── ResponseParser    → extracts text from provider response
    │   └── UsageExtractor    → extracts token counts
    │
    ▼
Standardized response (AIParseResult / AIParseResultMulti)
```

Each adapter should only implement:
1. `formatRequest(AIContext, prompt): array` — build HTTP body
2. `parseResponse(array): string` — extract text from response
3. `extractUsage(array): array` — get token counts
4. `getBaseUrl(): string` — API endpoint
5. `getHeaders(string $apiKey): array` — authentication headers

Everything else (error handling, retry, JSON decode, ParsedTransaction construction) is shared.

---

## C8 — Future Features Readiness

### 8.1 Current Readiness

| Feature | Ready? | Gap |
|---------|--------|-----|
| Multi Provider (Gemini/OpenAI/DeepSeek) | ✅ Partial | Works via `AiProviderFactory`, but each provider has duplicated code |
| AI Fallback (circuit breaker) | ✅ Yes | `AIManager`: LocalRuleEngine → Python → LLM. Works. |
| Tool Calling | ❌ No | No `tools` support in any provider. No function registry. |
| Function Calling | ❌ No | Same as tool calling. |
| MCP (Model Context Protocol) | ❌ No | No MCP support. All context is manually assembled. |
| AI Memory V2 | ⚠️ Basic | Current memory is keyword→category, no vector search, no semantic matching. |
| OCR V2 (evidence flow) | ⚠️ Separate | OCR has its own pipeline (`EvidenceResolver`, `CategoryResolver`, `WalletResolver`) — no AI context sharing. |
| Streaming Response | ❌ No | All providers return complete response synchronously. |
| Structured Output / JSON Mode | ⚠️ Partial | OpenAI and DeepSeek support `response_format: json_object`. Gemini doesn't. Manual JSON extraction via regex fallback. |

### 8.2 Architecture Gaps for Future Features

| Feature | Required Migration |
|---------|-------------------|
| **Tool Calling** | Need tool registry + tool schema builder. Prompt must include `tools` array. |
| **Function Calling** | Same as tool calling. Need `AITool` DTO + `ToolRegistry` service. |
| **MCP** | Need MCP client + tool definitions per provider. Context providers become MCP resources. |
| **Streaming** | Need `StreamableAIProviderInterface` + SSE-aware response handling. |
| **Structured Output** | Need per-provider schema format (Gemini: `responseSchema`, OpenAI: `response_format` with JSON schema). |
| **AI Memory V2** | Need vector store (pgvector/pinecone), embeddings service, semantic search. |

### 8.3 What the New Architecture Enables

With `AIContext` as the central DTO and modular `ContextProviders`:

- **Tool Calling**: AIContext is the input; tools operate on the same AIContext. No new data pipeline needed.
- **Function Calling**: LLM can request tool calls; results merged back into AIContext.
- **MCP**: Each `ContextProvider` becomes an MCP resource. `AIContext` is the aggregated state.
- **Streaming**: Streamed responses update `ParsedTransaction` fields incrementally — same pipeline, different transport.
- **New Provider**: Just implement `LLMAdapter` (5 methods). No duplicated business logic.

---

## 9. Problems Summary

### 9.1 Critical

| # | Problem | Location | Impact |
|---|---------|----------|--------|
| C‑01 | Same wallets/categories queried 5-7× per request | Multiple services | DB load, latency, N+1 across layers |
| C‑02 | No unified AIContext DTO — data flows as raw PHP arrays | Entire AI pipeline | Fragile, hard to extend, no type safety |
| C‑03 | 3 LLM providers share ~600 lines of duplicated code | Gemini/OpenAI/DeepSeekProvider | Maintenance burden, bug propagation |

### 9.2 Major

| # | Problem | Location | Impact |
|---|---------|----------|--------|
| M‑01 | `MultiTransactionPromptBuilder` $context parameter is dead code | `multiPromptBuilder.build()` | wallet_keywords, category_keywords, known_merchants never sent |
| M‑02 | Wallet balances included in multi but stripped in single | Prompt builders | Inconsistency, token waste for multi |
| M‑03 | `UserContextBuilder::build()` is orphaned — never called in AI flow | `UserContextBuilder` | Dead code, valuable context-building logic unused |
| M‑04 | `ContextBuilder` duplicates intent detection logic from `LocalRuleEngine` | `ContextBuilder` | Two heuristics for same question, potential mismatch |
| M‑05 | No wallet/category pruning strategy | Prompt builders | All wallets/categories sent regardless of relevance |

### 9.3 Minor

| # | Problem | Location | Impact |
|---|---------|----------|--------|
| m‑01 | Response format string hardcoded in MultiTransactionPromptBuilder | Builder | Should be in prompt template |
| m‑02 | GeminiProvider has retry logic but OpenAI/DeepSeek don't | Providers | Inconsistent reliability |
| m‑03 | `PythonNLPProvider::parseMultiTransaction()` returns failure stub | PythonNLP | Silently skips multi → falls through to LLM (may be intended but undocumented) |
| m‑04 | Prompt templates mixed with PHP code (`require`) | `resources/prompts/*.php` | Hard to edit for non-PHP devs. Plain PHP, not markdown/template. |
| m‑05 | `ChatTransactionOrchestrator` still has inline DB queries for conversation ID | `processSingleWebDraft()`, `buildDraftItem()` | Layer violation |
| m‑06 | `AiUsageLog::create()` called directly from `AIManager` and `MultiTransactionProcessor` | Multiple | Should be in a shared usage-tracking service |

### 9.4 Informational

| # | Observation | Location |
|---|-------------|----------|
| I‑01 | `LocalRuleEngine` is a separate AI pipeline with its own DB queries | `LocalRuleEngine` |
| I‑02 | `ConfidenceScoringEngine` re-queries wallets despite already having them in context | `Scoring` |
| I‑03 | `MemoryMatchService` duplicates keyword matching logic from `UserMemoryService::getTopRelevantMemories()` | `Scoring/Matchers` |
| I‑04 | `Config` values for prompts (amount shorthand, null rules) are PHP constants, not DB/config | `PromptInstructions` |
| I‑05 | OCR evidence flow is entirely separate — no AI context reuse | `Evidence` |

---

## 10. Proposed Architecture (Blueprint)

### 10.1 High-Level Data Flow

**Core Principle**: AIContext is for LLM communication only. Deterministic engine (LocalRuleEngine) uses a separate RuleContext. Both share the same underlying data repositories but build independent DTOs.

```
User Input
    │
    ▼
┌────────────────────────────────────────────────────────┐
│              Context Layer (shared data)                 │
│                                                          │
│  Data Repositories (one query, cached per request):      │
│  ├── WalletRepository   → wallets, keywords, balances    │
│  ├── CategoryRepository → categories, keywords, types    │
│  └── MemoryRepository   → active memories with weights   │
│                                                          │
│  These replace the 5-7 scattered DB queries per request. │
│  Data is fetched ONCE, cached for the request duration.  │
└────────────┬────────────────────────────┬───────────────┘
             │                            │
             ▼                            ▼
┌────────────────────────┐   ┌────────────────────────────┐
│  RuleContextBuilder     │   │  AIContextBuilder           │
│                         │   │                             │
│  Builds minimal context │   │  Builds LLM-specific DTO    │
│  for LocalRuleEngine:   │   │                             │
│  - categories (all)     │   │  - wallets (top 10, no bal) │
│  - wallets (all)        │   │  - categories (top 20)      │
│                         │   │  - keyword aliases (flat)   │
│  Output: RuleContext    │   │  - active memories (RAG)    │
│  DTO (simple arrays)    │   │  - today, timezone, locale  │
│                         │   │  - metadata (provider, etc) │
│                         │   │                             │
│                         │   │  Output: AIContext DTO      │
│                         │   │  (type-safe, LLM-specific)  │
└────────┬────────────────┘   └────────────┬───────────────┘
         │                                  │
         ▼                                  ▼
┌──────────────────────┐   ┌─────────────────────────────┐
│  LocalRuleEngine      │   │  PromptRenderer              │
│  (deterministic)      │   │  Renders AIContext → prompt  │
│                       │   │  string using templates.     │
│  Separated from LLM   │   │                              │
│  pipeline — tidak     │   │  Templates (.prompt.md):     │
│  terpengaruh oleh     │   │  ├── transaction-single      │
│  perubahan AIContext  │   │  └── transaction-multi       │
└──────────────────────┘   │                              │
                            │  Variables: {{wallets}},     │
                            │  {{categories}}, {{memories}}│
                            └────────────┬────────────────┘
                                          │
                                          ▼
                            ┌─────────────────────────────┐
                            │     LLM Adapter (per prov)   │
                            │                              │
                            │  Each adapter implements:    │
                            │  ├── formatRequest()         │
                            │  ├── parseResponse()         │
                            │  ├── extractUsage()          │
                            │  ├── getBaseUrl()            │
                            │  └── getHeaders()            │
                            │                              │
                            │  Shared via BaseAdapter:     │
                            │  ├── send() — HTTP + retry   │
                            │  ├── parse() — JSON extract  │
                            │  ├── buildParsedTx()         │
                            │  └── handleException()       │
                            └────────────┬────────────────┘
                                          │
                                          ▼
                            ┌─────────────────────────────┐
                            │  AIParseResult / Multi       │
                            │  Standardized output         │
                            └──────────────────────────────┘
```

**Key difference from Rev 1**: `RuleContext` and `AIContext` are separate DTOs built from the same data layer. Changes to AIContext (adding ConversationContext, ToolContext, etc.) never affect RuleEngine. Each builder only extracts what its consumer needs.

**Note**: `PromptRenderer` and `LLM Adapter` receive `AIContext` (not RuleContext) because prompt rendering is part of the LLM pipeline. RuleEngine is fully independent.

### 10.2 Directory Structure

```
app/Services/AI/
├── Context/
│   ├── ContextSnapshot.php               ← New (shared data cache, single fetch)
│   ├── AIContext.php                     ← New (LLM-specific DTO)
│   ├── AIContextBuilder.php              ← New (builds AIContext from ContextSnapshot)
│   ├── RuleContext.php                   ← New (RuleEngine-specific DTO)
│   └── RuleContextBuilder.php            ← New (builds RuleContext from ContextSnapshot)
├── Prompt/
│   ├── PromptRenderer.php                ← New (replaces builders)
│   ├── PromptInstructions.php            ← Keep (constants)
│   └── Templates/                        ← New (plain text)
│       ├── transaction-single.prompt.md
│       └── transaction-multi.prompt.md
├── Adapters/                             ← New (replaces Providers/)
│   ├── BaseAdapter.php                   ← New (shared logic)
│   ├── GeminiAdapter.php                 ← New (thin)
│   ├── OpenAIAdapter.php                 ← New (thin)
│   ├── DeepSeekAdapter.php               ← New (thin)
│   ├── PythonNLPAdapter.php              ← New (thin)
│   └── Contracts/
│       └── LLMAdapterInterface.php       ← New
├── Legacy/                               ← Old code (phase out)
│   ├── Providers/                        ← Keep during migration
│   ├── TransactionPromptBuilder.php
│   └── MultiTransactionPromptBuilder.php
├── AIManager.php                         ← Update (use AIContext)
├── TransactionResolver.php               ← Keep (different concern)
├── LocalRuleEngine.php                   ← Keep (different concern)
└── Scoring/
    └── ConfidenceScoringEngine.php        ← Keep (different concern)
```

### 10.3 Context Layer — Two Builders, One Data Source

Instead of a single AIContextBuilder for all consumers, we have:

```
Orchestrator
    │
    ├── ContextSnapshot::load(User $user, string $text)
    │       Fetches data ONCE:
    │       ├── wallets   → Wallet::where('user_id')
    │       ├── categories→ Category::where('user_id')
    │       └── memories  → UserMemoryService::getTopRelevantMemories()
    │
    ├── RuleContextBuilder::build(ContextSnapshot $data): RuleContext
    │       Returns: wallets, categories (full lists for regex matching)
    │       Consumer: LocalRuleEngine
    │
    └── AIContextBuilder::build(ContextSnapshot $data): AIContext
            Returns: wallets (top 10, no balance), categories (top 20),
                    keywordAliases, memories, today, timezone, locale
            Consumer: PromptRenderer → LLM Adapter
```

Each specialized builder:

```php
// LLM-specific — heavy filtering, token-sensitive
class AIContextBuilder
{
    public function build(ContextSnapshot $data, string $userInput): AIContext
    {
        return new AIContext(
            userInput: $userInput,
            wallets: $this->pruneWallets($data->wallets),
            categories: $this->pruneCategories($data->categories),
            keywordAliases: $this->buildAliases($data->wallets, $data->categories),
            activeMemories: $data->memories,
            today: now()->toDateString(),
            timezone: $data->user->timezone ?? 'Asia/Jakarta',
            locale: $data->user->locale ?? 'id',
        );
    }
}

// RuleEngine-specific — full data, deterministic matching
class RuleContextBuilder
{
    public function build(ContextSnapshot $data): RuleContext
    {
        return new RuleContext(
            categories: $data->categories, // all, unfiltered
            wallets: $data->wallets,       // all, with balances
        );
    }
}
```

**This keeps AIContext purely for the LLM pipeline** while eliminating the 5-7 duplicate DB queries. The `ContextSnapshot` is the shared cache — it fetches once and both builders pull from it.

### 10.4 Migration Path: No Breaking Changes

The migration from current pipeline to new architecture must be additive:

1. **Sprint A**: Create `AIContext` DTO + `AIContextBuilder` + all `ContextProviders`
   - Deploy alongside existing code (not wired in yet)
   - Add tests for AIContextBuilder output

2. **Sprint B**: Create `PromptRenderer` + LLM adapter interface
   - Prompt templates as `.md` files with `{{variables}}`
   - Wire AIContext → PromptRenderer alongside old prompt builders

3. **Sprint C**: Feature-flag new path
   - 10% users: new AIContext → PromptRenderer path
   - Compare confidence scores with old path
   - Monitor token usage reduction

4. **Sprint D**: Create `BaseAdapter` + migrate providers
   - Gemini, OpenAI, DeepSeek, PythonNLP each become thin adapters
   - Shared error handling, retry, parsing in BaseAdapter
   - Remove ~600 lines of duplication

5. **Sprint E**: 100% rollout — remove legacy path
   - Delete old prompt builders
   - Delete old provider implementations
   - Wire AIContextBuilder directly in Orchestrator

6. **Sprint F**: Cleanup
   - Remove `UserContextBuilder` (replaced by `ContextSnapshot`)
   - Remove `ContextBuilder` (replaced by `PromptRenderer`)
   - Remove `AiProviderRequest` (replaced by `AIContext`)
   - Remove `MultiTransactionPromptBuilder::$context` dead parameter
   - Fix `ConfidenceScoringEngine` to reuse `AIContext` (not re-query DB)

---

## 11. Migration Plan

### Sprint A — ContextSnapshot + RuleContext + AIContext (Smallest, Safest)

**Goal**: Create the shared Context Layer with two specialized builders. No existing behavior changes.

**Files to create**:
- `app/Services/AI/Context/ContextSnapshot.php` ← shared data cache (fetches wallets + categories + memories once)
- `app/Services/AI/Context/AIContext.php` ← LLM-specific DTO
- `app/Services/AI/Context/RuleContext.php` ← RuleEngine-specific DTO (simple wallets + categories)
- `app/Services/AI/Context/AIContextBuilder.php` ← builds AIContext from ContextSnapshot
- `app/Services/AI/Context/RuleContextBuilder.php` ← builds RuleContext from ContextSnapshot

**Files NOT created** (unlike Rev 1):
- No `ContextProviderInterface` — Data Repositories are simple static/method calls, not interface-driven (YAGNI — only two consumers)
- No separate `WalletContextProvider`, `CategoryContextProvider` — these are just methods on `ContextSnapshot`

**ContextSnapshot design** (simple, no over-engineering):

```php
readonly class ContextSnapshot
{
    // All data fetched ONCE, available for the request duration
    public function __construct(
        public User $user,
        public string $userInput,
        public Collection $wallets,
        public Collection $categories,
        public array $activeMemories,
    ) {}

    public static function load(User $user, string $text): self
    {
        return new self(
            user: $user,
            userInput: $text,
            wallets: $user->wallets()->get(),
            categories: $user->categories()->get(),
            activeMemories: app(UserMemoryService::class)->getTopRelevantMemories(
                $user->id, $text
            ),
        );
    }
}
```

**Verification**: `php -l` + unit test that both builders return correct data. No behavior change.

**Risk**: Very low — additive only, zero integration changes.

### Sprint B — Prompt Renderer + Templates

**Goal**: Separate prompt rendering from data collection.

**Files to create**:
- `app/Services/AI/Prompt/PromptRenderer.php`
- `app/Services/AI/Prompt/Templates/transaction-single.prompt.md`
- `app/Services/AI/Prompt/Templates/transaction-multi.prompt.md`

**Files to modify**: None during creation. After verification, update `TransactionPromptBuilder` and `MultiTransactionPromptBuilder` to delegate to `PromptRenderer`.

**Verification**: Prompt output comparison — old vs new must produce identical JSON for same input.

**Risk**: Low — template rendering is deterministic. Must match existing output exactly.

### Sprint C — Feature-Flagged Parallel Run (Optional)

**Goal**: Test new pipeline with real traffic before committing.

**Implementation**:
- Add feature flag in `config/bendaharaku.php`: `'ai_context_v2_enabled' => env('AI_CONTEXT_V2', false)`
- In `ChatTransactionOrchestrator::process()`:
  - If flag off → existing path (old prompt builders)
  - If flag on → AIContextBuilder → PromptRenderer → prompt string
- Pass prompt string directly to providers (skip builders)

**Monitoring**: Log token counts, confidence scores, success rates for both paths.

**Risk**: Medium — two paths must produce functionally identical prompts. Monitoring needed.

### Sprint D — LLM Adapters (De-duplicate 600 Lines)

**Goal**: Eliminate duplicated code across Gemini/OpenAI/DeepSeek.

**Files to create**:
- `app/Services/AI/Adapters/Contracts/LLMAdapterInterface.php`
- `app/Services/AI/Adapters/BaseAdapter.php`
- `app/Services/AI/Adapters/GeminiAdapter.php`
- `app/Services/AI/Adapters/OpenAIAdapter.php`
- `app/Services/AI/Adapters/DeepSeekAdapter.php`
- `app/Services/AI/Adapters/PythonNLPAdapter.php`

**Existing files to deprecate**:
- `app/Services/AI/Providers/GeminiProvider.php` (delegate to adapter, then delete)
- `app/Services/AI/Providers/OpenAIProvider.php` (same)
- `app/Services/AI/Providers/DeepSeekProvider.php` (same)
- `app/Services/AI/Contracts/AIProviderInterface.php` (replace with LLMAdapterInterface)

**Verification**: All existing tests pass with same confidence/output. Provider responses must be identical.

**Risk**: Medium — needs careful mapping of provider-specific response formats. Each adapter must handle edge cases.

### Sprint E — 100% Rollout + Legacy Removal

**Goal**: Remove all old code, making new architecture the only path.

**Files to delete**:
- `app/Services/AI/Providers/` (entire directory)
- `app/Services/AI/Prompt/TransactionPromptBuilder.php`
- `app/Services/AI/Prompt/MultiTransactionPromptBuilder.php`
- `app/Services/AI/Prompt/ContextBuilder.php`
- `app/Services/AI/Contracts/AIProviderInterface.php`
- `app/Services/AI/UserContextBuilder.php`
- `app/DTO/AiProviderRequest.php` (replaced by AIContext)

**Files to update**:
- `AIManager.php` — use AIContextBuilder directly
- `AiProviderFactory.php` — return Adapters instead of Providers
- `ChatTransactionOrchestrator.php` — build AIContext, pass to AIManager
- `MultiTransactionProcessor.php` — same

**Verification**: Full test suite passes. Token usage reduced by ~50%.

**Risk**: High — multiple files change simultaneously. Must be done after Sprint D is stable.

### Sprint F — Cleanup + Optimization

**Goal**: Remove redundant code and fix token waste.

**Tasks**:
1. Remove `UserContextBuilder` (replaced by `ContextSnapshot`)
2. Remove `ContextBuilder` (replaced by `PromptRenderer`)
3. Remove dead `$context` parameter from `MultiTransactionPromptBuilder`
4. Update `ConfidenceScoringEngine` to receive `AIContext` instead of re-querying DB
5. Remove redundant DB queries in `CategoryMatchService` and `WalletMatchService` (use `ContextSnapshot`)
6. Add wallet/category pruning to `AIContextBuilder` (top 10 wallets, top 20 categories)
7. Strip wallet balances from `AIContext` (backend's concern, not LLM's)
8. Update `LocalRuleEngine` to receive `RuleContext` instead of querying DB

**Verification**: Token usage confirmed reduced. All tests pass with same business behavior.

---

## 12. Dependency Map

```
Current (simplified):
    Orchestrator ──→ UserContextBuilder (orphaned)
    Orchestrator ──→ AIManager ──→ RuleEngine (own DB queries)
                                  ──→ PythonNLP (takes arrays)
                                  ──→ ProviderFactory → Provider
                                                        ──→ PromptBuilder
                                                             ──→ ContextBuilder

Target (simplified):
    Orchestrator
        │
        ├── ContextSnapshot::load()  ← single data fetch per request
        │       ├── WalletRepository
        │       ├── CategoryRepository
        │       └── MemoryRepository
        │
        ├── RuleContextBuilder  ──→ RuleContext  ──→ LocalRuleEngine
        │                               (full data, no LLM coupling)
        │
        └── AIContextBuilder   ──→ AIContext  ──→ PromptRenderer
                                                    └→ LLM Adapter
                                                         ├── GeminiAdapter
                                                         ├── OpenAIAdapter
                                                         ├── DeepSeekAdapter
                                                         └── PythonNLPAdapter
```

**Circular dependency risk**: None. RuleEngine and LLM pipeline are fully independent — they share data via `ContextSnapshot` but build their own DTOs. Changes to AIContext never affect RuleContext or vice versa.

---

## 13. Risk Matrix

| Change | Risk | Mitigation | Rollback |
|--------|------|------------|----------|
| Sprint A: ContextSnapshot + RuleContext + AIContext | 🟢 Low | Additive only, no integration | Delete new files |
| Sprint B: PromptRenderer | 🟢 Low | Output comparison test | Revert to old builders |
| Sprint C: Feature flag | 🟡 Medium | Monitor confidence scores | Disable flag |
| Sprint D: Adapter extraction | 🟡 Medium | Per-adapter unit tests | Keep old providers as fallback |
| Sprint E: Legacy removal | 🔴 High | Full regression suite | Git revert |
| Sprint F: Cleanup + optimization | 🟡 Medium | Token usage monitoring | Revert individual changes |

---

## 14. Conclusion

The current AI pipeline is functional but has grown organically:

1. **Data collection is fragmented** — same data queried 5-7 times per request from DB
2. **No central AIContext DTO** — data flows as raw PHP arrays with no type safety
3. **Massive provider duplication** — ~600 lines of identical code across 3 LLM providers
4. **Dead code** — `UserContextBuilder` is orphaned, `$context` parameter never passed, `wallet_keyword_aliases` never sent
5. **Token waste** — all wallets/categories sent without pruning, balances sent to multi but not single
6. **Poor extensibility** — adding a new provider requires duplicating provider logic and registering in 2+ places

The proposed architecture solves all 6 problems with 6 sprints of incremental, additive changes. Sprint A and B can be done immediately with zero risk. The critical mass is reached at Sprint E which is the highest-risk (but highest-reward) migration.

**Next step**: Review this document, prioritize sprints, and decide implementation order.
