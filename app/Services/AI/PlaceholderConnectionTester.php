<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Enums\AiProvider;

class PlaceholderConnectionTester
{
    /**
     * @return array{success: bool, message: string}
     */
    public function test(AiProvider $provider, string $apiKey): array
    {
        usleep(400000);

        return [
            'success' => true,
            'message' => 'Handshake sukses. Koneksi menuju ' . strtoupper($provider->value) . ' terverifikasi aktif.',
        ];
    }
}
