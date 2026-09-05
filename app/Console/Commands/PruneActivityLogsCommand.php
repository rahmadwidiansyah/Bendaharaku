<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneActivityLogsCommand extends Command
{
    protected $signature = 'activity:prune {--days=7 : Retensi hari}';

    protected $description = 'Hapus activity logs lebih dari N hari (default 7 hari)';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $tables = [
            'user_activity_logs' => 'created_at',
            'user_settings_changes' => 'changed_at',
            'user_ai_memory_logs' => 'created_at',
            // ai_parse_logs dan chat_messages dipertahankan lebih lama untuk analitik, tapi ikut prune jika mau
            // 'ai_parse_logs' => 'created_at',
            // 'chat_messages' => 'created_at',
        ];

        foreach ($tables as $table => $col) {
            try {
                $deleted = DB::table($table)->where($col, '<', $cutoff)->delete();
                $this->info("Pruned {$deleted} rows from {$table} (< {$cutoff->toDateTimeString()})");
            } catch (\Throwable $e) {
                $this->warn("Failed prunes {$table}: ".$e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
