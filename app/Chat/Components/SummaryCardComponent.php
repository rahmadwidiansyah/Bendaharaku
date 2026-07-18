<?php

declare(strict_types=1);

namespace App\Chat\Components;

/**
 * Ringkasan hasil batch multi-transaction.
 *
 * Formatter menggunakan data ini untuk render header/footer
 * seperti "✅ 3 berhasil · ❌ 1 gagal".
 *
 * Data sudah numerik — Formatter yang memformat angka
 * dan memilih translation key yang tepat.
 */
readonly class SummaryCardComponent implements ChatComponentInterface
{
    public function __construct(
        public int   $total,
        public int   $success,
        public int   $failed,
        public float $confidence = 0.0,
    ) {}

    public function type(): string
    {
        return 'summary_card';
    }

    public function allSuccess(): bool
    {
        return $this->success === $this->total && $this->failed === 0;
    }

    public function allFailed(): bool
    {
        return $this->failed === $this->total && $this->success === 0;
    }

    public function isPartial(): bool
    {
        return !$this->allSuccess() && !$this->allFailed();
    }

    public function toArray(): array
    {
        return [
            'type'       => $this->type(),
            'total'      => $this->total,
            'success'    => $this->success,
            'failed'     => $this->failed,
            'confidence' => $this->confidence,
        ];
    }
}
