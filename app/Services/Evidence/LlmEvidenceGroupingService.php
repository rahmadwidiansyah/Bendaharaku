<?php

declare(strict_types=1);

namespace App\Services\Evidence;

use App\Models\Evidence;
use App\Models\User;
use App\Services\Chat\ChatTransactionOrchestrator;
use App\Services\Wallet\WalletResolutionService;
use Illuminate\Support\Facades\Log;

class LlmEvidenceGroupingService
{
    public function __construct(
        private readonly ChatTransactionOrchestrator $orchestrator,
        private readonly WalletResolutionService $walletResolution,
    ) {}

    /**
     * Grouping evidence OCR text via LLM (primary).
     * - Kategori HARUS dari kategori milik user (jangan buat baru)
     * - Wallet prioritas: 1 caption_hint (name/keyword), 2 label wallet di OCR, 3 null → QuickWalletPicker
     * - Nama barang → notes = description (disamakan)
     *
     * @return array{success: bool, is_multi?: bool, multi_result?: \App\DTO\MultiTransactionResult, message?: string, error_code?: string}
     */
    public function group(Evidence $evidence, User $user, string $captionHint = ''): array
    {
        $ocrText = $evidence->ocr_text ?? $evidence->normalized_text ?? '';
        if (blank($ocrText)) {
            Log::warning('LlmEvidenceGroupingService: OCR text kosong', ['evidence_id' => $evidence->id]);
            return ['success' => false, 'error_code' => 'OCR_EMPTY', 'message' => 'OCR belum selesai, coba lagi.'];
        }

        // Build text untuk LLM: OCR + wallet hint
        $text = $ocrText;
        $captionHint = trim($captionHint);
        if ($captionHint !== '' && $captionHint !== '[Evidence]') {
            // Validasi caption adalah wallet milik user (name/keyword) sebelum append
            $hintWallet = $this->resolveHintWallet($captionHint, $user);
            if ($hintWallet) {
                $text .= "\n\n[Wallet hint: {$hintWallet->name}]";
                Log::info('Evidence LLM: caption hint wallet resolved', ['evidence_id' => $evidence->id, 'hint' => $captionHint, 'wallet' => $hintWallet->name]);
            } else {
                // Caption bukan wallet valid → tetap append sebagai hint tapi LLM boleh ignore
                $text .= "\n\n[Wallet hint: {$captionHint}]";
            }
        } else {
            // Caption kosong → coba deteksi wallet label di OCR text
            $ocrWallet = $this->detectWalletInOcr($ocrText, $user);
            if ($ocrWallet) {
                $text .= "\n\n[Wallet hint: {$ocrWallet->name}]";
                Log::info('Evidence LLM: OCR wallet label detected', ['evidence_id' => $evidence->id, 'wallet' => $ocrWallet->name]);
            }
        }

        // Panggil orchestrator dengan source EVIDENCE (biar is_cleared logic sesuai WEB/DRAFT)
        // Pakai WEB agar jadi draft (butuh QuickWalletPicker jika wallet null)
        $source = 'WEB';
        $result = $this->orchestrator->process($user, $text, $source);

        // Post-process: samakan notes = description (jika LLM isi notes tapi description kosong, atau sebaliknya)
        // Dan pastikan wallet null → biar QuickWalletPicker muncul (jangan default ke Dompet Cash)
        if (!empty($result['is_multi']) && isset($result['multi_result'])) {
            foreach ($result['multi_result']->results as $item) {
                if ($item->isSuccess() && $item->transaction) {
                    $trx = $item->transaction;
                    // Samakan notes & description
                    if (blank($trx->notes) && !blank($trx->subject)) {
                        $trx->notes = $trx->subject;
                    } elseif (!blank($trx->notes) && blank($trx->subject)) {
                        $trx->subject = $trx->notes;
                    } elseif (!blank($trx->notes)) {
                        $trx->subject = $trx->notes;
                    }
                }
                if ($item->isSuccess() && $item->draft) {
                    $draft = $item->draft;
                    $payload = $draft->payload ?? [];
                    // Samakan notes/description di payload
                    if (!empty($payload['notes']) && empty($payload['subject'])) {
                        $payload['subject'] = $payload['notes'];
                    } elseif (empty($payload['notes']) && !empty($payload['subject'])) {
                        $payload['notes'] = $payload['subject'];
                    }
                    $draft->payload = $payload;
                    // Jika draft masih needs_wallet, biarkan → QuickWalletPicker akan handle
                }
            }
        } elseif (!empty($result['success']) && isset($result['transaction'])) {
            $trx = $result['transaction'];
            if (!blank($trx->notes) && blank($trx->subject)) {
                $trx->subject = $trx->notes;
            } elseif (blank($trx->notes) && !blank($trx->subject)) {
                $trx->notes = $trx->subject;
            }
        }

        return $result;
    }

    private function resolveHintWallet(string $hint, User $user): ?\App\Models\Wallet
    {
        $hint = trim($hint);
        if ($hint === '') return null;

        // Coba exact name
        $wallet = $user->wallets()->where('name', $hint)->first();
        if ($wallet) return $wallet;

        // Coba keyword
        $wallets = $user->wallets()->where('group_type', '!=', 'System')->get();
        foreach ($wallets as $w) {
            $keywords = array_map('trim', explode(',', $w->keyword ?? ''));
            foreach ($keywords as $kw) {
                if ($kw !== '' && str_contains(mb_strtolower($hint), mb_strtolower($kw))) {
                    return $w;
                }
                if ($kw !== '' && str_contains(mb_strtolower($w->name), mb_strtolower($hint))) {
                    return $w;
                }
            }
            if (str_contains(mb_strtolower($hint), mb_strtolower($w->name))) {
                return $w;
            }
        }

        // Fallback via WalletResolutionService
        try {
            $found = $this->walletResolution->findWalletByText($hint, $user->id);
            if ($found) return $found;
        } catch (\Throwable $e) {}

        return null;
    }

    private function detectWalletInOcr(string $ocrText, User $user): ?\App\Models\Wallet
    {
        $wallets = $user->wallets()->where('group_type', '!=', 'System')->get();
        $lowerOcr = mb_strtolower($ocrText);
        foreach ($wallets as $w) {
            if (str_contains($lowerOcr, mb_strtolower($w->name))) {
                return $w;
            }
            $keywords = array_map('trim', explode(',', $w->keyword ?? ''));
            foreach ($keywords as $kw) {
                if ($kw !== '' && str_contains($lowerOcr, mb_strtolower($kw))) {
                    return $w;
                }
            }
        }
        return null;
    }
}
