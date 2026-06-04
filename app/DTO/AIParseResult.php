<?php
declare(strict_types=1);
namespace App\DTO;

readonly class AIParseResult
{
    public function __construct(
        public bool $success,
        public float $confidence,
        public ?string $error = null,
        public ?ParsedTransaction $transaction = null,
        public array $usage = ['prompt' => 0, 'completion' => 0, 'total' => 0] // Tambahan untuk Usage Log
    ) {}

    public static function failure(string $errorMessage): self
    {
        return new self(
            success: false,
            confidence: 0.0,
            error: $errorMessage,
            transaction: null,
            usage: ['prompt' => 0, 'completion' => 0, 'total' => 0]
        );
    }
}