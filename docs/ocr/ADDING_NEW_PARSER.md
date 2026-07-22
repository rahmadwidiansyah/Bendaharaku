# Adding a New Parser

## Overview

Parser mengubah teks OCR (normalized) menjadi `EvidenceData` DTO.

## Steps

### 1. Create Parser Class

```php
<?php

declare(strict_types=1);

namespace App\Evidence\Parsers;

use App\Enums\DocumentType;
use App\Evidence\DTO\EvidenceData;
use App\Models\Evidence;

class ShoppingReceiptParser
{
    public function parse(Evidence $evidence): EvidenceData
    {
        // Gunakan normalized_text, bukan raw ocr_text
        $text = $evidence->normalized_text ?? $evidence->ocr_text ?? '';

        // Ekstrak field menggunakan extractor
        $amount = $this->extractAmount($text);
        $store = $this->extractStore($text);

        return new EvidenceData(
            documentType: DocumentType::ShoppingReceipt,
            rawText: $text,
            merchantName: $store,
            amount: $amount,
            // ...
        );
    }
}
```

### 2. Register in ParsingStage

```php
// app/Evidence/Pipeline/Stages/ParsingStage.php
$parsedData = match ($context->documentType) {
    DocumentType::TransferReceipt => $this->parseTransferReceipt($context),
    DocumentType::ShoppingReceipt => $this->parseShoppingReceipt($context),
    // ...
};
```

### 3. Add Keywords to Classifier

```php
// config/classifier.php
'keywords' => [
    'SHOPPING_RECEIPT' => ['belanja', 'toko', 'struk', ...],
],
```

### 4. Write Tests

```php
// tests/Feature/Services/ShoppingReceiptParserTest.php
```

## Rules

- Parser hanya mengekstrak, tidak melakukan lookup DB
- Gunakan normalized_text sebagai input
- Raw OCR tetap disimpan di evidence.ocr_text
- Semua field nullable kecuali documentType dan rawText
