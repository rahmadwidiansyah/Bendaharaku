<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Dilempar ketika provider LLM mengembalikan HTTP 429 (Rate Limit / Quota Habis).
 */
class AiRateLimitException extends Exception
{
    public function __construct(string $providerName)
    {
        parent::__construct($providerName);
    }
}
