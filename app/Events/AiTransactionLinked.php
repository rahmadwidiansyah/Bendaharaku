<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\TransactionLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AiTransactionLinked
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public TransactionLog $transaction,
        public int $parseLogId
    ) {}
}
