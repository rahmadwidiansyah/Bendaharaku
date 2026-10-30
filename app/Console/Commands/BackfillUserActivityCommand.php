<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Backfill metrik aktivitas untuk data lama.
 *
 * Sumber: transaction_logs (pemilik transaksi).
 * Command ini idempoten — aman dijalankan berulang.
 */
class BackfillUserActivityCommand extends Command
{
    protected $signature = 'activity:backfill {--chunk=500}';

    protected $description = 'Backfill users.last_active_at dan user_activity_daily dari log yang ada';

    public function handle(): int
    {
        if (! Schema::hasTable('user_activity_daily')) {
            $this->error('Tabel user_activity_daily belum ada. Jalankan migrate dulu.');

            return self::FAILURE;
        }

        $chunk = max(50, (int) $this->option('chunk'));
        $processed = 0;

        User::query()->orderBy('id')->chunkById($chunk, function ($users) use (&$processed) {
            foreach ($users as $user) {
                $transactionMax = Schema::hasTable('transaction_logs')
                    ? DB::table('transaction_logs')->where('user_id', $user->id)->max('created_at')
                    : null;

                if ($transactionMax && (! $user->last_active_at || $user->last_active_at->lt($transactionMax))) {
                    $user->forceFill(['last_active_at' => $transactionMax])->saveQuietly();
                }

                if (Schema::hasTable('transaction_logs')) {
                    $days = DB::table('transaction_logs')
                        ->where('user_id', $user->id)
                        ->selectRaw('date(created_at) as day, min(created_at) as first_seen, max(created_at) as last_seen')
                        ->groupBy('day')
                        ->get();

                    foreach ($days as $day) {
                        DB::table('user_activity_daily')->upsert(
                            [[
                                'user_id' => $user->id,
                                'activity_date' => $day->day,
                                'first_seen_at' => $day->first_seen,
                                'last_seen_at' => $day->last_seen,
                            ]],
                            ['user_id', 'activity_date'],
                            ['last_seen_at' => $day->last_seen],
                        );
                    }
                }

                $processed++;
            }
        });

        $this->info("Backfill selesai untuk {$processed} user.");

        return self::SUCCESS;
    }
}
