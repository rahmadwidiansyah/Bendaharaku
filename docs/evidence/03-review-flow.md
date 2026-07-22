# Evidence Review Flow

## Overview

Review Flow adalah tahap akhir dari Evidence Pipeline di mana user dapat melihat, mengedit, dan mengonfirmasi draft transaksi sebelum di-commit menjadi transaksi final.

## Purpose

- **Human Verification:** User dapat memvalidasi data sebelum transaksi final dibuat
- **Error Correction:** User dapat mengedit field yang salah dideteksi (wallet, kategori, nominal)
- **Confidence Visibility:** User melihat confidence score dan warnings dari AI
- **Idempotency:** Commit dapat dipanggil ulang tanpa membuat transaksi duplikat

## Flow Diagram

```
Pipeline Complete (READY)
    ↓
[GET /evidence/{uuid}/draft] — Show draft to user
    ↓
User reviews data (optional edit)
    ↓
[PATCH /evidence/{uuid}/draft] — Update draft (optional)
    ↓
User clicks "Confirm"
    ↓
[POST /evidence/{uuid}/commit] — Create transaction
    ↓
Transaction Created (COMPLETED)
```

## API Endpoints

### 1. GET /api/chat/evidence/{uuid}/draft

**Purpose:** Retrieve draft for user review

**Controller:** `EvidenceReviewController@show`

**Request:**
```http
GET /api/chat/evidence/01HZ0XABC123/draft
Authorization: Bearer {token}
```

**Response (Success):**
```json
{
  "success": true,
  "evidence": {
    "uuid": "01HZ0XABC123",
    "status": "READY",
    "document_type": "QRIS_RECEIPT",
    "original_name": "receipt.png",
    "url": "https://storage.example.com/evidence/01HZ0XABC123.png"
  },
  "draft": {
    "transactionType": "EXPENSE",
    "walletId": 39,
    "walletName": "BCA",
    "categoryId": 137,
    "categoryName": "Belanja Dapur & Groceries",
    "merchantName": "Indomaret",
    "amount": 25000,
    "currency": "IDR",
    "description": null,
    "transactionDate": "2026-07-22T00:00:00",
    "referenceNumber": "123456789012",
    "destinationName": null,
    "destinationAccount": null,
    "destinationWalletId": null,
    "confidence": 0.83,
    "warnings": [],
    "metadata": {
      "wallet_source": {
        "wallet_id": 39,
        "wallet_name": "BCA",
        "confidence": 1.0,
        "match_method": "exact_name"
      },
      "category": {
        "category_id": 137,
        "category_name": "Belanja Dapur & Groceries",
        "confidence": 0.8
      },
      "merchant": {
        "merchant_name": "Indomaret",
        "merchant_category": "retail",
        "confidence": 0.85
      },
      "duplicate": {
        "is_duplicate": false,
        "confidence": 1.0,
        "warnings": []
      }
    },
    "resolved": true,
    "amountConfidence": 0.95,
    "walletConfidence": 1.0,
    "categoryConfidence": 0.8,
    "dateConfidence": 0.85,
    "referenceConfidence": 0.9
  }
}
```

**Response (Error — Not Ready):**
```json
{
  "success": false,
  "message": "Evidence belum selesai diproses."
}
```

**Response (Error — Not Found):**
```json
{
  "success": false,
  "message": "Evidence tidak ditemukan."
}
```

---

### 2. PATCH /api/chat/evidence/{uuid}/draft

**Purpose:** Update draft with user edits

**Controller:** `EvidenceReviewController@update`

**Request:**
```http
PATCH /api/chat/evidence/01HZ0XABC123/draft
Authorization: Bearer {token}
Content-Type: application/json

{
  "wallet_id": 40,
  "category_id": 138,
  "amount": 26000,
  "description": "Belanja bulanan",
  "transaction_date": "2026-07-22"
}
```

**Validation Rules:**
```php
[
    'transaction_type' => 'nullable|string|in:EXPENSE,INCOME,TRANSFER,INTERNAL_TRANSFER',
    'wallet_id' => 'nullable|integer|exists:wallets,id',
    'category_id' => 'nullable|integer|exists:categories,id',
    'amount' => 'nullable|numeric|min:0',
    'description' => 'nullable|string|max:500',
    'transaction_date' => 'nullable|string',
    'destination_name' => 'nullable|string|max:200',
    'destination_account' => 'nullable|string|max:50',
]
```

