# Shopping Receipt Parser

Parser untuk dokumen jenis `SHOPPING_RECEIPT`. Mengekstrak informasi dari struk belanja (minimarket, supermarket, restoran, apotek, dll.).

## Supported Receipt

### Retail / Minimarket
- Indomaret
- Alfamart
- Alfamidi
- Super Indo
- Hypermart
- Transmart
- Lawson
- FamilyMart

### Restaurant / Cafe
- McDonald's
- KFC
- Starbucks
- Mixue

### Pharmacy
- Apotek K24
- Guardian
- Watsons

Parser tetap generik — merchant lain dapat diproses dengan menambahkan alias ke config.

## Cara Kerja

```
OCR Text → MerchantExtractor → SummaryExtractor → ItemExtractor
         → PaymentMethodExtractor → ReceiptInfoExtractor → DateExtractor
         ↓
      EvidenceData (merchant, total, items, payment_method, dll)
         ↓
      EvidenceResolver → TransactionDraft
```

### Flow Pipeline

1. **ClassificationStage** mendeteksi document type = `SHOPPING_RECEIPT` berdasarkan keywords di `config/classifier.php`
2. **ParsingStage** merutekan ke `ShoppingReceiptParser` berdasarkan document type
3. **ShoppingReceiptParser** mengekstrak semua field menggunakan extractor
4. **ResolveStage** mengubah `EvidenceData` menjadi `TransactionDraft`

## Extractors

| Extractor | Field Output | Confidence |
|-----------|-------------|------------|
| `MerchantExtractor` | `merchant_name` | 0.95 (matched) / 0.6 (fallback) |
| `SummaryExtractor` | `total`, `subtotal`, `tax`, `discount`, `service_charge` | 0.9 |
| `ItemExtractor` | `items[]` (ReceiptItem[]) | 0.85 |
| `PaymentMethodExtractor` | `payment_method` | 0.9 |
| `ReceiptInfoExtractor` | `receipt_number`, `cashier` | 0.75-0.8 |
| `DateExtractor` | `transaction_time` | 0.85 |

## ReceiptItem DTO

```php
readonly class ReceiptItem {
    public string $name;      // Nama produk
    public int $qty;          // Jumlah
    public float $unitPrice;  // Harga satuan
    public ?float $discount;  // Diskon per item
    public float $total;      // Total harga item
    public float $confidence; // Confidence level
}
```

## Menambah Merchant Baru

Edit `config/shopping_parser.php`:

```php
'merchant_aliases' => [
    'Nama Merchant' => ['varian1', 'varian2', 'VARIAN3'],
],
```

### Menambah ke kategori tertentu

```php
// Restaurant (default category: Makan & Minum)
'restaurant_merchants' => ['Nama Resto'],

// Retail (default category: Belanja)
'retail_merchants' => ['Nama Toko'],

// Pharmacy (default category: Kesehatan)
'pharmacy_merchants' => ['Nama Apotek'],
```

## Menambah Regex Baru

### Item Pattern

Edit `config/shopping_parser.php` → `item_patterns`:

```php
'item_patterns' => [
    // Pattern baru: [name] [qty]x[harga] [total]
    '/^(.+?)\s+(\d+)\s*x\s*([\d.,]+)\s+([\d.,]+)$/i',
],
```

### Amount Pattern

```php
'amount_patterns' => [
    '/(?:total|jumlah)[:\s]*([\d.,]+)/i',
],
```

### Summary Line Pattern

```php
'summary_line_patterns' => [
    '/^nama\s*baris/i',
],
```

## Confidence Scoring

| Field | Source | Weight |
|-------|--------|--------|
| Merchant | MerchantExtractor | 0.95 |
| Total | SummaryExtractor | 0.90 |
| Items | ItemExtractor | 0.85 |
| Payment | PaymentMethodExtractor | 0.90 |
| Date | DateExtractor | 0.85 |
| Receipt Info | ReceiptInfoExtractor | 0.75 |

Overall confidence = average dari semua non-zero confidences.

## Merchant Category → Category Mapping

| Merchant Category | Default Category | Confidence |
|-------------------|-----------------|------------|
| `restaurant` | Makan & Minum | 0.85 |
| `retail` | Belanja | 0.85 |
| `pharmacy` | Kesehatan | 0.85 |

Mapping dilakukan oleh `MerchantResolver` → `CategoryResolver`.

## Unit Tests

```bash
php artisan test --filter=ShoppingReceiptParserTest
php artisan test --filter=MerchantExtractorTest
php artisan test --filter=ItemExtractorTest
php artisan test --filter=SummaryExtractorTest
php artisan test --filter=PaymentMethodExtractorTest
php artisan test --filter=ReceiptItemTest
```

## File Structure

```
app/
├── Evidence/
│   ├── DTO/
│   │   ├── EvidenceData.php          # DTO dengan shopping fields
│   │   ├── ReceiptItem.php           # DTO untuk item struk
│   │   └── TransactionDraft.php
│   ├── Parsers/
│   │   ├── ShoppingReceiptParser.php # Parser utama
│   │   ├── TransferReceiptParser.php # Parser transfer (existing)
│   │   └── Extractors/
│   │       ├── MerchantExtractor.php   # Ekstrak nama merchant
│   │       ├── ItemExtractor.php       # Ekstrak item/baris
│   │       ├── SummaryExtractor.php    # Ekstrak total/subtotal/pajak
│   │       ├── PaymentMethodExtractor.php # Ekstrak metode bayar
│   │       ├── ReceiptInfoExtractor.php   # Ekstrak no struk/kasir
│   │       ├── AmountExtractor.php     # Shared
│   │       └── DateExtractor.php       # Shared
│   ├── Pipeline/
│   │   └── Stages/
│   │       └── ParsingStage.php        # Route ke parser
│   └── Resolver/
│       ├── MerchantResolver.php        # Match merchant + detect category
│       └── CategoryResolver.php        # Match category dari merchant
config/
├── shopping_parser.php               # Konfigurasi parser
└── classifier.php                    # Keyword classifier (existing)
```
