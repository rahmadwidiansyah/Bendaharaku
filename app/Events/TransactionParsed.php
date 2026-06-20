<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use App\DTO\AIParseResult;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionParsed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
        public string $inputText,
        public AIParseResult $parseResult
    ) {}
}