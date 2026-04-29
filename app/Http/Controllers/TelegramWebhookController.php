<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB; // JANGAN LUPA TAMBAH INI
use App\Models\User;
use App\Models\TransactionLog;
use App\Models\Category;
use App\Models\Wallet;
use App\Models\TransactionType;

class TelegramWebhookController extends Controller
{
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
            // 1. CEK PERINTAH /SALDO, /WEB, /HELP
            // ==========================================
            
            // Perintah /saldo (Sudah kita buat sebelumnya)
            if ($textLower === '/saldo') {
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
                $textMsg .= str_pad("Total Saldo", $maxNameLen, " ", STR_PAD_RIGHT) . ": Rp " . str_pad($totalStr, $maxBalLen, " ", STR_PAD_LEFT) . "\n```";

                $this->sendMessage($chatId, "💳 *Laporan Saldo Saat Ini:*\n" . $textMsg);
                return response()->json(['status' => 'success']);
            }

            // Perintah /web (Sudah kita buat sebelumnya)
            if ($textLower === '/web') {
                $appUrl = env('APP_URL', 'https://widihhh.my.id'); 
                $msg = "🌐 *Link Akses Web*\n\nBuka dashboard di browser:\n👉 [Buka Bendaharaku]({$appUrl})";
                $this->sendMessage($chatId, $msg);
                return response()->json(['status' => 'success']);
            }

            // Perintah /help atau /start atau sapaan
            $greetings = ['/start', '/help', 'hai', 'halo', 'hello', 'p', 'ping', 'tes', 'test', 'help', 'tolong'];
            if (in_array($textLower, $greetings)) {
                $msg = "👋 *Halo Bos {$user->name}!* \nSaya adalah asisten *Bendaharaku V4*. Saya akan mencatat semua keuanganmu secara otomatis.\n\n";
                $msg .= "📖 *PANDUAN CATAT TRANSAKSI:*\n";
                $msg .= "Cukup ketik kalimat santai, contoh:\n\n";
                $msg .= "🔴 *Pengeluaran:* \n`Beli nasi goreng 15k bca`\n`Es jeruk 5000 dana`\n\n";
                $msg .= "🟢 *Pemasukan:* \n`Gajian 5jt mandiri`\n`Dikasih emak 50rb cash`\n\n";
                $msg .= "🔵 *Transfer:* \n`Transfer bca ke dana 100k`\n`Pindah bca ke gopay 50rb`\n\n";
                $msg .= "🤝 *Hutang & Piutang (Wajib #Nama):* \n`Pinjam duit 100k bca #Budi`\n`Bayar utang ke #Budi 50k dana`\n`Ngasih pinjaman 20k cash #Agus` \n`nagih utang ke #Budi 50k dana` \n\n";
                $msg .= "📊 *PERINTAH BOT:*\n";
                $msg .= "▫️ `/saldo` - Cek sisa uangmu saat ini.\n";
                $msg .= "▫️ `/web` - Buka dashboard web.\n";
                $msg .= "▫️ `/help` - Tampilkan panduan ini.";

                $this->sendMessage($chatId, $msg);
                return response()->json(['status' => 'success']);
            }
              
            // ==========================================
            // 1.5 CEK PERINTAH BUKA WEB (/web)
            // ==========================================
            if ($textLower === '/web') {
                $appUrl = env('APP_URL', 'https://widihhh.my.id'); 
                
                $msg = "🌐 *Akses Bendaharaku V4*\n\nSilakan klik tombol/link di bawah ini untuk membuka Web Dashboard:\n\n👉 [Buka Bendaharaku]({$appUrl})\n\n_Catatan: Jika terbuka di dalam Telegram, klik titik tiga di pojok kanan atas lalu pilih 'Buka di Chrome/Browser'._";
                
                $this->sendMessage($chatId, $msg);
                return response()->json(['status' => 'success']);
            }

            // ==========================================
            // 2. CEK SAPAAN / CHAT NYASAR
            // ==========================================
            $greetings = ['/start', 'hai', 'halo', 'hello', 'p', 'ping', 'tes', 'test', 'woy', 'bot'];
            if (in_array($textLower, $greetings)) {
                $msg = "Halo Bos *{$user->name}*! 👋\n\nBendahara siap nyatet keuanganmu hari ini. Langsung aja ketik transaksinya!\n\n💡 *Contoh Ketikan:*\n- Beli es jeruk 5k dana\n- Gajian 5jt bca\n- Dapat hutangan 50rb bni *#Budi*\n\n📊 *Perintah Lain:*\nKetik `/saldo` untuk melihat total uangmu.\nKetik `/web` untuk buka dashboard web.";
                $this->sendMessage($chatId, $msg);
                return response()->json(['status' => 'greeting']);
            }

            $this->sendMessage($chatId, "⏳ Siap, lagi dicerna AI...");

            // 3. KIRIM KE PYTHON UNTUK ANALISA
            $wallets = Wallet::where('user_id', $user->id)->get(['id', 'name', 'group_type', 'keyword'])->toArray();
            $categories = Category::where('user_id', $user->id)->select('id', 'category_name as name', 'type_id', 'keyword')->get()->toArray();

            $response = Http::withHeaders(['X-API-KEY' => env('PYTHON_AI_KEY')])
                ->timeout(10)
                ->post(env('PYTHON_AI_URL') . '/analyze', [
                    'text' => $text,
                    'wallets' => $wallets,
                    'categories' => $categories
                ]);