**Response (Success):**
```json
{
  "success": true,
  "draft": {
    "transactionType": "EXPENSE",
    "walletId": 40,
    "walletName": null,
    "categoryId": 138,
    "categoryName": null,
    "merchantName": "Indomaret",
    "amount": 26000,
    "currency": "IDR",
    "description": "Belanja bulanan",
    "transactionDate": "2026-07-22",
    "amountConfidence": 1.0,
    "walletConfidence": 1.0,
    "categoryConfidence": 1.0
  }
}
```

**Logic:**
- User-edited fields get **confidence = 1.0** (manual verification)
- Only provided fields are updated — others remain unchanged
- Draft is saved to `evidence.resolved_data` (JSON column)

---

### 3. POST /api/chat/evidence/{uuid}/commit

**Purpose:** Commit draft to create final transaction

**Controller:** `EvidenceReviewController@commit`

**Service:** `EvidenceCommitService`

**Request:**
```http
POST /api/chat/evidence/01HZ0XABC123/commit
Authorization: Bearer {token}
Content-Type: application/json

{
  "amount": 27000
}
```

**Optional Body:** Last-minute overrides (same fields as PATCH)

**Response (Success):**
```json
{
  "success": true,
  "transaction_id": 401,
  "status": "COMPLETED",
  "message": "Transaksi berhasil dibuat.",
  "transaction": {
    "id": 401,
    "reference_number": "OCR-01HZ0XDEF456",
    "date": "2026-07-22",
    "amount": 25000,
    "subject": "INDOMARET",
    "notes": "Ref: 123456789012 | [OCR: receipt.png]",
    "is_cleared": true,
    "category": {
      "id": 137,
      "category_name": "Belanja Dapur & Groceries"
    },
    "sourceWallet": {
      "id": 39,
      "name": "BCA"
    },
    "destinationWallet": {
      "id": 37,
      "name": "Merchant System"
    }
  },
  "warnings": []
}
```

**Response (Error — Validation Failed):**
```json
{
  "success": false,
  "message": "Gagal membuat transaksi: Transaksi gagal: Dompet asal dan dompet tujuan tidak boleh sama."
}
```

**Response (Error — Already Committed):**
```json
{
  "success": true,
  "transaction_id": 401,
  "status": "COMPLETED",
  "message": "Transaksi sudah dibuat sebelumnya."
}
```

---

## Commit Service Logic

### Service: `EvidenceCommitService`

**Location:** `app/Services/Evidence/EvidenceCommitService.php`

**Responsibility:**
- Apply user overrides to draft
- Map draft to `ProcessTransactionAction` format
- Handle wallet resolution based on transaction type
- Duplicate detection
- Create transaction via `ProcessTransactionAction::create()`
- Update evidence status to `COMPLETED`

### Commit Flow

```php
public function commit(Evidence $evidence, array $overrides = []): array
{
    // 1. Validate status
    if (!$evidence->isReady() && !$evidence->isResolved()) {
        return ['success' => false, 'message' => 'Evidence belum siap'];
    }
    
    // 2. Check idempotency (already committed?)
    if ($evidence->transaction_id !== null) {
        $existing = TransactionLog::find($evidence->transaction_id);
        if ($existing) {
            return [
                'success' => true,
                'transaction_id' => $evidence->transaction_id,
                'message' => 'Transaksi sudah dibuat sebelumnya.'
            ];
        }
    }
    
    // 3. Get draft data
    $draft = $evidence->resolved_data;
    
    // 4. Apply overrides
    $data = $this->applyOverrides($draft, $overrides);
    
    // 5. Duplicate check
    $duplicateCheck = $this->checkDuplicate($evidence->user_id, $data);
    
    // 6. Map to ProcessTransactionAction format
    $transactionData = $this->mapDraftToTransactionData($data, $evidence);
    
    // 7. Create transaction in DB transaction
    DB::transaction(function () use ($transactionData, $evidence) {
        $transaction = $this->transactionAction->create(
            data: $transactionData,
            userId: $evidence->user_id,
            sourcePrefix: 'OCR',
        );
        
        $evidence->update([
            'status' => EvidenceStatus::Completed,
            'transaction_id' => $transaction->id,
            'completed_at' => now(),
        ]);
        
        return $transaction;
    });
}
```

### Wallet Resolution by Transaction Type

**Critical Logic:** EXPENSE dan INCOME membutuhkan System Wallet sebagai counterpart

