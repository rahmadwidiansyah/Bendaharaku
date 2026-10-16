<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * Metadata routing untuk observability (tidak disimpan utuh ke DB,
 * hanya ringkasannya: trace_id, selected_*, attempt_count, fallback_*).
 */
readonly class AIParseResultRouting
{
    public function __construct(
        public string $traceId = '',
        public string $selectedProvider = '',
        public int $selectedAccountId = 0,
        public string $selectedModel = '',
        public string $selectionMethod = '',
        public int $attemptCount = 0,
        public ?string $fallbackFrom = null,
        public ?string $fallbackTo = null,
        public ?string $fallbackReason = null,
        public int $latencyMs = 0,
        public array $attempts = [],
        public string $finalStatus = 'success',
    ) {}

    public function toLogArray(): array
    {
        return [
            'trace_id' => $this->traceId,
            'selected_provider' => $this->selectedProvider,
            'selected_account_id' => $this->selectedAccountId,
            'selected_model' => $this->selectedModel,
            'selection_method' => $this->selectionMethod,
            'attempt_count' => $this->attemptCount,
            'fallback_from' => $this->fallbackFrom,
            'fallback_to' => $this->fallbackTo,
            'fallback_reason' => $this->fallbackReason,
            'latency_ms' => $this->latencyMs,
            'final_status' => $this->finalStatus,
            // attempts detail hanya di debug log, bukan di DB.
            'attempts' => array_map(fn ($a) => [
                'provider' => $a['provider'] ?? null,
                'account_id' => $a['account_id'] ?? null,
                'model' => $a['model'] ?? null,
                'category' => $a['category'] ?? null,
                'latency_ms' => $a['latency_ms'] ?? null,
            ], $this->attempts),
        ];
    }
}