            if (!$response->successful()) throw new \Exception("Aduh, Otak AI (Python) lagi down nih.");

            $ai = $response->json();

            // 4. VALIDASI RAMAH (Basic)
            if (!$ai['amount']) {
                $this->sendMessage($chatId, "🤔 *Nominalnya berapa Bos?*\nAku bingung nih, kamu belum nyebutin jumlah uangnya.\n\n💡 *Contoh:* Beli es jeruk 5k dana");
                return response()->json(['status' => 'failed']);
            }
            if (!$ai['category_id']) {
                $this->sendMessage($chatId, "🧐 *Masuk kategori apa nih?*\nAku belum kenal nama barang/kegiatannya. Pastikan keyword-nya udah kamu daftarin di Web ya!\n\n💡 *Contoh:* Beli es jeruk 5k dana");
                return response()->json(['status' => 'failed']);
            }
            if (!$ai['source_wallet_id'] || !$ai['dest_wallet_id']) {
                $this->sendMessage($chatId, "👛 *Pakai dompet apa Bos?*\nKamu lupa nyebutin nama dompetnya nih (Cash, BCA, Dana, dll).\n\n💡 *Contoh:* Beli es jeruk 5k dana");
                return response()->json(['status' => 'failed']);
            }

            // 5. VALIDASI KETAT HUTANG / PIUTANG (#NAMA)
            $category = Category::find($ai['category_id']);
            $catNameLower = strtolower($category->category_name);
            $isDebtRelated = str_contains($catNameLower, 'hutang') || str_contains($catNameLower, 'piutang');

            preg_match('/#([a-zA-Z0-9_]+)/', $text, $matches);
            $extractedSubject = $matches[1] ?? null;

            if ($isDebtRelated && !$extractedSubject) {
                $contohNominal = ($ai['amount'] / 1000) . "k";
                $this->sendMessage($chatId, "🤝 *Nama orangnya siapa Bos?*\nKarena ini transaksi Hutang/Piutang, kamu WAJIB pakai hashtag buat nyebut nama orangnya.\n\n💡 *Contoh:* {$category->category_name} {$contohNominal} dana #Budi");
                return response()->json(['status' => 'failed']);
            }

            $finalSubject = $extractedSubject ?? $user->name;

            // ==========================================
            // 6. SIMPAN KE DATABASE & POTONG SALDO
            // ==========================================
            $refNumber = 'TEL' . date('YmdHis') . rand(100, 999);

            DB::transaction(function () use ($ai, $user, $refNumber, $text, $finalSubject) {
                // Buat Log Transaksi
                TransactionLog::create([
                    'reference_number'      => $refNumber,
                    'user_id'               => $user->id,
                    'date'                  => now()->format('Y-m-d'),
                    'type_id'               => $ai['type_id'],
                    'category_id'           => $ai['category_id'],
                    'source_wallet_id'      => $ai['source_wallet_id'],
                    'destination_wallet_id' => $ai['dest_wallet_id'],
                    'amount'                => $ai['amount'],
                    'balance_before'        => 0, // Opsional jika mau akurat bisa dihitung dulu
                    'balance_after'         => 0,
                    'subject'               => $finalSubject,
                    'notes'                 => $text . ($ai['is_cleared'] ? '' : ' [DRAFT AI]'),
                    'is_cleared'            => $ai['is_cleared'],
                ]);

                // POTONG SALDO REAL-TIME JIKA CLEAR! 🔥
                if ($ai['is_cleared']) {
                    Wallet::where('id', $ai['source_wallet_id'])->decrement('balance', $ai['amount']);
                    Wallet::where('id', $ai['dest_wallet_id'])->increment('balance', $ai['amount']);
                }
            });

            // ==========================================
            // 7. LAPORAN SUKSES (FULL DETAIL + REF NUMBER)
            // ==========================================
            $walletSourceName = Wallet::find($ai['source_wallet_id'])->name ?? '-';
            $walletDestName = Wallet::find($ai['dest_wallet_id'])->name ?? '-';
            
            // Penamaan Tipe Transaksi
            $typeName = "Transfer / Hutang 🔵";
            if (str_contains(strtolower($walletSourceName), 'external')) $typeName = "Pemasukan 🟢";
            if (str_contains(strtolower($walletDestName), 'merchant')) $typeName = "Pengeluaran 🔴";

            $statusIkon = $ai['is_cleared'] ? "✅ *TRANSAKSI BERHASIL*" : "📝 *MASUK DRAFT (Butuh Cek Web)*";
            $formattedAmount = "Rp " . number_format($ai['amount'], 0, ',', '.');

            $msg = "{$statusIkon}\n";
            $msg .= "_{$typeName}_\n\n";
            $msg .= "🏷 *Ref ID    :* `{$refNumber}`\n";
            $msg .= "💰 *Nominal :* {$formattedAmount}\n";
            $msg .= "📂 *Kategori :* {$category->category_name}\n";
            $msg .= "📤 *Sumber  :* {$walletSourceName}\n";
            $msg .= "📥 *Tujuan  :* {$walletDestName}\n";
            $msg .= "👤 *Pihak     :* {$finalSubject}\n\n";
            $msg .= "💬 *Pesan Asli:*\n_{$text}_";

            $this->sendMessage($chatId, $msg);

        } catch (\Exception $e) {
            Log::error("CRASH BOT: " . $e->getMessage());
            $this->sendMessage($chatId, "❌ Waduh, ada error sistem Bos: " . $e->getMessage());
        }

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