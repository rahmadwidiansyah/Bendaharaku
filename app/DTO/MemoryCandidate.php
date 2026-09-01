<?php

declare(strict_types=1);

namespace App\DTO;

readonly class MemoryCandidate
{
    public function __construct(
        public string $keyword,
        public string $targetType,
        public ?string $targetName = null,
    ) {}
}
