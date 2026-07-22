# Evidence Pipeline — Stage Reference

## EvidenceStage Contract

```php
interface EvidenceStage
{
    public function handle(EvidenceContext $context, Closure $next): void;
}
```

## EvidenceContext

| Property | Type | Description |
|----------|------|-------------|
| evidence | Evidence | Model evidence |
| ocrText | ?string | Teks mentah dari OCR |
| normalizedText | ?string | Teks setelah normalisasi |
| documentType | ?DocumentType | Jenis dokumen |
| parsedData | ?EvidenceData | Hasil parser |
| draft | ?TransactionDraft | Draft transaksi |
| warnings | array | Peringatan |
| metadata | array | Data tambahan |
| stageDurations | array | Durasi per stage (ms) |
| normalizationChanges | int | Jumlah perubahan normalisasi |

## Stages

### OCRStage
- **Input**: evidence (gambar dari storage)
- **Output**: context.ocrText
- **Service**: OCRClient (HTTP ke PaddleOCR)

### NormalizeStage
- **Input**: context.ocrText
- **Output**: context.normalizedText, context.normalizationChanges
- **Service**: OCRTextNormalizer

### ClassificationStage
- **Input**: evidence (normalized_text atau ocr_text)
- **Output**: context.documentType
- **Service**: DocumentClassifier

### ParsingStage
- **Input**: context.documentType
- **Output**: context.parsedData
- **Service**: TransferReceiptParser (dan parser lainnya)

### ResolveStage
- **Input**: context.parsedData
- **Output**: context.draft, context.warnings
- **Service**: EvidenceResolver
