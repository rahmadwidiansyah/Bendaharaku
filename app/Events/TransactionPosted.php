<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\TransactionLog;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionPosted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
        public TransactionLog $transactionLog,
        public int $parseLogId
    ) {}
}
