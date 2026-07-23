<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AiTransactionLinked;
use Illuminate\Support\Facades\DB;

class LinkParseLogToTransaction
{
    public function handle(AiTransactionLinked $event): void
    {
        if ($event->parseLogId <= 0) {
            return;
        }

        DB::table('ai_parse_logs')
            ->where('id', $event->parseLogId)
            ->update([
                'transaction_log_id' => $event->transaction->id,
                'status' => $event->transaction->is_cleared ? 'posted' : 'draft',
                'updated_at' => now(),
            ]);
    }
}