```php
private function mapDraftToTransactionData(TransactionDraft $draft, Evidence $evidence): array
{
    $sourceWalletId = $draft->walletId;
    $destinationWalletId = $draft->destinationWalletId;
    
    // EXPENSE: user wallet → Merchant System
    if ($draft->transactionType === 'EXPENSE') {
        $sourceWalletId = $draft->walletId;
        $destinationWalletId = $this->getSystemWalletId(
            $evidence->user_id,
            config('bendaharaku.system_wallets.merchant', 'Merchant System')
        );
    }
    
    // INCOME: External System → user wallet
    elseif ($draft->transactionType === 'INCOME') {
        $sourceWalletId = $this->getSystemWalletId(
            $evidence->user_id,
            config('bendaharaku.system_wallets.external', 'External System')
        );
        $destinationWalletId = $draft->walletId;
    }
    
    // TRANSFER: user wallet → user wallet
    elseif ($draft->transactionType === 'TRANSFER') {
        $sourceWalletId = $draft->walletId;
        $destinationWalletId = $draft->destinationWalletId
            ?? throw new \RuntimeException('Transfer requires destination wallet');
    }
    
    return [
        'transaction_type' => $draft->transactionType,
        'category_id' => $draft->categoryId,
        'source_wallet_id' => $sourceWalletId,
        'destination_wallet_id' => $destinationWalletId,
        'amount' => $draft->amount,
        'date' => $draft->transactionDate ?? now()->format('Y-m-d'),
        'subject' => $draft->destinationName ?? $draft->merchantName ?? '-',
        'notes' => $this->buildNotes($draft, $evidence),
        'is_cleared' => true,
    ];
}
```

**Why System Wallets?**

`ProcessTransactionAction` requires `source_wallet_id !== destination_wallet_id`. For single-wallet transactions (EXPENSE/INCOME), we use virtual System Wallets:

- **Merchant System** — Destination for all expense transactions
- **External System** — Source for all income transactions
- **System Hutang** — Counterpart for debt transactions
- **System Piutang** — Counterpart for receivable transactions

These wallets are auto-created when user registers (see `User::boot()`).

### Duplicate Detection

```php
private function checkDuplicate(int $userId, TransactionDraft $draft): array
{
    $warnings = [];
    
    // Check by reference number
    if ($draft->referenceNumber) {
        $existing = TransactionLog::where('user_id', $userId)
            ->where('reference_number', $draft->referenceNumber)
            ->where('is_cleared', true)
            ->exists();
        
        if ($existing) {
            $warnings[] = "Referensi {$draft->referenceNumber} sudah ada.";
        }
    }
    
    // Check by amount + wallet + date (±5 minutes)
    if ($draft->amount && $draft->walletId && $draft->transactionDate) {
        $date = Carbon::parse($draft->transactionDate);
        $existing = TransactionLog::where('user_id', $userId)
            ->where('amount', $draft->amount)
            ->where(fn($q) => $q->where('source_wallet_id', $draft->walletId)
                ->orWhere('destination_wallet_id', $draft->walletId))
            ->whereBetween('date', [$date->copy()->subMinutes(5), $date->copy()->addMinutes(5)])
            ->exists();
        
        if ($existing) {
            $warnings[] = 'Kemungkinan duplikat: nominal dan waktu yang sama.';
        }
    }
    
    return [
        'is_duplicate' => !empty($warnings),
        'warnings' => $warnings,
    ];
}
```

**Note:** Duplicate warnings are **non-blocking** — user can proceed if intentional.

---

## Frontend Integration

### Review Page (Vue Component)

```vue
<template>
  <div class="evidence-review">
    <h2>Review Transaksi dari Struk</h2>
    
    <!-- Evidence Image -->
    <img :src="evidence.url" alt="Receipt" />
    
    <!-- Draft Form -->
    <form @submit.prevent="handleCommit">
      <div class="field">
        <label>Dompet</label>
        <select v-model="draft.walletId">
          <option v-for="w in wallets" :value="w.id">{{ w.name }}</option>
        </select>
        <span class="confidence">Confidence: {{ draft.walletConfidence }}</span>
      </div>
      
      <div class="field">
        <label>Kategori</label>
        <select v-model="draft.categoryId">
          <option v-for="c in categories" :value="c.id">{{ c.category_name }}</option>
        </select>
        <span class="confidence">Confidence: {{ draft.categoryConfidence }}</span>
      </div>
      
      <div class="field">
        <label>Nominal</label>
        <input type="number" v-model.number="draft.amount" />
        <span class="confidence">Confidence: {{ draft.amountConfidence }}</span>
      </div>
      
      <div class="field">
        <label>Tanggal</label>
        <input type="date" v-model="draft.transactionDate" />
      </div>
      
      <div class="field">
        <label>Catatan (opsional)</label>
        <input type="text" v-model="draft.description" />
      </div>
      
      <!-- Warnings -->
      <div v-if="draft.warnings.length" class="warnings">
        <p v-for="w in draft.warnings">⚠️ {{ w }}</p>
      </div>
      
      <button type="submit" :disabled="loading">
        {{ loading ? 'Memproses...' : 'Konfirmasi & Buat Transaksi' }}
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps(['uuid']);
const evidence = ref(null);
const draft = ref(null);
const loading = ref(false);

onMounted(async () => {
  const response = await fetch(`/api/chat/evidence/${props.uuid}/draft`);
  const data = await response.json();
  evidence.value = data.evidence;
  draft.value = data.draft;
});

const handleCommit = async () => {
  loading.value = true;
  
  try {
    const response = await fetch(`/api/chat/evidence/${props.uuid}/commit`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        wallet_id: draft.value.walletId,
        category_id: draft.value.categoryId,
        amount: draft.value.amount,
        description: draft.value.description,
        transaction_date: draft.value.transactionDate,
      }),
    });
    
    const result = await response.json();
    
    if (result.success) {
      alert('Transaksi berhasil dibuat!');
      // Redirect to transaction detail
      window.location.href = `/transactions/${result.transaction_id}`;
    } else {
      alert('Error: ' + result.message);
    }
  } finally {
    loading.value = false;
  }
};
</script>
```

