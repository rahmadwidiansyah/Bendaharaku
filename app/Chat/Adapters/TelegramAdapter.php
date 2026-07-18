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
use App\Models\Wallet;
use App\Support\MoneyFormatter;
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
        private readonly TelegramFormatter      $formatter,
    ) {}

    /**
     * Handle satu Telegram Update payload.
     * Dipanggil oleh TelegramWebhookController.
     *
     * @param array $update  Telegram Update object dari request body
     * @return array         Response sederhana untuk HTTP reply ke Telegram
     */
    public function handle(array $update): array
    {
        if (!isset($update['message']['text'])) {
            return ['status' => 'ignored'];
        }

        $chatId    = $update['message']['chat']['id'];
        $text      = $update['message']['text'];
        $messageId = (string) ($update['message']['message_id'] ?? null);

        // Telegram language_code (opsional, mis. 'id', 'en')
        $platformLocale = $update['message']['from']['language_code'] ?? null;

        // 1. Resolve user
        $user = User::where('telegram_id', $chatId)->first();
        if (!$user) {
            $this->sendMessage($chatId, __(
                'chat.general.unauthorized',
                ['platform_id' => $chatId],
            ));
            return ['status' => 'unauthorized'];
        }

        // 2. Resolve locale (priority: user settings > platform > default)
        $locale = ChatContext::resolveLocale(
            userLocale:     $user->locale,
            platformLocale: $platformLocale,
        );

        $timezone = $user->timezone ?? 'Asia/Jakarta';

        // 3. Handle perintah dasar sebelum AI processing
        $textLower = strtolower(trim($text));
        if ($cmd = $this->handleCommand($textLower, $user, $chatId, $locale)) {
            return $cmd;
        }

        // 4. Kirim typing indicator
        $this->sendMessage($chatId, trans('chat.general.processing', [], $locale));

        // 5. Bangun ChatContext + ChatRequest
        $context = ChatContext::make(
            platform:       ChatPlatform::Telegram,
            conversationId: (string) $chatId,
            locale:         $locale,
            timezone:       $timezone,
            messageId:      $messageId,
            metadata:       [
                'telegram_update_id' => $update['update_id'] ?? null,
                'first_name'         => $update['message']['from']['first_name'] ?? null,
            ],
        );

        $request = ChatRequest::make(
            rawMessage: $text,
            user:       $user,
            context:    $context,
        );

        // 6. Proses via ChatApplicationService
        $response = $this->chatService->handleMessage($request);

        // 7. Format & kirim
        $formatted = $this->formatter->format($response, $context);
        $this->sendMessage($chatId, $formatted);

        Log::info('TelegramAdapter: response sent', [
            'trace_id'  => $context->traceId,
            'chat_id'   => $chatId,
            'intent'    => $response->intent->value,
            'success'   => $response->success,
        ]);

        return ['status' => $response->success ? 'success' : 'failed'];
    }

    // ── Commands ──────────────────────────────────────────────────

    /**
     * Handle perintah platform-specific.
     * Return array jika ditangani, null jika bukan perintah.
     */
    private function handleCommand(string $textLower, User $user, int|string $chatId, string $locale): ?array
    {
        if ($textLower === '/saldo') {
            $this->sendBalanceReport($user, $chatId, $locale);
            return ['status' => 'success'];
        }

        if ($textLower === '/web') {
            $appUrl = config('app.url', 'https://bendaharaku.widihhh.my.id');
            $msg    = trans('chat.command.web_link_msg', ['url' => $appUrl], $locale);
            $this->sendMessage($chatId, $msg);
            return ['status' => 'success'];
        }

        $greetings = ['/start', '/help', 'hai', 'halo', 'hello', 'p', 'ping', 'tes', 'test', 'help', 'tolong'];
        if (in_array($textLower, $greetings)) {
            $this->sendHelpMessage($user, $chatId, $locale);
            return ['status' => 'success'];
        }

        return null;
    }

    private function sendBalanceReport(User $user, int|string $chatId, string $locale): void
    {
        $wallets = Wallet::where('user_id', $user->id)
            ->whereIn('group_type', ['Asset', 'Liquid'])
            ->orderByDesc('balance')
            ->get();

        if ($wallets->isEmpty()) {
            $this->sendMessage($chatId, trans('chat.command.balance_empty', [], $locale));
            return;
        }

        $totalBalance = 0;
        $walletData   = [];
        $maxNameLen   = 11;
        $maxBalLen    = 0;

        foreach ($wallets as $w) {
            $name  = strtoupper($w->name);
            $bal   = $w->balance; // sudah float karena cast di model Wallet
            $totalBalance += $bal;
            $balStr = MoneyFormatter::amount($bal);
            if (strlen($name) > $maxNameLen) $maxNameLen = strlen($name);
            if (strlen($balStr) > $maxBalLen) $maxBalLen = strlen($balStr);
            $walletData[] = ['name' => $name, 'balStr' => $balStr];
        }

        $totalStr = MoneyFormatter::amount($totalBalance);
        if (strlen($totalStr) > $maxBalLen) $maxBalLen = strlen($totalStr);

        $textMsg = "```text\n";
        foreach ($walletData as $wd) {
            $textMsg .= str_pad($wd['name'], $maxNameLen, ' ', STR_PAD_RIGHT)
                . ': Rp '
                . str_pad($wd['balStr'], $maxBalLen, ' ', STR_PAD_LEFT) . "\n";
        }
        $textMsg .= str_repeat('-', $maxNameLen + 5 + $maxBalLen) . "\n";
        $textMsg .= str_pad(trans('chat.command.total_balance', [], $locale), $maxNameLen, ' ', STR_PAD_RIGHT)
            . ': Rp '
            . str_pad($totalStr, $maxBalLen, ' ', STR_PAD_LEFT) . "\n```";

        $this->sendMessage($chatId, trans('chat.command.balance_title', [], $locale) . "\n" . $textMsg);
    }

    private function sendHelpMessage(User $user, int|string $chatId, string $locale): void
    {
        $msg  = trans('chat.command.help_greeting', ['name' => $user->name], $locale) . ' ';
        $msg .= trans('chat.command.help_intro', [], $locale) . "\n\n";
        $msg .= trans('chat.command.help_guide', [], $locale) . "\n";
        $msg .= trans('chat.command.help_example_intro', [], $locale) . "\n\n";
        $msg .= "🔴 *Pengeluaran:* \n`Beli nasi goreng 15k bca`\n`Es jeruk 5000 dana`\n\n";
        $msg .= "🟢 *Pemasukan:* \n`Gajian 5jt mandiri`\n`Dikasih emak 50rb cash`\n\n";
        $msg .= "🔵 *Transfer:* \n`Transfer bca ke dana 100k`\n\n";
        $msg .= "🤝 *Hutang & Piutang (Wajib #Nama):* \n`Pinjam duit 100k bca #Budi`\n\n";
        $msg .= trans('chat.command.help_commands_title', [], $locale) . "\n";
        $msg .= trans('chat.command.help_cmd_balance', [], $locale) . "\n";
        $msg .= trans('chat.command.help_cmd_web', [], $locale) . "\n";
        $msg .= trans('chat.command.help_cmd_help', [], $locale);

        $this->sendMessage($chatId, $msg);
    }

    // ── Telegram API ──────────────────────────────────────────────

    public function sendMessage(int|string $chatId, string $text): void
    {
        $token = config('services.telegram.token');
        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'Markdown',
        ]);
    }
}
