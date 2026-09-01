<?php

declare(strict_types=1);

namespace App\DTO;

readonly class KeywordResolverResult
{
    public function __construct(
        public ?int $targetId,
        public ?string $targetName,
        public string $matchedBy,
        public ?string $matchedKeyword = null,
    ) {}

    public static function notFound(): self
    {
        return new self(null, null, 'none');
    }

    public function isResolved(): bool
    {
        return $this->targetId !== null;
    }
}
