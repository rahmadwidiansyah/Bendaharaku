<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\TransactionSource;
use App\Events\TransactionPosted;
use App\Services\AI\Memory\UserMemoryService;
use Illuminate\Support\Facades\Log;

class LearnFromTransaction
{
    private array $learnableSources = [
        TransactionSource::TELEGRAM->value,
        TransactionSource::WEB_CHAT->value,
        TransactionSource::OCR->value,
        TransactionSource::DRAFT->value,
    ];

    public function __construct(
        private readonly UserMemoryService $memoryService,
    ) {}

    public function handle(TransactionPosted $event): void
    {
        if (! in_array($event->source->value, $this->learnableSources, true)) {
            return;
        }

        $transaction = $event->transaction;

        // Memory HANYA dari keyword hasil LLM (prinsip: "yang dicatat cuma ingatan dari LLM").
        // Transaksi tanpa keyword LLM (Python/LRE/manual/OCR-review) tidak membentuk memory.
        if (empty($event->aiKeywords)) {
            Log::info('Memory learn skipped: no LLM keywords', [
                'user_id' => $transaction->user_id,
                'transaction_id' => $transaction->id,
                'source' => $event->source->value,
            ]);

            return;
        }

        $this->memoryService->learnFromKeywords(
            userId: $transaction->user_id,
            keywords: $event->aiKeywords,
            source: $event->source->value,
            transactionId: $transaction->id,
        );

        Log::info('Memory learn from transaction', [
            'user_id' => $transaction->user_id,
            'transaction_id' => $transaction->id,
            'source' => $event->source->value,
            'subject' => $transaction->subject,
            'category_id' => $transaction->category_id,
            'ai_keywords_count' => count($event->aiKeywords),
        ]);
    }
}
