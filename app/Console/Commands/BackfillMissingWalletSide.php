<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\WalletSide;
use App\Models\TransactionDraft;
use Illuminate\Console\Command;

class BackfillMissingWalletSide extends Command
{
    protected $signature = 'draft:backfill-missing-wallet-side
        {--dry-run : Jangan simpan, hanya tampilkan yang akan diubah}';

    protected $description = 'Isi missing_wallet_side untuk draft existing berdasarkan heuristic payload';

    private const string TYPE_INCOME = 'income';

    private const string TYPE_EXPENSE = 'expense';

    private const string TYPE_DEBT = 'debt';

    public function handle(): int
    {
        $drafts = TransactionDraft::whereNull('missing_wallet_side')->get();

        if ($drafts->isEmpty()) {
            $this->info('Tidak ada draft yang perlu di-backfill.');

            return Command::SUCCESS;
        }

        $this->info("Ditemukan {$drafts->count()} draft tanpa missing_wallet_side.");

        $updated = 0;
        $bar = $this->output->createProgressBar($drafts->count());
        $bar->start();

        foreach ($drafts as $draft) {
            $side = $this->determineSide($draft->payload);
            if ($side === null) {
                $bar->advance();

                continue;
            }

            if (! $this->option('dry-run')) {
                $draft->missing_wallet_side = $side->value;
                $draft->save();
            }

            $updated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->info("[DRY-RUN] {$updated} draft akan di-update.");
        } else {
            $this->info("{$updated} draft berhasil di-backfill.");
        }

        return Command::SUCCESS;
    }

    private function determineSide(array $payload): ?WalletSide
    {
        $typeKey = $payload['type_key'] ?? self::TYPE_EXPENSE;
        $sourceId = $payload['source_wallet_id'] ?? null;
        $destId = $payload['destination_wallet_id'] ?? null;
        $sourceName = $payload['source_wallet_name'] ?? '';
        $destName = $payload['destination_wallet_name'] ?? '';

        $sourceIsPlaceholder = $sourceId === null && $this->isExternalByName($sourceName);
        $destIsPlaceholder = $destId === null && $this->isExternalByName($destName);

        $sourceIsSet = $sourceId !== null && ! $sourceIsPlaceholder;
        $destIsSet = $destId !== null && ! $destIsPlaceholder;

        if ($sourceIsSet && $destIsSet) {
            return WalletSide::None;
        }

        if (! $sourceIsSet && ! $destIsSet) {
            if ($typeKey === self::TYPE_INCOME) {
                return WalletSide::Destination;
            }

            return WalletSide::Both;
        }

        if (! $sourceIsSet) {
            if ($typeKey === self::TYPE_INCOME) {
                return WalletSide::Destination;
            }

            return WalletSide::Source;
        }

        if (! $destIsSet) {
            if ($typeKey === self::TYPE_DEBT) {
                return WalletSide::Destination;
            }

            return WalletSide::Destination;
        }

        return WalletSide::None;
    }

    private function isExternalByName(string $name): bool
    {
        $lower = mb_strtolower(trim($name));

        return $lower === '' || str_contains($lower, 'external') || str_contains($lower, 'merchant');
    }
}
