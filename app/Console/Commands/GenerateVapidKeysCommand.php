<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateVapidKeysCommand extends Command
{
    protected $signature = 'notification:generate-vapid-keys';

    protected $description = 'Generate VAPID key pair untuk Web Push (jalankan sekali, isi ke .env)';

    public function handle(): int
    {
        $keys = VAPID::createVapidKeys();

        $this->info('VAPID keys berhasil digenerate. Salin ke .env:');
        $this->newLine();
        $this->line('VAPID_PUBLIC_KEY='.$keys['publicKey']);
        $this->line('VAPID_PRIVATE_KEY='.$keys['privateKey']);
        $this->line('VAPID_SUBJECT=mailto:admin@bendaharaku.id');
        $this->newLine();
        $this->warn('VAPID_PRIVATE_KEY bersifat rahasia — jangan commit ke repository.');

        return self::SUCCESS;
    }
}