---

## Security & Validation

### Authorization
```php
// EvidenceReviewController
$evidence = Evidence::where('uuid', $uuid)
    ->where('user_id', $user->id) // ← Ensure ownership
    ->first();
```

**Rule:** User can only review their own evidences.

### Input Validation
```php
$validated = $request->validate([
    'wallet_id' => 'nullable|integer|exists:wallets,id',
    'category_id' => 'nullable|integer|exists:categories,id',
    'amount' => 'nullable|numeric|min:0',
]);

// Additional check: wallet/category must belong to user
$wallet = Wallet::where('id', $validated['wallet_id'])
    ->where('user_id', $user->id)
    ->firstOrFail();
```

### Idempotency
```php
// Commit is idempotent — safe to retry
if ($evidence->transaction_id !== null) {
    return ['success' => true, 'transaction_id' => $evidence->transaction_id];
}
```

**Benefit:** Network retries won't create duplicate transactions.

---

## Testing

### Feature Test

```php
public function test_commit_creates_transaction(): void
{
    $user = User::factory()->create();
    $evidence = Evidence::factory()->ready()->create(['user_id' => $user->id]);
    
    $response = $this->actingAs($user)
        ->postJson("/api/chat/evidence/{$evidence->uuid}/commit");
    
    $response->assertOk()
        ->assertJson(['success' => true]);
    
    $evidence->refresh();
    $this->assertNotNull($evidence->transaction_id);
    $this->assertEquals('COMPLETED', $evidence->status->value);
}

public function test_commit_is_idempotent(): void
{
    $user = User::factory()->create();
    $evidence = Evidence::factory()->ready()->create(['user_id' => $user->id]);
    
    // First commit
    $this->actingAs($user)
        ->postJson("/api/chat/evidence/{$evidence->uuid}/commit");
    
    $firstTxId = $evidence->fresh()->transaction_id;
    
    // Second commit (retry)
    $response = $this->actingAs($user)
        ->postJson("/api/chat/evidence/{$evidence->uuid}/commit");
    
    $response->assertOk();
    $secondTxId = $evidence->fresh()->transaction_id;
    
    $this->assertEquals($firstTxId, $secondTxId);
}
```

---

## Troubleshooting

### Error: "Dompet asal dan tujuan tidak boleh sama"

**Cause:** System wallet resolution failed for EXPENSE/INCOME

**Fix:** Ensure user has System Wallets created:
```php
// Check in Tinker
$user->wallets()->where('group_type', 'System')->get();

// Should return: Merchant System, External System, System Hutang, System Piutang
```

### Error: "Evidence belum siap untuk di-commit"

**Cause:** Evidence status is not `READY` or `RESOLVED`

**Fix:** Check pipeline status:
```php
$evidence->status; // Should be 'READY'
$evidence->resolved_data; // Should not be null
```

### Warning: "Kemungkinan duplikat"

**Cause:** Similar transaction found (same amount, wallet, time)

**Action:** User can review and decide:
- If duplicate: cancel commit
- If intentional (e.g., dua pembelian berturut-turut): proceed with commit

---

## See Also

- [Pipeline Architecture](./01-pipeline-architecture.md)
- [Evidence Context](./02-evidence-context.md)
- [Commit Service](../../app/Services/Evidence/EvidenceCommitService.php)
- [Review Controller](../../app/Http/Controllers/EvidenceReviewController.php)
