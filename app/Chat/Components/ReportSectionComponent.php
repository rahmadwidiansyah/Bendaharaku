<?php

declare(strict_types=1);

namespace App\Chat\Components;

/**
 * Section header dengan emoji dan konten untuk laporan berkomparasi.
 * Digunakan untuk styling /laporan command response.
 */
readonly class ReportSectionComponent implements ChatComponentInterface
{
    public function __construct(
        public string $title,
        public string $emoji = '📊',
        public array  $items = [],
        public ?string $translationKey = null,
        public string $total = '',
        public int    $count = 0,
    ) {}

    public function type(): string
    {
        return 'report_section';
    }

    public function toArray(): array
    {
        return [
            'type'            => $this->type(),
            'title'           => $this->title,
            'emoji'           => $this->emoji,
            'items'           => $this->items,
            'translationKey'  => $this->translationKey,
            'total'           => $this->total,
            'count'           => $this->count,
        ];
    }
}
