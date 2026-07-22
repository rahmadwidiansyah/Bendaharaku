<?php

declare(strict_types=1);

namespace App\DTO;

readonly class AiProviderRequest
{
    public function __construct(
        public string $text,
        public string $apiKey,
        public string $model,
        public array $wallets = [],
        public array $categories = [],
        public array $activeMemories = [] // Ditambahkan untuk menyambung pipeline
    ) {}
}
