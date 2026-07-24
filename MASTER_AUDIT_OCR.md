# MASTER AUDIT — OCR Architecture, Pipeline & Quality

**Date:** 2026-07-24  
**Scope:** Seluruh pipeline OCR Bendaharaku  
**Method:** Static code analysis, architecture review, security audit (55+ files audited)

| Area | Score | Status |
|------|-------|--------|
| Architecture | 8/10 | ✅ Pipeline clean, SRP terjaga, no circular deps |
| Security | 7/10 | ⚠️ No magic bytes, but private storage; CORS `*` (internal) |
| Performance | 7/10 | ⚠️ No client compression, no preprocessing, no OCR caching |
| Maintainability | 7/10 | ⚠️ Duplicate number parsing, dead code, misleading config |
| AI Integration | 8/10 | ✅ OCR→AI boundary clean via EvidenceData DTO |
| **Production Readiness** | **7/10** | ✅ Commit idempoten, ✅ no double-transaction. ⚠️ Retry miswired, file cleanup broken |

---

## 1. Architecture Overview

### Complete Component Map

```
┌─────────────────────────────────────────────────────────────────────────┐
│ FRONTEND (Vue 3)                                                        │
│  ChatUploadSheet.vue  ──►  useEvidenceUpload.js  ──►  axios POST         │
│  EvidencePreview.vue                                                   │
│  EvidenceReviewSheet.vue  ──►  axios PATCH/POST                         │
└───────────────────────────────────────────────────┬─────────────────────┘
                                                      │ POST /chat/evidence
                                                      ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ CONTROLLER LAYER                                                        │
│  EvidenceController::store()       ← StoreEvidenceRequest (validation)  │
│  EvidenceReviewController::show()  ← GET  /{uuid}/draft                 │
│  EvidenceReviewController::update()← PATCH /{uuid}/draft                │
│  EvidenceReviewController::commit()← POST  /{uuid}/commit               │
│  EvidenceDebugController          ← debug/health/timeline/stats         │
└───────────────────────────────────┬─────────────────────────────────────┘
                                      │
                                      ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ UPLOAD SERVICE                                                          │
│  EvidenceUploadService::upload()  →  Storage::disk('evidence')          │
│  EvidenceUploadService::delete()                                       │
│  EvidencePipelineService::queue()  [UPLOADED → QUEUED]                  │
│  ProcessEvidenceJob::dispatch()                                        │
└───────────────────────────────────┬─────────────────────────────────────┘
                                      │
                                      ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ PIPELINE (ProcessEvidenceJob)                                           │
│                                                                         │
│  EvidencePipeline::process()                                            │
│    ├── OCRStage          → OCRClient::extract()     → Python PaddleOCR  │
│    ├── NormalizeStage    → OCRTextNormalizer::normalize()               │
│    ├── ClassificationStage → DocumentClassifier::classify()             │
│    ├── ParsingStage      → Transfer/Shopping/Qris ReceiptParser         │
│    └── ResolveStage      → EvidenceResolver                             │
│                             ├── TransferResolver → WalletResolver       │
│                             ├── WalletResolver                          │
│                             ├── MerchantResolver                        │
│                             ├── CategoryResolver                        │
│                             └── DuplicateResolver                       │
└───────────────────────────────────┬─────────────────────────────────────┘
                                      │
                                      ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ PERSISTENCE                                                             │
│  ProcessEvidenceJob::persistResults()  →  PipelineService state update   │
│  EvidencePipelineService::ocrCompleted/classified/parsed/resolved/     │
│  EvidenceCommitService::commit() → ProcessTransactionAction::create()  │
└───────────────────────────────────┬─────────────────────────────────────┘
                                      │
                                      ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ OCR MICROSERVICE (Python - FastAPI + PaddleOCR CPU)                     │
│  POST /ocr/extract  ←  Receive image, extract text                     │
│  GET  /ready        ←  Health check (for Docker)                       │
│  GET  /             ←  Root                                            │
│                                                                         │
│  app/main.py               FastAPI app, CORS                            │
│  app/api/extract.py        POST /ocr/extract (MIME + size validation)   │
│  app/services/ocr_service.py  PaddleOCR lazy singleton                  │
│  app/utils/image.py        EXIF rotation + resize to max 4096px        │
│  app/schemas/ocr.py        Pydantic OCRResponse + HealthResponse        │
└─────────────────────────────────────────────────────────────────────────┘
```

### Data Flow

