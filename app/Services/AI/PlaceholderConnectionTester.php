<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Enums\AiProvider;

/**
 * Penguji koneksi NYATA (menggantikan perilaku placeholder lama yang selalu sukses).
 * Memanggil generateText ringan via adapter agar key/model benar-benar terverifikasi.
 * Tidak pernah me-log atau mengembalikan API key.
 */
class PlaceholderConnectionTester
{
    public function __construct(
        private AiProviderFactory $factory,
    ) {}

    /**
     * @return array{success: bool, message: string, error_code?: ?string, latency_ms?: ?int}
     */
    public function test(AiProvider $provider, string $apiKey, ?string $model = null): array
    {
        $apiKey = trim($apiKey);
        if ($apiKey === '') {
            return [
                'success' => false,
                'message' => 'Token API tidak ditemukan untuk dilakukan pengujian.',
                'error_code' => 'missing_api_key',
            ];
        }

        $testModel = $model && trim($model) !== '' ? trim($model) : $provider->defaultModel();
        if ($provider === AiProvider::OpenAiCompatible && blank($testModel)) {
            $testModel = 'gpt-4o-mini';
        }

        $started = microtime(true);
        try {
            $adapter = $this->factory->make($provider);
            $text = $adapter->generateText('Reply with exactly: ok', $apiKey, (string) $testModel);
            $latency = (int) round((microtime(true) - $started) * 1000);
            if (blank($text)) {
                return [
                    'success' => false,
                    'message' => 'Provider merespons kosong. Coba model lain.',
                    'error_code' => 'invalid_response',
                    'latency_ms' => $latency,
                ];
            }

            return [
                'success' => true,
                'message' => 'Handshake sukses. Koneksi menuju '.strtoupper($provider->value).' terverifikasi aktif.',
                'latency_ms' => $latency,
            ];
        } catch (\Throwable $e) {
            $latency = (int) round((microtime(true) - $started) * 1000);
            $lower = strtolower($e->getMessage());
            $code = 'unknown_error';
            $message = 'Koneksi gagal: '.mb_substr($e->getMessage(), 0, 200);
            if (str_contains($lower, 'api key tidak valid') || str_contains($lower, '401') || str_contains($lower, '403')) {
                $code = 'invalid_api_key';
                $message = 'API key tidak valid atau akses ditolak.';
            } elseif (str_contains($lower, 'rate') || str_contains($lower, 'quota') || str_contains($lower, '429')) {
                $code = 'rate_limit';
                $message = 'Rate limit/kuota tercapai saat pengujian.';
            } elseif (str_contains($lower, 'timeout') || str_contains($lower, 'timed out')) {
                $code = 'timeout';
                $message = 'Pengujian timeout. Coba lagi.';
            }

            return [
                'success' => false,
                'message' => $message,
                'error_code' => $code,
                'latency_ms' => $latency,
            ];
        }
    }
}
