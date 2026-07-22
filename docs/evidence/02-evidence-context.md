# Evidence Context

## Overview

`EvidenceContext` adalah container state yang digunakan selama pipeline execution. Context ini menyimpan data sementara yang dihasilkan oleh setiap stage dan diteruskan ke stage berikutnya.

## Purpose

- **Decoupling:** Stage tidak perlu langsung menulis ke database — state disimpan di context
- **Testability:** Context dapat di-mock untuk testing
- **Flexibility:** Stage dapat membaca output stage sebelumnya tanpa database query
- **Atomicity:** Changes committed ke database hanya di akhir pipeline

## Location

```
app/Evidence/Pipeline/Context/EvidenceContext.php
```

## Structure

```php
namespace App\Evidence\Pipeline\Context;

use App\Evidence\DTO\ParsedReceipt;
use App\Evidence\DTO\TransactionDraft;
use App\Enums\DocumentType;
use App\Models\Evidence;

class EvidenceContext
{
    public function __construct(
        private Evidence $evidence,
        private ?string $text = null,
        private ?string $normalizedText = null,
        private ?DocumentType $documentType = null,
        private ?ParsedReceipt $parsedReceipt = null,
        private ?TransactionDraft $draft = null,
        private array $metadata = [],
    ) {}

    // Getters
    public function getEvidence(): Evidence;
    public function getText(): ?string;
    public function getNormalizedText(): ?string;
    public function getDocumentType(): ?DocumentType;
    public function getParsedReceipt(): ?ParsedReceipt;
    public function getDraft(): ?TransactionDraft;
    public function getMetadata(string $key = null): mixed;

    // Setters
    public function setText(string $text): void;
    public function setNormalizedText(string $text): void;
    public function setDocumentType(DocumentType $type): void;
    public function setParsedReceipt(ParsedReceipt $receipt): void;
    public function setDraft(TransactionDraft $draft): void;
    public function setMetadata(string $key, mixed $value): void;

    // State checks
    public function hasText(): bool;
    public function hasNormalizedText(): bool;
    public function hasDocumentType(): bool;
    public function hasParsedReceipt(): bool;
    public function hasDraft(): bool;
}
```

## Lifecycle Example

### Initial State (Upload)
```php
$context = new EvidenceContext(
    evidence: $evidence, // Evidence model from DB
    text: null,
    normalizedText: null,
    documentType: null,
    parsedReceipt: null,
    draft: null,
    metadata: [],
);
```

### After OCR Stage
```php
$context->setText("QRIS\nMerchant: INDOMARET\nNominal: Rp 25.000\n...");
$context->setMetadata('ocr', [
    'engine' => 'OCRClient',
    'duration_ms' => 1523,
    'confidence' => 0.95,
]);

// Context state:
// - text: "QRIS\n..."
// - normalizedText: null
// - documentType: null
// - parsedReceipt: null
// - draft: null
```

### After Normalize Stage
```php
$normalizedText = $this->normalizeText($context->getText());
$context->setNormalizedText($normalizedText);

// Context state:
// - text: "QRIS\n..." (raw OCR)
// - normalizedText: "QRIS Merchant: INDOMARET Nominal: Rp 25000" (cleaned)
// - documentType: null
// - parsedReceipt: null
// - draft: null
```

### After Classification Stage
```php
$docType = $this->classify($context->getNormalizedText());
$context->setDocumentType($docType);

// Context state:
// - text: "QRIS\n..."
// - normalizedText: "QRIS Merchant: INDOMARET..."
// - documentType: DocumentType::QrisReceipt
// - parsedReceipt: null
// - draft: null
```

### After Parser Stage
```php
$parser = $this->selectParser($context->getDocumentType());
$parsed = $parser->parse($context->getNormalizedText());
$context->setParsedReceipt($parsed);

// Context state:
// - text: "QRIS\n..."
// - normalizedText: "QRIS Merchant: INDOMARET..."
// - documentType: DocumentType::QrisReceipt
// - parsedReceipt: ParsedReceipt {
//     merchant_name: 'INDOMARET',
//     amount: 25000.0,
//     transaction_date: '2026-07-22',
//     reference_number: '123456789012',
//     ...
//   }
// - draft: null
```

### After Resolver Stage
```php
$draft = $this->resolver->resolve($context);
$context->setDraft($draft);

// Context state:
// - text: "QRIS\n..."
// - normalizedText: "QRIS Merchant: INDOMARET..."
// - documentType: DocumentType::QrisReceipt
// - parsedReceipt: ParsedReceipt {...}
// - draft: TransactionDraft {
//     transactionType: 'EXPENSE',
//     walletId: 39,
//     categoryId: 137,
//     amount: 25000.0,
//     confidence: 0.83,
//     ...
//   }
```

### Persistence (End of Pipeline)
```php
$evidence->update([
    'ocr_text' => $context->getText(),
    'document_type' => $context->getDocumentType(),
    'parser_engine' => 'QrisReceiptParser',
    'resolved_data' => $context->getDraft()->toArray(),
    'status' => EvidenceStatus::Ready,
]);
```

## Usage in Pipeline Stages

### OCR Stage
```php
class OCRStage implements PipelineStage
{
    public function process(EvidenceContext $context): void
    {
        $evidence = $context->getEvidence();
        
        $result = $this->ocrClient->extract($evidence);
        
        // Store in context
        $context->setText($result['text']);
        $context->setMetadata('ocr', [
            'engine' => $result['engine'],
            'duration_ms' => $result['processing_time_ms'],
        ]);
        
        // Persist to DB
        $evidence->update([
            'ocr_text' => $result['text'],
            'ocr_engine' => $result['engine'],
            'ocr_duration_ms' => $result['processing_time_ms'],
        ]);
    }
}
```