```
User Image Upload
    │
    ▼
EvidenceController::store()
    ├── StoreEvidenceRequest  [max:10240, mimes:jpg/jpeg/png/webp, dimensions]
    ├── EvidenceUploadService::upload()
    │     → Storage::disk('evidence')->put("user_{id}/original/{uuid}.{ext}")
    │     → Evidence::create(status: UPLOADED)
    ├── event(new EvidenceUploaded) — NO LISTENERS
    ├── EvidencePipelineService::queue() → QUEUED
    └── ProcessEvidenceJob::dispatch()
          │
          ├── $pipelineService->startProcessing() → PROCESSING
          ├── Pipeline:
          │     OCRStage → NormalizeStage → ClassificationStage
          │     → ParsingStage → ResolveStage
          └── persistResults() → OCR_COMPLETED → CLASSIFIED → PARSED → RESOLVED → READY

EvidenceReviewController
    GET /{uuid}/draft    → TransactionDraft JSON
    PATCH /{uuid}/draft  → Update resolved_data
    POST /{uuid}/commit  → ProcessTransactionAction (DB transaction)
```

### Pipeline Stage Lifecycle

```
UPLOADED → QUEUED → PROCESSING → OCR_COMPLETED → CLASSIFIED → PARSED → RESOLVED → READY → COMPLETED
                                                                    ↘ FAILED
```

---

## 2. Dependency Analysis

### No circular dependencies ✅
### No god classes ✅
### No god methods ✅ (EvidenceCommitService::commit() is long but acceptable)
### Module boundaries are clean ✅
### Interface segregation is proper ✅

---

## 3. Critical Findings (P0)

### P0-1: Duplicate Number Parsing Logic — SSOT Violation

| File | Method |
|------|--------|
| `app/Evidence/Parsers/Extractors/NumberParser.php:18` | `NumberParser::parse()` |
| `app/Evidence/Parsers/Extractors/AmountExtractor.php:68` | `AmountExtractor::parseAmount()` |

Both methods contain ~90% identical logic for parsing Indonesian number formats (comma/dot as thousands/decimal separators). `NumberParser` is **never used anywhere** — it is dead code. `AmountExtractor` has its own private duplicate.

**Impact:** Any fix to number parsing must be applied in both places. Violates Single Source of Truth.

### P0-2: Idempotency Gap — Pipeline Can Re-process Already-Processed Evidence

`ProcessEvidenceJob::handle()` (line 50-58):
```php
$evidence = Evidence::find($this->evidenceId);
if (! $evidence) { return; }
// NO check if already processed, NO check if already OCR'd
```

The job does not verify if the evidence has already been processed before running the pipeline. If:
- User clicks "retry" after OCR succeeded but a later stage failed
- Queue dispatches the job twice (rare but possible)
- The pipeline runs with stale status from a previous partial run

**Consequence:** OCR is called again (wasteful), duplicate processing logs created, but commit is idempotent (checks `transaction_id`). So transaction duplication is prevented — but OCR API usage doubles.

### P0-3: No OCR Result Caching on Retry

When a pipeline stage fails AFTER OCR succeeds:
```
OCR succeeds (text extracted)
    → Normalize succeeds
    → Classification fails (network blip)
    → Status = FAILED
    → User retries
    → ENTIRE pipeline runs again
    → OCR CALLED AGAIN (wasteful)
```

The OCR text IS stored in `evidence.ocr_text` (written by `OCRClient::extract()` on line 121). But the pipeline never checks if `ocr_text` already exists before calling OCR. A simple check in `OCRStage` could save 100% of OCR API calls on retries.

---

## 4. High Priority (P1)

### P1-1: MIME Validation — No Magic Byte Inspection

- `StoreEvidenceRequest` uses `'mimes:jpg,jpeg,png,webp'` → Laravel checks file extension, not content
- `OCRClient::extract()` uses `$evidence->mime_type` from `$file->getMimeType()` → client-reported
- Python `extract.py:27` checks `file.content_type` → also client-reported
- **No magic byte inspection anywhere in the pipeline**

**Context:** Storage is `storage/app/private/evidence/{user_id}/` — NOT publicly accessible. Executable files cannot be reached via web. Risk is limited to disk space pollution and potential issues if files are ever moved or the disk driver changes.

**Recommendation:** Add `finfo()` or `exif_imagetype()` validation in `EvidenceUploadService` as defense-in-depth. Priority is moderate given private storage.

### P1-2: No Job-Level Retry / Dead Letter Queue

```
ProcessEvidenceJob:        $tries = 1       (NO retry)
OCRClient HTTP retry:      retry: 3         (3 HTTP retries, 1s delay, 30s timeout)
evidence.php config:       retry.ocr.max_tries = 3  (COMPLETELY IGNORED)
```

- HTTP-level retries are fine, but after they're exhausted the job fails permanently
- Config has sophisticated retry settings that are never wired to the job
- No exponential backoff
- No dead-letter queue
- No alert on repeated failure

### P1-3: CleanupStaleEvidenceCommand — File Cleanup Bug

`CleanupStaleEvidenceCommand.php:57`:
```php
if ($evidence->file_path && ...)  // BUG: column is 'path', not 'file_path'
```

