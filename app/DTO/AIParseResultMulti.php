<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * DTO hasil parsing multi-transaksi dari LLM Provider.
 *
 * Berbeda dengan AIParseResult yang hanya membawa satu ParsedTransaction,
 * DTO ini membawa array ParsedTransaction[] untuk keperluan multi-transaksi.
 *
 * Format JSON dari LLM yang diharapkan:
 * {
 *   "transactions": [
 *     { ...ParsedTransaction fields... },
 *     { ...ParsedTransaction fields... }
 *   ]
 * }
 */
readonly class AIParseResultMulti
{
    /**
     * @param  bool  $success  Apakah parsing berhasil
     * @param  ParsedTransaction[]  $transactions  Array hasil parsing (kosong jika gagal)
     * @param  float  $confidence  Rata-rata confidence seluruh transaksi
     * @param  string|null  $error  Pesan error jika gagal
     * @param  array  $usage  Token usage { prompt, completion, total }
     * @param  string  $provider  Nama provider yang digunakan
     * @param  string  $model  Model yang digunakan
     */
    public function __construct(
        public bool $success,
        public array $transactions,
        public float $confidence,
        public ?string $error,
        public array $usage = [],
        public string $provider = 'system',
        public string $model = 'unknown',
    ) {}

    public static function failure(string $message, string $provider = 'system', string $model = 'unknown'): self
    {
        return new self(
            success: false,
            transactions: [],
            confidence: 0.0,
            error: $message,
            usage: [],
            provider: $provider,
            model: $model,
        );
    }

    /**
     * Jumlah transaksi yang berhasil diparsing.
     */
    public function count(): int
    {
        return count($this->transactions);
    }
}
