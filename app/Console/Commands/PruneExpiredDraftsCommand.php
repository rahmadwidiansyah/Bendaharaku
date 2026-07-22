<?php

namespace App\Console\Commands;

use App\Models\TransactionDraft;
use Illuminate\Console\Command;

class PruneExpiredDraftsCommand extends Command
{
    protected $signature = 'drafts:prune {--days=1 : Hapus draft expired lebih dari N hari}';

    protected $description = 'Bersihkan draft transaksi yang sudah kedaluwarsa';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $deleted = TransactionDraft::where('status', TransactionDraft::STATUS_PENDING)
            ->where('expires_at', '<', now()->subDays($days))
            ->update(['status' => TransactionDraft::STATUS_EXPIRED]);

        // Hapus yang expired lebih dari 7 hari
        $purged = TransactionDraft::whereIn('status', [
            TransactionDraft::STATUS_CANCELLED,
            TransactionDraft::STATUS_EXPIRED,
        ])
            ->where('updated_at', '<', now()->subDays(7))
            ->delete();

        $this->info("Marked expired: {$deleted}, Purged old: {$purged}");

        return Command::SUCCESS;
    }
}
