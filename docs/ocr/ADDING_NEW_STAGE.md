# Adding a New Stage

## Steps

### 1. Create Stage Class

```php
<?php

declare(strict_types=1);

namespace App\Evidence\Pipeline\Stages;

use App\Evidence\Pipeline\Contracts\EvidenceStage;
use App\Evidence\Pipeline\Context\EvidenceContext;
use Closure;

class MyNewStage implements EvidenceStage
{
    public function handle(EvidenceContext $context, Closure $next): void
    {
        // 1. Proses
        $result = $this->doSomething($context);

        // 2. Simpan ke context
        $context->metadata['my_result'] = $result;

        // 3. Record duration
        $context->recordStageDuration('MY_STAGE', $durationMs);

        // 4. Panggil stage berikutnya
        $next($context);
    }
}
```

### 2. Register in Pipeline

```php
// app/Evidence/Jobs/ProcessEvidenceJob.php
$pipeline->through([
    OCRStage::class,
    NormalizeStage::class,
    ClassificationStage::class,
    MyNewStage::class,  // ← Tambah di sini
    ParsingStage::class,
    ResolveStage::class,
])->process($context);
```

### 3. Add DB Columns (if needed)

```php
// Migration
Schema::table('evidences', function (Blueprint $table) {
    $table->text('my_field')->nullable()->after('previous_field');
});
```

### 4. Update Evidence Model

Tambahkan ke `$fillable` dan `$casts`.

## Rules

- Stage hanya boleh melakukan SATU proses
- Stage tidak boleh langsung memanggil stage lain (kecuali via `$next`)
- Gunakan dependency injection untuk service
- Simpan hasil ke `$context`, bukan ke database langsung (kecuali untuk audit fields)
