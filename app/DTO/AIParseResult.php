<?php

declare(strict_types=1);

namespace App\DTO;

readonly class AIParseResult
{
    public function __construct(
        public bool $success,
        public float $confidence,
        public ?string $error,
        public ?ParsedTransaction $transaction,
        public array $usage = [],
        public string $provider = 'system', // Wajib diisi oleh Provider
        public string $model = 'unknown'    // Wajib diisi oleh Provider
    ) {}

    public static function failure(string $message, string $provider = 'system', string $model = 'unknown'): self
    {
        return new self(false, 0.0, $message, null, [], $provider, $model);
    }
}