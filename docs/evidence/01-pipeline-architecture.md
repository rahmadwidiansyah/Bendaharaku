# Evidence Pipeline Architecture

## Overview

Evidence Pipeline adalah sistem pemrosesan bukti transaksi (struk, screenshot, dokumen digital) yang mengubah gambar menjadi draft transaksi siap commit menggunakan OCR, AI classification, dan parsing otomatis.

## Architecture Flow

```
Upload → Queue → OCR → Normalize → Classify → Parse → Resolve → Review → Commit
```

### Core Components

```
app/Evidence/
├── Pipeline/
│   ├── EvidencePipeline.php          # Main pipeline orchestrator
│   ├── Context/
│   │   └── EvidenceContext.php       # Pipeline state container
│   └── Stages/
│       ├── OCRStage.php              # Text extraction
│       ├── NormalizeStage.php        # Text cleanup
│       ├── ClassificationStage.php   # Document type detection
│       ├── ParserStage.php           # Structured data extraction
│       └── ResolverStage.php         # Entity resolution (wallet, category)
├── Jobs/
│   └── ProcessEvidenceJob.php        # Queue handler
└── Events/
    ├── EvidenceQueued.php
    ├── EvidenceProcessingStarted.php
    └── EvidenceProcessingCompleted.php
```

## Pipeline Stages

### 1. Upload Stage
**責任:** Receive file, validate, store to S3-compatible storage

**Input:** `UploadedFile` from user
**Output:** `Evidence` model with status `UPLOADED`

**Service:** `EvidenceUploadService`

```php
$result = $uploadService->upload($user, $file);
// Returns: ['evidence' => Evidence, 'url' => string]
```

