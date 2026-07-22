<?php

declare(strict_types=1);

namespace App\Chat\Adapters;

use App\Chat\ChatApplicationService;
use App\Chat\DTOs\ChatContext;
use App\Chat\DTOs\ChatRequest;
use App\Chat\DTOs\ChatResponse;
use App\Chat\Formatters\TelegramFormatter;
use App\Enums\ChatPlatform;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Adapter Telegram — satu-satunya kelas yang tahu tentang Telegram API.
 *
 * Tanggung jawab:
 * 1. Parsing Telegram Update payload → ChatRequest
 * 2. Resolve User dari telegram_id
 * 3. Resolve locale dari user settings / Telegram language_code
 * 4. Delegate ke ChatApplicationService::handleMessage()
 * 5. Format ChatResponse via TelegramFormatter
 * 6. Kirim ke Telegram API via sendMessage()
 *
 * Tidak ada AI logic, tidak ada business rule di sini.
 * Tidak ada Markdown dibangun di sini — semua dari TelegramFormatter.
 */
class TelegramAdapter
{
    public function __construct(
        private readonly ChatApplicationService $chatService,
        private readonly TelegramFormatter $formatter,
    ) {}

    /**
     * Handle satu Telegram Update payload.
     * Dipanggil oleh TelegramWebhookController.
     *
     * @param  array  $update  Telegram Update object dari request body
     * @return array Response sederhana untuk HTTP reply ke Telegram
     */
    public function handle(array $update): array
    {
        if (! isset($update['message']['text'])) {
            return ['status' => 'ignored'];
        }

        $chatId = $update['message']['chat']['id'];
        $text = $update['message']['text'];
        $messageId = (string) ($update['message']['message_id'] ?? null);

        // Telegram language_code (opsional, mis. 'id', 'en')
        $platformLocale = $update['message']['from']['language_code'] ?? null;

        // 1. Resolve user
        $user = User::where('telegram_id', $chatId)->first();
        if (! $user) {
            $this->sendMessage($chatId, __(
                'chat.general.unauthorized',
                ['platform_id' => $chatId],
            ));

            return ['status' => 'unauthorized'];
        }

        // 2. Resolve locale (priority: user settings > platform > default)
        $locale = ChatContext::resolveLocale(
            userLocale: $user->locale,
            platformLocale: $platformLocale,
        );

        $timezone = $user->timezone ?? 'Asia/Jakarta';

        // 4. Kirim typing indicator (hanya untuk non-command/AI query)
        $isCommand = str_starts_with(trim($text), '/')
            || in_array(strtolower(trim($text)), ['hai', 'halo', 'hello', 'hi', 'ping', 'p', 'tes', 'test', 'help', 'tolong']);
        if (! $isCommand) {
            $this->sendMessage($chatId, trans('chat.general.processing', [], $locale));
        }

        // 5. Bangun ChatContext + ChatRequest
        $context = ChatContext::make(
            platform: ChatPlatform::Telegram,
            conversationId: (string) $chatId,
            locale: $locale,
            timezone: $timezone,
            messageId: $messageId,
            metadata: [
                'telegram_update_id' => $update['update_id'] ?? null,
                'first_name' => $update['message']['from']['first_name'] ?? null,
                'platform' => 'telegram',
            ],
        );

        $request = ChatRequest::make(
            rawMessage: $text,
            user: $user,
            context: $context,
        );

        // 6. Proses via ChatApplicationService
        $response = $this->chatService->handleMessage($request);

        // 7. Format & kirim
        $formatted = $this->formatter->format($response, $context);
        $this->sendMessage($chatId, $formatted);

        Log::info('TelegramAdapter: response sent', [
            'trace_id' => $context->traceId,
            'chat_id' => $chatId,
            'intent' => $response->intent->value,
            'success' => $response->success,
        ]);

        return ['status' => $response->success ? 'success' : 'failed'];
    }

    // ── Telegram API ──────────────────────────────────────────────

    public function sendMessage(int|string $chatId, string $text): void
    {
        $token = config('services.telegram.token');
        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ]);
    }
}
