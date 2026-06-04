<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Chat\ChatTransactionOrchestrator;

class TelegramWebhookController extends Controller
{
    public function __construct(
        private readonly ChatTransactionOrchestrator $orchestrator
    ) {}

    public function handle(Request $request)
    {
        Log::info('--- WEBHOOK INCOMING ---');

        $update = $request->all();
        if (!isset($update['message']['text'])) return response()->json(['status' => 'ignored']);

        $chatId = $update['message']['chat']['id'];
        $text = $update['message']['text'];
        $textLower = strtolower(trim($text));

        try {
            $user = User::where('telegram_id', $chatId)->first();
            if (!$user) {
                $this->sendMessage($chatId, "❌ Wah, ID Telegram kamu ({$chatId}) belum terdaftar nih di Bendaharaku. Daftarin dulu ya!");
                return response()->json(['status' => 'unauthorized']);
            }

            // ==========================================
            // 1. CEK PERINTAH DASAR
            // ==========================================
            if ($textLower === '/saldo') {
                return $this->handleSaldoCommand($user, $chatId);
            }

            if ($textLower === '/web') {
                $appUrl = env('APP_URL', 'https://bendaharaku.widihhh.my.id'); 
                $msg = "🌐 *Akses Bendaharaku V4*\n\nSilakan klik tombol/link di bawah ini untuk membuka Web Dashboard:\n\n👉 [Buka Bendaharaku]({$appUrl})\n\n_Catatan: Jika terbuka di dalam Telegram, klik titik tiga di pojok kanan atas lalu pilih 'Buka di Chrome/Browser'._";
                $this->sendMessage($chatId, $msg);
                return response()->json(['status' => 'success']);
            }

            $greetings = ['/start', '/help', 'hai', 'halo', 'hello', 'p', 'ping', 'tes', 'test', 'help', 'tolong'];
            if (in_array($textLower, $greetings)) {
                return $this->handleHelpCommand($user, $chatId);
            }

            // ==========================================
            // 2. PROSES TRANSAKSI VIA ORCHESTRATOR
            // ==========================================
            $this->sendMessage($chatId, "⏳ Siap, lagi dicerna AI...");

            $result = $this->orchestrator->process($user, $text, 'TEL');

            if (!$result['success']) {
                $this->sendMessage($chatId, $result['message']);
                return response()->json(['status' => 'failed']);
            }
            
            // ==========================================
            // 3. LAPORAN SUKSES
            // ==========================================
            $trx = $result['transaction'];
            
            $walletSourceName = $trx->sourceWallet->name ?? '-';
            $walletDestName = $trx->destinationWallet->name ?? '-';
            
            // Menggunakan tipe transaksi aktual dari relasi, BUKAN string matching
            $typeName = match(strtolower($trx->type->name)) {
                'income' => 'Pemasukan 🟢',
                'expense' => 'Pengeluaran 🔴',
                'transfer' => 'Transfer 🔵',
                'debt', 'receivable' => 'Hutang / Piutang 🤝',
                default => 'Transaksi ⚪',
            };

            $statusIkon = $trx->is_cleared ? "✅ *TRANSAKSI BERHASIL*" : "📝 *MASUK DRAFT (Butuh Cek Web)*";
            $formattedAmount = "Rp " . number_format($trx->amount, 0, ',', '.');

            $msg = "{$statusIkon}\n";
            $msg .= "_{$typeName}_\n\n";
            $msg .= "🏷 *Ref ID    :* `{$trx->reference_number}`\n";
            $msg .= "💰 *Nominal :* {$formattedAmount}\n";
            $msg .= "📂 *Kategori :* {$trx->category->category_name}\n";
            $msg .= "📤 *Sumber  :* {$walletSourceName}\n";
            $msg .= "📥 *Tujuan  :* {$walletDestName}\n";
            $msg .= "👤 *Pihak     :* {$trx->subject}\n\n";
            $msg .= "💬 *Pesan Asli:*\n_{$text}_";

            $this->sendMessage($chatId, $msg);
            

        } catch (\Exception $e) {
            Log::error("CRASH BOT: ", ['exception' => $e, 'message' => $e->getMessage()]);
            $this->sendMessage($chatId, "❌ Waduh, ada error sistem Bos. Tim lagi benerin nih!");
        }

        return response()->json(['status' => 'success']);
    }

