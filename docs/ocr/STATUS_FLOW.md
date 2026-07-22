# Evidence Status Flow

## Lifecycle

```
UPLOADED
  ↓ (user upload)
QUEUED
  ↓ (job dispatch)
PROCESSING
  ↓ (OCR complete)
OCR_COMPLETED
  ↓ (classification complete)
CLASSIFIED
  ↓ (parsing complete)
PARSED
  ↓ (resolution complete)
RESOLVED
  ↓ (user review)
READY
  ↓ (user commit)
COMPLETED

Any → FAILED (on error)
```

## Status Definitions

| Status | Deskripsi | Terminal? |
|--------|-----------|-----------|
| UPLOADED | File sudah di-upload | No |
| QUEUED | Menunggu diproses | No |
| PROCESSING | Sedang diproses pipeline | No |
| OCR_COMPLETED | OCR selesai | No |
| CLASSIFIED | Klasifikasi selesai | No |
| PARSED | Parsing selesai | No |
| RESOLVED | Resolver selesai | No |
| READY | Siap di-review user | No |
| COMPLETED | Transaksi sudah dibuat | Yes |
| FAILED | Gagal diproses | Yes |

## Status Colors (UI)

| Status | Color |
|--------|-------|
| UPLOADED | emerald |
| QUEUED | blue |
| PROCESSING | amber |
| OCR_COMPLETED | cyan |
| CLASSIFIED | indigo |
| PARSED | violet |
| RESOLVED | fuchsia |
| READY | green |
| COMPLETED | emerald |
| FAILED | red |
