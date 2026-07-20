<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Thrown ketika API LLM menolak request karena:
 * - Request token count melebihi max input token limit
 * - Prompt + response terlalu besar
 *
 * HTTP status: 400 (bad request)
 * Recoverable: true (user bisa kirim pesan lebih pendek)
 */
class AiTokenLimitException extends \Exception
{
    public function __construct(
        private string $provider = 'Unknown',
        private int $estimatedTokens = 0,
        string $message = '',
    ) {
        parent::__construct($message ?: "Token limit exceeded for {$provider}");
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getEstimatedTokens(): int
    {
        return $this->estimatedTokens;
    }
}
