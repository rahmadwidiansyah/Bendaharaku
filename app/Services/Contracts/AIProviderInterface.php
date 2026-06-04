<?php

declare(strict_types=1);

namespace App\Services\AI\Contracts;

use App\DTO\AIParseResult;
use App\DTO\AiProviderRequest;

interface AIProviderInterface
{
    public function parseTransaction(AiProviderRequest $request): AIParseResult;
}