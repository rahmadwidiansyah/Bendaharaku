<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UppercaseSubjectsCommand extends Command
{
    /**
     * Nama dan signature command.
     */
    protected $signature = 'transactions:uppercase-subjects
                            {--dry-run : Preview perubahan tanpa menyimpan ke database}';

    /**
     * Deskripsi command.
     */
    protected $description = 'Normalisasi kolom subject pada transaction_logs menjadi UPPERCASE semua';

    /**
     * Eksekusi command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        // Ambil semua transaksi yang punya subject valid (bukan null dan bukan '-')
        $rows = DB::table('transaction_logs')
            ->whereNotNull('subject')
            ->where('subject', '!=', '-')
            ->where('subject', '!=', '')
            ->get(['id', 'subject']);

        if ($rows->isEmpty()) {
            $this->info('Tidak ada data subject yang perlu diubah.');

            return self::SUCCESS;
        }

        // Pisahkan yang perlu diubah (yang belum uppercase)
        $toUpdate = $rows->filter(fn ($r) => $r->subject !== strtoupper($r->subject));

        if ($toUpdate->isEmpty()) {
            $this->info('Semua subject sudah dalam format UPPERCASE. Tidak ada yang perlu diubah.');

            return self::SUCCESS;
        }

        $this->info("Ditemukan {$toUpdate->count()} baris yang perlu diubah ke UPPERCASE.");

        if ($isDryRun) {
            $this->warn('[DRY RUN] Perubahan TIDAK disimpan. Preview:');
            $this->table(
                ['ID', 'Subject Lama', 'Subject Baru'],
                $toUpdate->map(fn ($r) => [$r->id, $r->subject, strtoupper($r->subject)])->toArray()
            );

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($toUpdate->count());
        $bar->start();

        DB::transaction(function () use ($toUpdate, $bar) {
            foreach ($toUpdate as $row) {
                DB::table('transaction_logs')
                    ->where('id', $row->id)
                    ->update(['subject' => strtoupper($row->subject)]);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("✅ Berhasil! {$toUpdate->count()} subject diubah ke UPPERCASE.");

        return self::SUCCESS;
    }
}
