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
        TransactionSource::WEB->value,
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

        if ($transaction->subject === '-' || blank($transaction->subject)) {
            return;
        }

        $this->memoryService->upsertMemory(
            userId: $transaction->user_id,
            correctedData: [
                'subject' => $transaction->subject,
                'category_id' => $transaction->category_id,
                'source_wallet_id' => $transaction->source_wallet_id,
            ],
            source: $event->source->value,
            transactionId: $transaction->id,
        );

        Log::info('Memory learn from transaction', [
            'user_id' => $transaction->user_id,
            'transaction_id' => $transaction->id,
            'source' => $event->source->value,
            'subject' => $transaction->subject,
            'category_id' => $transaction->category_id,
        ]);
    }
}
