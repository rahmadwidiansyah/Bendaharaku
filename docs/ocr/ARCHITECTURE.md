# Evidence Pipeline Architecture

## Overview

Evidence Pipeline adalah stage-based pipeline untuk memproses bukti transaksi (gambar) menjadi data transaksi siap commit.

## Principles

1. **Stage-based**: Setiap stage bertanggung jawab pada satu proses
2. **Context-driven**: Stage berkomunikasi melalui `EvidenceContext`
3. **Middleware pattern**: Stage memanggil stage berikutnya via `Closure $next`
4. **Dependency Injection**: Service di-resolve melalui container
5. **Backward Compatible**: API dan UX tidak berubah

## Directory Structure

```
app/Evidence/Pipeline/
├── EvidencePipeline.php          # Pipeline orchestrator
├── Contracts/
│   └── EvidenceStage.php         # Stage interface
├── Context/
│   └── EvidenceContext.php       # Context object
└── Stages/
    ├── OCRStage.php              # OCR text extraction
    ├── NormalizeStage.php        # OCR text normalization
    ├── ClassificationStage.php   # Document type classification
    ├── ParsingStage.php          # Text → EvidenceData
    └── ResolveStage.php          # EvidenceData → TransactionDraft
```

## Flow

```
Upload → ProcessEvidenceJob
           ↓
         EvidencePipeline
           ↓
         ┌─ OCRStage ────────────── PaddleOCR HTTP
         ├─ NormalizeStage ───────── Text cleanup
         ├─ ClassificationStage ──── Rule-based classifier
         ├─ ParsingStage ─────────── TransferReceiptParser
         └─ ResolveStage ─────────── EvidenceResolver
           ↓
         PersistResults → Database
           ↓
         Review → Commit
```

## Adding a New Stage

1. Create class implementing `EvidenceStage`
2. Implement `handle(EvidenceContext $context, Closure $next): void`
3. Add to pipeline array in `ProcessEvidenceJob`
4. See `ADDING_NEW_STAGE.md` for details
