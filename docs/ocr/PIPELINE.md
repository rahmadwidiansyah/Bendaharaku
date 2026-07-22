# Pipeline Flow

## Status Lifecycle

```
UPLOADED → QUEUED → PROCESSING → OCR_COMPLETED → CLASSIFIED → PARSED → RESOLVED → READY → COMPLETED
                                                       ↘ FAILED
```

## Stage Mapping

| Stage | DB Status | Input | Output |
|-------|-----------|-------|--------|
| OCRStage | PROCESSING → OCR_COMPLETED | gambar | ocr_text |
| NormalizeStage | (tidak mengubah status) | ocr_text | normalized_text |
| ClassificationStage | OCR_COMPLETED → CLASSIFIED | normalized_text | document_type |
| ParsingStage | CLASSIFIED → PARSED | normalized_text | parsed_data (EvidenceData) |
| ResolveStage | PARSED → RESOLVED | parsed_data | resolved_data (TransactionDraft) |

## Database Fields

| Field | Stage yang mengisi | Deskripsi |
|-------|-------------------|-----------|
| ocr_text | OCRStage | Teks mentah dari OCR |
| normalized_text | NormalizeStage | Teks setelah normalisasi |
| normalization_duration_ms | NormalizeStage | Durasi normalisasi |
| normalization_changes | NormalizeStage | Jumlah perubahan |
| document_type | ClassificationStage | Jenis dokumen |
| parsed_data | ParsingStage | Hasil ekstraksi parser |
| resolved_data | ResolveStage | Draft transaksi |

## Pipeline Resume

Jika pipeline gagal di stage tertentu, pipeline dapat di-restart dari stage terakhir yang berhasil berdasarkan `evidence_processing_logs`.
