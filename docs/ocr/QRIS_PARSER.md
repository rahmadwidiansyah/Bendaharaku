# QRIS Receipt Parser

## Flow Parser

```
OCR Text
  ↓
QRISReceiptParser::parse()
  ├── MerchantExtractor (label-based + alias matching)
  ├── AmountExtractor  (reuse dari shared)
  ├── WalletExtractor  (reuse dari shared)
  ├── ReferenceExtractor (reuse dari shared)
  ├── DateExtractor    (reuse dari shared)
  ├── TimeExtractor    (QRIS-specific)
  ├── IssuerExtractor  (QRIS-specific)
  ├── AcquirerExtractor (QRIS-specific)
  ├── StatusExtractor  (QRIS-specific)
  ├── TerminalIdExtractor (QRIS-specific)
  └── Compute overall confidence
  ↓
EvidenceData DTO
  ↓
Resolver → Review → Commit
```

## Pipeline Integration

Parser dijalankan oleh `ParsingStage` jika `document_type == QRIS_RECEIPT`:

```php
DocumentType::QrisReceipt => $this->parseQrisReceipt($context),
```

Trigger ada di `app/Evidence/Pipeline/Stages/ParsingStage.php`.

## Supported Format

QRIS receipt with label-value pairs:

```
QRIS

PEMBAYARAN BERHASIL

Merchant
MIXUE PANDANARAN

Nominal
Rp25.000

Metode
SeaBank

Ref
20260722123456

Tanggal
22/07/2026

Jam
14:33
```

Label yang didukung (dari `config/qris_parser.php`):

| Label | Field |
|-------|-------|
| Merchant, Pedagang | `merchantName` |
| Nominal, Jumlah, Total | `amount` |
| Metode, Pembayaran | `walletName` |
| Ref, Referensi, RRN | `referenceNumber` |
| Tanggal, Tgl | `date` |
| Jam, Waktu | `time` |
| Status | `transactionStatus` |
| Issuer, Penerbit | `issuer` |
| Acquirer, Akuisitor | `acquirer` |
| NMID, Terminal, MID | `terminalId` |

## Cara Menambah Merchant

Edit `config/qris_parser.php`:

```php
'merchant_aliases' => [
    'NamaMerchant' => ['ALIAS', 'alias', 'variasi'],
],
```

Tambahkan juga kategori merchant:

```php
'merchant_categories' => [
    'NamaKategori' => [
        'keyword1', 'keyword2',
    ],
],
```

## Cara Menambah Wallet

Wallet sudah didukung oleh `WalletExtractor` (shared). Untuk menambah wallet baru:

Edit `config/ocr_normalizer.php`:

```php
'wallet_aliases' => [
    'NamaWallet' => ['Alias1', 'ALIAS2', 'alias3'],
],
```

Atau untuk QRIS-specific: edit `config/qris_parser.php`:

```php
'wallet_aliases' => [
    'NamaWallet' => ['Alias1', 'ALIAS2'],
],
```

## Cara Menambah Regex Reference

Edit `config/qris_parser.php`:

```php
'reference_patterns' => [
    '/ref[:\s]*(\d{12,20})/i',
    '/pattern baru[:\s]*(\d+)/i',
],
```

## Cara Debugging Parser

### 1. Cek log pipeline

```bash
docker compose exec app tail -f storage/logs/laravel.log | grep "QRIS"
```

Log yang dihasilkan:
- `QRIS parsing started`
- `QRIS merchant found`
- `QRIS wallet found`
- `QRIS amount found`
- `QRIS parsing finished`

### 2. Test parser langsung

```bash
docker compose exec app php artisan tinker
```

```php
$text = "QRIS\nPEMBAYARAN BERHASIL\nMerchant\nMIXUE\nNominal\nRp25.000\nMetode\nGoPay\nRef\n20260722123456\nTanggal\n22/07/2026\nJam\n14:33";
$evidence = App\Models\Evidence::find(1);
$evidence->normalized_text = $text;
$parser = new App\Evidence\Parsers\QrisReceiptParser;
$result = $parser->parse($evidence);
print_r($result->toArray());
```

### 3. Jalankan unit test

```bash
./vendor/bin/phpunit tests/Unit/QrisReceiptParserTest.php
```

## Konfigurasi

Semua konfigurasi ada di `config/qris_parser.php`:

| Key | Deskripsi |
|-----|-----------|
| `merchant_aliases` | Mapping alias ke canonical merchant name |
| `merchant_categories` | Mapping kategori berdasarkan keyword merchant |
| `wallet_aliases` | Mapping alias wallet |
| `issuer_aliases` | Mapping alias issuer bank |
| `acquirer_aliases` | Mapping alias acquirer |
| `status_keywords` | Keywords untuk status transaksi |
| `reference_patterns` | Regex untuk nomor referensi |
| `time_patterns` | Regex untuk jam transaksi |
| `label_mappings` | Mapping label OCR → field parser |

## Architecture Notes

- Parser mengikuti pola yang sama dengan `TransferReceiptParser` dan `ShoppingReceiptParser`
- Menggunakan extractor dari `App\Evidence\Parsers\Extractors\*`
- Reuse `AmountExtractor`, `ReferenceExtractor`, `DateExtractor`, `WalletExtractor` dari shared
- Extractors baru: `TimeExtractor`, `IssuerExtractor`, `AcquirerExtractor`, `StatusExtractor`, `TerminalIdExtractor`
- Resolver menggunakan `EvidenceResolver` yang sudah ada (WalletResolver, MerchantResolver, CategoryResolver, DuplicateResolver)
- DTO EvidenceData telah ditambah field: `merchantCity`, `terminalId`, `issuer`, `acquirer`, `transactionStatus`, `date`, `time`