**Validations:**
- File type: image/*, application/pdf
- Max size: 10MB
- Mime type check

---

### 2. Queue Stage
**責任:** Dispatch evidence to background processing queue

**Input:** Evidence with status `UPLOADED`
**Output:** Evidence with status `QUEUED`

**Job:** `ProcessEvidenceJob`

```php
$pipelineService->queue($evidence);
// Dispatches ProcessEvidenceJob to 'evidence' queue
```

---

### 3. OCR Stage
**責任:** Extract text from image/PDF

**Input:** Evidence with file URL
**Output:** Raw text + metadata

**Engine Options:**
- **OCRClient** (default): External OCR service via HTTP
- **StubOCR** (test): Hardcoded text for testing
- Future: Google Vision, AWS Textract

**Output Fields:**
```php
$evidence->update([
    'ocr_text' => $extractedText,
    'ocr_engine' => 'OCRClient',
    'ocr_duration_ms' => 1523,
    'ocr_version' => 'v2.1'
]);
```

**Stage Code:**
```php
class OCRStage implements PipelineStage {
    public function process(EvidenceContext $context): void {
        $result = $this->ocrClient->extract($context->evidence);
        $context->setText($result['text']);
        $context->setMetadata('ocr', $result);
    }
}
```

---

### 4. Normalize Stage
**責任:** Clean and normalize OCR text

**Transformations:**
- Remove excessive whitespace
- Normalize currency formats (Rp 1.000 → 1000)
- Fix common OCR errors (O → 0 in numbers)
- Standardize line breaks

**Example:**
```
Input:  "QRIS\n\n\nRp  1.000\n\nMerchant:   INDOMARET"
Output: "QRIS\nRp 1000\nMerchant: INDOMARET"
```

---

### 5. Classification Stage
**責任:** Detect document type from normalized text

**Classifier:** `DocumentClassifier`

**Supported Types:**
- `QRIS_RECEIPT` — QRIS payment receipt (GoPay, OVO, Dana, QRIS standard)
- `BANK_MUTATION` — Bank statement/mutation
- `E_WALLET` — E-wallet transaction (non-QRIS)
- `TRANSFER_RECEIPT` — Bank transfer receipt
- `GENERIC_RECEIPT` — Generic/unknown receipt

**Classification Logic:**
```php
// Priority-based rule matching
if (contains('QRIS') && contains('Merchant')) return QRIS_RECEIPT;
if (contains('Mutasi') || contains('Saldo')) return BANK_MUTATION;
if (contains('Transfer Berhasil')) return TRANSFER_RECEIPT;
// ... fallback to GENERIC_RECEIPT
```

**Output:**
```php
$evidence->document_type = DocumentType::QrisReceipt;
```

---

### 6. Parser Stage
**責任:** Extract structured transaction data

**Parser Selection:** Based on `document_type`

```php
$parser = match($evidence->document_type) {
    DocumentType::QrisReceipt => new QrisReceiptParser(),
    DocumentType::TransferReceipt => new TransferReceiptParser(),
    default => new GenericReceiptParser(),
};

$parsed = $parser->parse($normalizedText);
```

**Parser Output:** `ParsedReceipt` DTO

```php
[
    'merchant_name' => 'INDOMARET',
    'amount' => 25000.0,
    'transaction_date' => '2026-07-22',
    'reference_number' => '123456789012',
    'payment_method' => 'BCA',
    'raw_data' => [...],
    'confidence' => 0.85
]
```

**Parsers:**
- `QrisReceiptParser` — QRIS payment receipts
- `TransferReceiptParser` — Bank transfer receipts
- `GenericReceiptParser` — Fallback parser

See: [Parser Documentation](../parser/)

---

### 7. Resolver Stage
**責任:** Resolve entities (wallet, category, merchant) and create transaction draft

**Resolver:** `EvidenceResolver`

**Resolution Steps:**

#### 7.1 Wallet Resolution
Match `payment_method` to user's wallet:
```php
// Exact name match
$wallet = Wallet::where('user_id', $userId)
    ->where('name', 'ILIKE', '%BCA%')
    ->first();

// Fallback: keyword match
$wallet = Wallet::where('user_id', $userId)
    ->whereRaw("keyword ILIKE ?", ['%bca%'])
    ->first();
```

**Confidence Score:**
- Exact name match: 1.0
- Keyword match: 0.9
- AI fallback: 0.7
- No match: 0.0 (requires user review)

#### 7.2 Category Resolution
Match `merchant_name` to user's categories:

```php
$category = Category::where('user_id', $userId)
    ->whereRaw("keyword ILIKE ?", ['%indomaret%'])
    ->first();

// Fallback: AI category prediction
if (!$category) {
    $aiPrediction = $this->nlpService->predictCategory(
        text: "Belanja di Indomaret",
        userCategories: $categories
    );
    $category = $aiPrediction['category'];
    $confidence = $aiPrediction['confidence'];
}
```

**Confidence Score:**
- Keyword match: 0.8
- AI prediction: AI confidence (0.5–0.9)
- Default fallback: 0.5

#### 7.3 Transaction Type
Derived from category:
```php
$transactionType = match($category->type->name) {
    'Expense' => 'EXPENSE',
    'Income' => 'INCOME',
    'Transfer' => 'TRANSFER',
    'Debt' => 'DEBT',
    'Receivable' => 'RECEIVABLE',
};
```

#### 7.4 Duplicate Detection
Check for potential duplicates:
```php
// By reference number
$existing = TransactionLog::where('user_id', $userId)
    ->where('reference_number', $refNumber)
    ->exists();

// By amount + wallet + date (±5 minutes)
$existing = TransactionLog::where('user_id', $userId)
    ->where('amount', $amount)
    ->where('source_wallet_id', $walletId)
    ->whereBetween('date', [$date->subMinutes(5), $date->addMinutes(5)])
    ->exists();
```

**Output:** Warnings array if duplicate detected

#### 7.5 Draft Creation

```php
$draft = new TransactionDraft(
    transactionType: 'EXPENSE',
    walletId: 39,
    walletName: 'BCA',
    categoryId: 137,
    categoryName: 'Belanja Dapur & Groceries',
    merchantName: 'Indomaret',
    amount: 25000.0,
    currency: 'IDR',
    description: null,
    transactionDate: '2026-07-22',
    referenceNumber: '123456789012',
    destinationName: null,
    destinationAccount: null,
    destinationWalletId: null,
    confidence: 0.83,
    warnings: [],
    metadata: [
        'wallet_source' => ['confidence' => 1.0, 'match_method' => 'exact_name'],
        'category' => ['confidence' => 0.8],
        'duplicate' => ['is_duplicate' => false],
    ],
    resolved: true,
    amountConfidence: 0.95,
    walletConfidence: 1.0,
    categoryConfidence: 0.8,
);

$evidence->update([
    'resolved_data' => $draft->toArray(),
    'status' => EvidenceStatus::Ready,
]);
```

---

## Status Flow

```
UPLOADED → QUEUED → PROCESSING → (stages) → READY → COMPLETED
                                        ↓
                                     FAILED
```

### Status Definitions

| Status | Description | Can Retry | Next Action |
|--------|-------------|-----------|-------------|
| `UPLOADED` | File uploaded, not yet queued | — | Queue |
| `QUEUED` | In background queue | — | Process |
| `PROCESSING` | Pipeline is running | — | Wait |
| `READY` | Draft ready for review | Yes | Review/Commit |
| `FAILED` | Processing failed | Yes | Retry |
| `COMPLETED` | Transaction created | No | — |

### Status Transitions

```php
// Upload → Queue
$evidence->status = EvidenceStatus::Queued;

// Queue → Processing
$evidence->status = EvidenceStatus::Processing;

// Processing → Ready (success)
$evidence->status = EvidenceStatus::Ready;

// Processing → Failed (error)
$evidence->status = EvidenceStatus::Failed;
$evidence->error_message = $exception->getMessage();

// Ready → Completed (commit)
$evidence->status = EvidenceStatus::Completed;
$evidence->transaction_id = $transaction->id;
$evidence->completed_at = now();
```

---

## Error Handling

### Stage Failure
If any stage throws exception:
```php
try {
    $this->pipeline->process($context);
} catch (\Throwable $e) {
    $evidence->update([
        'status' => EvidenceStatus::Failed,
        'error_message' => $e->getMessage(),
    ]);
    
    Log::error('Evidence pipeline failed', [
        'evidence_id' => $evidence->id,
        'stage' => $context->getCurrentStage(),
        'error' => $e->getMessage(),
    ]);
}
```

### Retry Strategy
- Failed evidences can be requeued manually
- `ProcessEvidenceJob` has `tries = 3` with exponential backoff
- Permanent failures marked with `error_message`

---

## Logging & Observability

### Processing Log
Setiap stage dicatat di `evidence_processing_logs`:

```php
ProcessingLog::create([
    'evidence_id' => $evidence->id,
    'stage' => 'OCR',
    'status' => 'OCR_COMPLETED',
    'duration_ms' => 1523,
    'metadata' => ['engine' => 'OCRClient', 'version' => 'v2.1'],
]);
```

### Timeline View
```php
$timeline = $processingLogService->getTimeline($evidence);
// Returns chronological stages with status and duration
```

### Metrics
- Average processing time per stage
- Success/failure rate per document type
- OCR accuracy by engine
- Parser confidence distribution

---

## Configuration

### Queue Configuration
```php
// config/queue.php
'connections' => [
    'evidence' => [
        'driver' => 'database',
        'queue' => 'evidence',
        'retry_after' => 300,
    ],
],
```

### Pipeline Version
```php
// config/evidence.php
'pipeline_version' => '1.0',
```

Stored in `evidence.commit_version` for audit trail.

---

## Testing

### Unit Test with Stub OCR
```php
app()->instance(OCRClient::class, new class extends OCRClient {
    public function extract($evidence): array {
        return [
            'text' => 'QRIS\nMerchant: INDOMARET\nNominal: Rp 25.000',
            'processing_time_ms' => 100,
            'engine' => 'StubOCR',
        ];
    }
});

$pipeline->process($evidence);
```

### Integration Test
```bash
php artisan test --filter EvidencePipelineTest
```

---

## Performance

### Benchmarks (avg)
- Upload: ~50ms
- OCR: ~1500ms (external service)
- Normalize: ~10ms
- Classify: ~20ms
- Parse: ~50ms
- Resolve: ~100ms (DB queries + AI)

**Total:** ~1730ms per evidence

### Optimization Tips
- Use queue workers: `php artisan queue:work --queue=evidence`
- Scale OCR service horizontally
- Cache category/wallet lookups
- Batch AI predictions

---

## Next Steps

- [Evidence Context](./02-evidence-context.md)
- [Review Flow](./03-review-flow.md)
- [Parser Documentation](../parser/)
- [AI Architecture](../ai/)
