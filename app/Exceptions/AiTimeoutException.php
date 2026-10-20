<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Dilempar ketika provider LLM timeout atau server sedang tidak tersedia (408/503/504).
 */
class AiTimeoutException extends Exception
{
    public function __construct(string $providerName, private readonly ?int $httpStatus = null)
    {
        parent::__construct($providerName);
    }

    /**
     * Status HTTP asli jika kegagalan berasal dari respons provider (bukan koneksi/timeout).
     */
    public function httpStatus(): ?int
    {
        return $this->httpStatus;
    }
}
