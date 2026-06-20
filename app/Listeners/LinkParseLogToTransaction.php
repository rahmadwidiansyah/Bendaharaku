<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TransactionPosted;
use Illuminate\Support\Facades\DB;

class LinkParseLogToTransaction
{
    /**
     * Mengunci relasi antara entitas hasil parse dengan transaksi nyata di database.
     */
    public function handle(TransactionPosted $event): void
    {
        if ($event->parseLogId <= 0) {
            return;
        }

        DB::table('ai_parse_logs')
            ->where('id', $event->parseLogId)
            ->update([
                'transaction_log_id' => $event->transactionLog->id,
                'status' => $event->transactionLog->is_cleared ? 'posted' : 'draft',
                'updated_at' => now(),
            ]);
    }
}