    private function handleSaldoCommand(User $user, $chatId)
    {
        $wallets = Wallet::where('user_id', $user->id)
            ->whereIn('group_type', ['Asset', 'Liquid'])
            ->orderByDesc('balance')
            ->get();

        if ($wallets->isEmpty()) {
            $this->sendMessage($chatId, "🏦 *Belum Ada Dompet*\nKamu belum membuat dompet Asset/Liquid apa pun di web.");
            return response()->json(['status' => 'success']);
        }

        $totalBalance = 0; $walletData = []; $maxNameLen = 11; $maxBalLen = 0;
        foreach ($wallets as $w) {
            $name = strtoupper($w->name); $bal = $w->balance; $totalBalance += $bal;
            $balStr = number_format($bal, 0, ',', '.');
            if (strlen($name) > $maxNameLen) $maxNameLen = strlen($name);
            if (strlen($balStr) > $maxBalLen) $maxBalLen = strlen($balStr);
            $walletData[] = ['name' => $name, 'balStr' => $balStr];
        }
        $totalStr = number_format($totalBalance, 0, ',', '.');
        if (strlen($totalStr) > $maxBalLen) $maxBalLen = strlen($totalStr);

        $textMsg = "```text\n";
        foreach ($walletData as $wd) {
            $textMsg .= str_pad($wd['name'], $maxNameLen, " ", STR_PAD_RIGHT) . ": Rp " . str_pad($wd['balStr'], $maxBalLen, " ", STR_PAD_LEFT) . "\n";
        }
        $textMsg .= str_repeat("-", $maxNameLen + 5 + $maxBalLen) . "\n";
        $textMsg .= str_pad("Total Saldo", $maxNameLen, " ", STR_PAD_RIGHT) . ": Rp " . str_pad($totalStr, $maxBalLen, " ", STR_PAD_LEFT) . "\n
```";

        $this->sendMessage($chatId, "💳 *Laporan Saldo Saat Ini:*\n" . $textMsg);
        return response()->json(['status' => 'success']);
    }

    private function handleHelpCommand(User $user, $chatId)
    {
        $msg = "👋 *Halo Bos {$user->name}!* \nSaya adalah asisten *Bendaharaku V4*. Saya akan mencatat semua keuanganmu secara otomatis.\n\n";
        $msg .= "📖 *PANDUAN CATAT TRANSAKSI:*\n";
        $msg .= "Cukup ketik kalimat santai, contoh:\n\n";
        $msg .= "🔴 *Pengeluaran:* \n`Beli nasi goreng 15k bca`\n`Es jeruk 5000 dana`\n\n";
        $msg .= "🟢 *Pemasukan:* \n`Gajian 5jt mandiri`\n`Dikasih emak 50rb cash`\n\n";
        $msg .= "🔵 *Transfer:* \n`Transfer bca ke dana 100k`\n`Pindah bca ke gopay 50rb`\n\n";
        $msg .= "🤝 *Hutang & Piutang (Wajib #Nama):* \n`Pinjam duit 100k bca #Budi`\n`Bayar utang ke #Budi 50k dana`\n`Ngasih pinjaman 20k cash #Agus`\n\n";
        $msg .= "📊 *PERINTAH BOT:*\n";
        $msg .= "▫️ `/saldo` - Cek sisa uangmu saat ini.\n";
        $msg .= "▫️ `/web` - Buka dashboard web.\n";
        $msg .= "▫️ `/help` - Tampilkan panduan ini.";

        $this->sendMessage($chatId, $msg);
        return response()->json(['status' => 'success']);
    }

    private function sendMessage($chatId, $text)
    {
        $token = config('services.telegram.token');
        return Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ]);
    }
}