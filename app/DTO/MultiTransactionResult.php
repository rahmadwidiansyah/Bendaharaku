<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\MultiTransactionErrorCode;
use App\Models\TransactionLog;

/**
 * DTO hasil akhir dari processMulti() di ChatTransactionOrchestrator.
 *
 * Struktur ini platform-agnostic — tidak mengandung Telegram markup,
 * HTML, atau format presentasi apapun. Formatter masing-masing platform
 * (TelegramMultiTransactionFormatter, dll) yang bertanggung jawab
 * mengubah DTO ini ke string yang sesuai.
 *
 * Setiap item dalam $results mempertahankan urutan asli input dari user.
 *
 * Contoh serialisasi ke array:
 * {
 *   "results": [
 *     { "index": 1, "status": "success", "transaction": {...} },
 *     { "index": 2, "status": "failed",  "raw": "...", "errorCode": "WALLET_NOT_FOUND", "reason": "..." }
 *   ],
 *   "summary": { "total": 4, "success": 3, "failed": 1 }
 * }
 */
readonly class MultiTransactionResult
{
    /**
     * @param MultiTransactionItem[] $results  Ordered list, urutan = urutan input user
     * @param string                 $provider Nama AI provider yang dipakai (untuk info di UI)
     * @param string                 $model    Nama model yang dipakai
     * @param float                  $confidence Rata-rata confidence LLM
     */
    public function __construct(
        public array  $results,
        public string $provider,
        public string $model,
        public float  $confidence,
    ) {}

    // ── Computed helpers ──────────────────────────────────────────

    public function successCount(): int
    {
        return count(array_filter($this->results, fn (MultiTransactionItem $i) => $i->isSuccess()));
    }

    public function failedCount(): int
    {
        return count(array_filter($this->results, fn (MultiTransactionItem $i) => $i->isFailed()));
    }

    public function totalCount(): int
    {
        return count($this->results);
    }

    public function hasAnySuccess(): bool
    {
        return $this->successCount() > 0;
    }

    public function allFailed(): bool
    {
        return $this->failedCount() === $this->totalCount();
    }

    public function allSuccess(): bool
    {
        return $this->successCount() === $this->totalCount();
    }

    /**
     * Kembalikan semua item sukses saja (untuk event, logging, dll).
     *
     * @return MultiTransactionItem[]
     */
    public function successItems(): array
    {
        return array_values(array_filter($this->results, fn (MultiTransactionItem $i) => $i->isSuccess()));
    }

    /**
     * Kembalikan semua item gagal saja (untuk logging, dll).
     *
     * @return MultiTransactionItem[]
     */
    public function failedItems(): array
    {
        return array_values(array_filter($this->results, fn (MultiTransactionItem $i) => $i->isFailed()));
    }

    /**
     * Summary untuk response array / JSON.
     */
    public function summary(): array
    {
        return [
            'total'   => $this->totalCount(),
            'success' => $this->successCount(),
            'failed'  => $this->failedCount(),
        ];
    }

    /**
     * Serialize seluruh DTO ke array (untuk response controller / logging).
     */
    public function toArray(): array
    {
        return [
            'results'    => array_map(fn (MultiTransactionItem $i) => $i->toArray(), $this->results),
            'summary'    => $this->summary(),
            'provider'   => $this->provider,
            'model'      => $this->model,
            'confidence' => $this->confidence,
        ];
    }
}
