<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Chat\Adapters\TelegramAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Thin controller — satu-satunya tanggung jawab adalah:
 * 1. Terima HTTP request dari Telegram
 * 2. Delegate ke TelegramAdapter
 * 3. Return HTTP response
 *
 * Tidak ada business logic, tidak ada AI, tidak ada formatting di sini.
 * Seluruh logika ada di TelegramAdapter → ChatApplicationService → Orchestrator.
 */
class TelegramWebhookController extends Controller
{
    public function __construct(
        private readonly TelegramAdapter $adapter,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        Log::info('TelegramWebhookController: incoming', [
            'update_id' => $request->input('update_id'),
        ]);

        try {
            $result = $this->adapter->handle($request->all());

            return response()->json($result);
        } catch (\Throwable $e) {
            Log::error('TelegramWebhookController: unhandled crash', [
                'exception' => $e,
                'message' => $e->getMessage(),
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }
}
