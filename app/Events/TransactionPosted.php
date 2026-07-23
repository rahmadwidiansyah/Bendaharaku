<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\TransactionSource;
use App\Models\TransactionLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionPosted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public TransactionLog $transaction,
        public TransactionSource $source = TransactionSource::SYSTEM,
    ) {}
}
