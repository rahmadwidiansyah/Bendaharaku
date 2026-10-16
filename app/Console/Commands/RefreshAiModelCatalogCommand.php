<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AiProviderAccount;
use App\Services\AI\Discovery\ModelDiscoveryService;
use Illuminate\Console\Command;

class RefreshAiModelCatalogCommand extends Command
{
    protected $signature = 'ai:discover-models {--force : Paksa refresh meski cache masih segar} {--user= : Hanya untuk user ID tertentu}';

    protected $description = 'Refresh katalog model AI per akun (discovery Gemini/OpenAI/DeepSeek/compatible, last-known-good).';

    public function handle(ModelDiscoveryService $discovery): int
    {
        $query = AiProviderAccount::where('is_active', true)->where('is_valid', true);
        if ($this->option('user')) {
            $query->where('user_id', (int) $this->option('user'));
        }
        $accounts = $query->get();
        $force = (bool) $this->option('force');
        $ok = 0;
        $fail = 0;
        foreach ($accounts as $account) {
            try {
                $result = $discovery->discoverForAccount($account, $force);
                $this->line("account #{$account->id} {$account->provider->value}/{$account->alias}: {$result['source']} discovered={$result['discovered']} eligible={$result['eligible']}".($result['error'] ? " error={$result['error']}" : ''));
                if (in_array($result['source'], ['live', 'cache', 'cache-stale'], true)) {
                    $ok++;
                } else {
                    $fail++;
                }
            } catch (\Throwable $e) {
                $fail++;
                $this->error("account #{$account->id}: ".$e->getMessage());
            }
        }
        $this->info("Selesai: {$ok} ok, {$fail} gagal dari {$accounts->count()} akun.");

        return self::SUCCESS;
    }
}