### Normalize Stage
```php
class NormalizeStage implements PipelineStage
{
    public function process(EvidenceContext $context): void
    {
        // Read from context (no DB query needed)
        $rawText = $context->getText();
        
        if (!$rawText) {
            throw new \RuntimeException('No OCR text available');
        }
        
        $normalized = $this->normalize($rawText);
        $context->setNormalizedText($normalized);
    }
    
    private function normalize(string $text): string
    {
        // Remove excessive whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Normalize currency
        $text = preg_replace('/Rp\s*(\d+)\.(\d+)/', '$1$2', $text);
        
        return trim($text);
    }
}
```

### Classification Stage
```php
class ClassificationStage implements PipelineStage
{
    public function process(EvidenceContext $context): void
    {
        $text = $context->getNormalizedText() ?? $context->getText();
        
        if (!$text) {
            throw new \RuntimeException('No text available for classification');
        }
        
        $docType = $this->classifier->classify($text);
        
        // Store in context
        $context->setDocumentType($docType);
        
        // Persist to DB
        $context->getEvidence()->update([
            'document_type' => $docType,
        ]);
    }
}
```

### Parser Stage
```php
class ParserStage implements PipelineStage
{
    public function process(EvidenceContext $context): void
    {
        $docType = $context->getDocumentType();
        $text = $context->getNormalizedText() ?? $context->getText();
        
        if (!$docType || !$text) {
            throw new \RuntimeException('Missing document type or text');
        }
        
        $parser = $this->selectParser($docType);
        $parsed = $parser->parse($text);
        
        // Store in context
        $context->setParsedReceipt($parsed);
        $context->setMetadata('parser', [
            'engine' => get_class($parser),
            'confidence' => $parsed->confidence,
        ]);
        
        // Persist to DB
        $context->getEvidence()->update([
            'parser_engine' => get_class($parser),
            'parsed_data' => $parsed->toArray(),
        ]);
    }
}
```

### Resolver Stage
```php
class ResolverStage implements PipelineStage
{
    public function process(EvidenceContext $context): void
    {
        $parsed = $context->getParsedReceipt();
        
        if (!$parsed) {
            throw new \RuntimeException('No parsed receipt available');
        }
        
        $evidence = $context->getEvidence();
        $draft = $this->resolver->resolve($evidence->user_id, $parsed);
        
        // Store in context
        $context->setDraft($draft);
        
        // Persist to DB
        $evidence->update([
            'resolved_data' => $draft->toArray(),
            'status' => EvidenceStatus::Ready,
        ]);
    }
}
```

## Benefits

### 1. Clean Stage Interface
Stages don't need to know about database persistence — they just read/write to context:

```php
interface PipelineStage
{
    public function process(EvidenceContext $context): void;
}
```

### 2. Easy Testing
Mock context without database:

```php
$context = new EvidenceContext(
    evidence: $evidence,
    text: "QRIS\nMerchant: INDOMARET\n...",
);

$normalizeStage->process($context);

$this->assertNotNull($context->getNormalizedText());
```

### 3. Pipeline Flexibility
Pipeline orchestrator is simple:

```php
class EvidencePipeline
{
    public function process(EvidenceContext $context): void
    {
        foreach ($this->stages as $stage) {
            $stage->process($context);
        }
    }
}
```

### 4. Metadata Collection
Collect diagnostic info without polluting domain models:

```php
$context->setMetadata('ocr', ['engine' => 'OCRClient', 'duration_ms' => 1523]);
$context->setMetadata('parser', ['confidence' => 0.85]);
$context->setMetadata('resolver', ['ai_fallback_used' => true]);

// At the end, store diagnostics
$evidence->update([
    'processing_metadata' => $context->getMetadata(),
]);
```

## Context vs. Evidence Model

| Aspect | EvidenceContext | Evidence Model |
|--------|-----------------|----------------|
| **Lifespan** | Single pipeline run | Persistent (database) |
| **Purpose** | Temporary state holder | Long-term data storage |
| **Mutability** | Mutable (setters) | Mutable via `->update()` |
| **Scope** | Pipeline stages only | Entire application |
| **Performance** | In-memory, fast | DB read/write |

**Rule:** Context holds *transient* processing state. Evidence model holds *persistent* business data.

## Advanced Patterns

### Conditional Stages
```php
public function process(EvidenceContext $context): void
{
    if (!$context->hasParsedReceipt()) {
        // Skip this stage if parsing failed
        return;
    }
    
    $parsed = $context->getParsedReceipt();
    // ... continue processing
}
```

### Stage Dependencies
```php
class ResolverStage implements PipelineStage
{
    public function process(EvidenceContext $context): void
    {
        // Ensure dependencies are met
        if (!$context->hasDocumentType()) {
            throw new \RuntimeException('ResolverStage requires ClassificationStage');
        }
        
        if (!$context->hasParsedReceipt()) {
            throw new \RuntimeException('ResolverStage requires ParserStage');
        }
        
        // Safe to proceed
        $draft = $this->resolver->resolve($context);
        $context->setDraft($draft);
    }
}
```

### Rollback Support
```php
class EvidencePipeline
{
    public function process(EvidenceContext $context): void
    {
        $checkpoints = [];
        
        try {
            foreach ($this->stages as $stage) {
                $checkpoints[] = clone $context; // Save state before stage
                $stage->process($context);
            }
        } catch (\Throwable $e) {
            // Restore to last valid checkpoint
            $context = end($checkpoints);
            throw $e;
        }
    }
}
```

## See Also

- [Pipeline Architecture](./01-pipeline-architecture.md)
- [Pipeline Stages Implementation](../../app/Evidence/Pipeline/Stages/)
- [Evidence Model](../../app/Models/Evidence.php)
