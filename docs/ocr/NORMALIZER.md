# OCR Text Normalizer

## Purpose

Membersihkan hasil OCR sebelum dipakai Classifier dan Parser.

## Position in Pipeline

```
OCR → Normalize → Classifier → Parser → Resolver
```

## Normalization Rules

### 1. Currency
```
Rpl  → Rp
Rp.  → Rp
RP   → Rp
IDR  → Rp
```

### 2. Wallet Names
```
Sea BanK  → SeaBank
Shopee Pay → ShopeePay
Go Pay    → GoPay
OV0       → OVO
Link Aja  → LinkAja
Dana      → DANA
```

### 3. Number Context
Karakter OCR yang salah di konteks angka:
```
O → 0 (hanya jika di sebelah digit)
l → 1
I → 1
S → 5
B → 8
```

### 4. Whitespace
- Double space → single space
- Tab → space
- Multiple newlines → max 2
- Space before period/comma → dihapus

### 5. Reference Numbers
```
ABC 123 456 → ABC123456
```

### 6. Noise Removal
Hapus karakter acak: `---`, `===`, `***`, `___`

### 7. Unicode
- Non-breaking space → regular space
- En/Em dash → hyphen
- Smart quotes → straight quotes

## Configuration

Semua rules dikonfigurasi di `config/ocr_normalizer.php`.

## Metrics

- `normalization_duration_ms`: Durasi normalisasi
- `normalization_changes`: Jumlah perubahan yang dilakukan
