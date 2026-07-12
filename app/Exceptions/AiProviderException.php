<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Dilempar untuk error umum dari provider LLM (API key invalid, format response salah, dll).
 */
class AiProviderException extends Exception
{
    public function __construct(string $providerName, string $detail = '')
    {
        parent::__construct("{$providerName}: {$detail}");
    }
}
