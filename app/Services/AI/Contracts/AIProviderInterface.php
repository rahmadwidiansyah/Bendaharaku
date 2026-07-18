<?php

declare(strict_types=1);

namespace App\Services\AI\Contracts;

use App\DTO\AIParseResult;
use App\DTO\AIParseResultMulti;
use App\DTO\AiProviderRequest;

interface AIProviderInterface
{
    /**
     * Parse satu transaksi dari teks (digunakan sebagai fallback LLM untuk single transaction).
     */
    public function parseTransaction(AiProviderRequest $request): AIParseResult;

    /**
     * Parse banyak transaksi dari satu teks.
     * Mengembalikan AIParseResultMulti yang berisi array ParsedTransaction[].
     *
     * Format JSON yang diharapkan dari LLM:
     * { "transactions": [ {...}, {...} ] }
     */
    public function parseMultiTransaction(AiProviderRequest $request): AIParseResultMulti;
}