`$evidence->file_path` is always `null` → files are never cleaned up. Orphan files accumulate indefinitely.

### P1-4: No Client-Side Image Compression

Mobile camera uploads (12MP = ~4-5MB) bypass any compression. No `canvas` resize before upload. Long upload times on slow connections.

### P1-5: No Image Preprocessing Before OCR

Python's `image.py` only does EXIF rotation + resize >4096px. Missing:
- ❌ Grayscale conversion (low-quality scans)
- ❌ Denoising (grainy receipts)
- ❌ Adaptive thresholding (poor lighting)
- ❌ Deskew/rotation correction (tilted photos)
- ❌ Sharpening / contrast enhancement

### P1-6: Misleading Unused Config

```php
// config/evidence.php — NEVER referenced by any code
'retry' => ['ocr' => ['max_tries' => 3, 'backoff' => 60], ...]
'timeout' => ['ocr' => 120, 'pipeline' => 300]
```

Developers may deploy thinking retry is configured, but it has zero effect.

### P1-7: TransactionDraft 20+ Parameter Constructor

Hard to call correctly, error-prone. Consider builder pattern.

---

## 5. Medium Priority (P2)

| ID | Issue | Severity | File |
|----|-------|----------|------|
| P2-1 | Stale model updates (architecture risk — not a bug currently since pipeline is sequential) | Architecture fragility | `OCRClient.php`, `NormalizeStage.php`, `ProcessEvidenceJob.php` |
| P2-2 | Python OCR CORS `allow_origins=["*"]` | Low risk (internal service) | `ocr-service/app/main.py:24` |
| P2-3 | Events dispatched but zero listeners (4 events) | Dead code | `app/Evidence/Events/*.php` |
| P2-4 | ClassificationStage relies on DB ordering (refresh) | Architecture fragility | `ClassificationStage.php:31` |
| P2-5 | Hardcoded version string `'1.0'` | Minor | `OCRClient.php:125` |
| P2-6 | Magic number 10MB repeated in 3 places | Minor | Controller, config, Python |

---

## 6. Low Priority (P3)

| ID | Issue | File |
|----|-------|------|
| P3-1 | EvidenceContext all-public properties (no encapsulation) | `EvidenceContext.php` |
| P3-2 | NumberParser is dead code (never used) | `NumberParser.php` |
| P3-3 | No upload cancellation (AbortController) | `useEvidenceUpload.js` |
| P3-4 | No retry count in UI | `EvidencePreview.vue` |
| P3-5 | DuplicateResolver uses ±5 min window — effective for accidental double-tap but not for near-identical receipts on different days | `DuplicateResolver.php` |

### Idempotency Analysis

| Operation | Idempotent? | Mechanism |
|-----------|-------------|-----------|
| Upload (EvidenceController::store) | ✅ Always creates new record (UUIDv4) | Each upload = new Evidence row |
| Pipeline (ProcessEvidenceJob) | ❌ No check before re-processing | No guard against re-running if OCR already done |
| Commit (EvidenceCommitService::commit) | ✅ Checks `transaction_id !== null` | Prevents double transaction creation |
| Retry (EvidencePipelineService::retry) | ⚠️ Status reset to QUEUED | Pipeline re-runs from scratch, including OCR |

### OCR Timeout Recovery

Current flow when OCR times out (30s default):
1. `OCRClient::extract()` throws `RuntimeException`
2. Pipeline catches it → `$pipelineService->fail()` → status = FAILED
3. User retries → status = QUEUED → job re-dispatched
4. **Entire pipeline re-runs**, including OCR API call

**Gap:** The OCR text IS already stored in `evidence.ocr_text` from the previous failed attempt (written before update() in `OCRClient::extract()`? — actually, the `update()` at line 121 only runs on successful response). So on retry, OCR is always re-called. No partial result reuse.

---

## 7. Test Coverage Gaps

❌ Image preprocessing (Python side)  
❌ MIME spoofing / magic byte validation  
❌ Concurrent evidence processing  
❌ EXIF rotation handling  
❌ Duplicate detection  
❌ Blurry / corrupted / empty image  
❌ OCR service timeout / down  
❌ Network interruption  
❌ Large image rejection path  
❌ GPU/CPU fallback  

**Existing tests:** OCRClientConfigTest (7), OCRTextNormalizerTest (17), OCRClientTest (1), ShoppingReceiptParserTest, QrisReceiptParserTest

---

## 8. Dead Code Inventory

| Item | Location | Status |
|------|----------|--------|
| `NumberParser` (entire class) | `Extractors/NumberParser.php` | Never used |
| `EvidenceUploaded` event | `Events/EvidenceUploaded.php` | Zero listeners |
| `EvidenceQueued` event | `Events/EvidenceQueued.php` | Zero listeners |
| `EvidenceProcessingStarted` event | `Events/EvidenceProcessingStarted.php` | Zero listeners |
| `EvidenceProcessingCompleted` event | `Events/EvidenceProcessingCompleted.php` | Zero listeners |
| `evidence.retry.*` config | `config/evidence.php` | Never referenced |
| `evidence.timeout.*` config | `config/evidence.php` | Never referenced |

---

## 9. Architecture Scores

| Category | Score | Rationale |
|----------|-------|-----------|
| **Architecture** | **8/10** | Clean pipeline, good SoC, modular extractors. Deduct: stale model updates (architectural risk), unused config |
| **Security** | **7/10** | No magic byte validation, but private storage reduces risk. CORS wide open (internal service). Broken file cleanup. |
| **Performance** | **7/10** | No client compression, no preprocessing, no OCR caching. Deduct: OCR re-called on every retry |
| **Maintainability** | **7/10** | Clean extractor pattern, but duplicate parsing, dead code, misleading config |
| **AI Integration** | **8/10** | Clean DTO boundary between OCR and AI. Rule-based classifier is proper SSOT |
| **Production Readiness** | **7/10** | Commit is idempotent ✅. Pipeline sequential = safe ✅. Missing DLQ, retry misconfig, broken cleanup, no OCR result reuse |

---

## 10. Final Verdict

```
┌─────────────────────────────────────────────────────────────────────┐
│                                                                     │
│        ⚠️  PRODUCTION READY with High Priority Technical Debt       │
│                                                                     │
│  Fondasi arsitektur solid, pipeline modular, commit idempoten,      │
│  tidak ada circular dependency, tidak ada god class, batas antar    │
│  modul jelas, dan pipeline berjalan sequential dalam 1 job.         │
│                                                                     │
│  1 Critical (P0) — Fix ASAP:                                        │
│                                                                     │
│    1. Duplicate number parsing (AmountExtractor vs NumberParser)    │
│                                                                     │
│  7 High (P1) — Should fix sprint ini:                               │
│                                                                     │
│    1. No magic byte validation (defense-in-depth, private storage)  │
│    2. No job-level retry / DLQ / retry config ignored               │
│    3. CleanupStaleEvidenceCommand file cleanup broken (file_path)   │
│    4. No client-side image compression                              │
│    5. No image preprocessing (denoise, deskew, threshold)           │
│    6. No OCR result caching on retry (wasteful re-OCR)              │
│    7. Misleading unused retry config                                │
│                                                                     │
│  5 Medium (P2) — Sprint berikutnya:                                 │
│                                                                     │
│    1. Stale model updates (architecture risk, not active bug)       │
│    2. Python CORS restrict to specific origins                      │
│    3. Clean up dead code (events without listeners, unused config)  │
│    4. ClassificationStage DB ordering fragility                     │
│    5. Hardcoded version + magic number 10MB duplication             │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 11. Recommended Fix Order (by ROI)

| Rank | Fix | Priority | Effort | Impact | File(s) |
|------|-----|----------|--------|--------|---------|
| 1 | Eliminate duplicate number parsing — make `NumberParser` SSOT | P0 | ~15 min | High (SSOT) | `AmountExtractor.php`, `NumberParser.php` |
| 2 | Fix `file_path` → `path` in CleanupStaleEvidenceCommand | P1 | 1 line | High (orphan cleanup) | `CleanupStaleEvidenceCommand.php:57` |
| 3 | Add OCR result reuse on retry (check `evidence.ocr_text` before calling OCR) | P1 | ~30 min | Medium (API cost, speed) | `OCRStage.php`, `ProcessEvidenceJob.php` |
| 4 | Wire `evidence.retry.ocr` config to `ProcessEvidenceJob` | P1 | ~30 min | High (reliability) | `ProcessEvidenceJob.php`, `evidence.php` |
| 5 | Add magic byte validation in upload service | P1 | ~30 min | Medium (defense-in-depth) | `StoreEvidenceRequest.php`, `EvidenceUploadService.php` |
| 6 | Add client-side image compression | P1 | ~1 hour | Medium (UX) | `useEvidenceUpload.js` |
| 7 | Add image preprocessing in Python (deskew, threshold, denoise) | P1 | ~2 hours | Medium (accuracy) | `ocr-service/app/utils/image.py` |
| 8 | Replace stale model updates with context-only flow | P2 | ~2 hours | Low (architecture risk) | `NormalizeStage.php`, `OCRClient.php`, `ProcessEvidenceJob.php` |
| 9 | Clean up dead code (unused events, unused config) | P2 | ~30 min | Low (maintainability) | Various |
| 10 | Restrict Python CORS origins | P2 | 1 line | Low (security) | `ocr-service/app/main.py:24` |
