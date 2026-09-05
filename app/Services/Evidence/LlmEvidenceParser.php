<?php

declare(strict_types=1);

namespace App\Services\Evidence;

use App\Enums\DocumentType;
use App\Evidence\DTO\EvidenceData;
use App\Models\Evidence;
use App\Models\User;
use App\Services\AI\AIManager;
use Illuminate\Support\Facades\Log;

class LlmEvidenceParser
{
    public function __construct(
        private readonly AIManager $aiManager,
    ) {}

    /**
     * Parse OCR text via LLM (AI Parser) untuk pengelompokan transaksi otomatis.
     * Dipanggil sebagai fallback ketika regex parser confidence rendah atau amount null.
     * Juga bisa jadi primary jika EVIDENCE_LLM_ENABLED=true dan user punya LLM aktif.
     */
    public function parse(Evidence $evidence, string $ocrText): ?EvidenceData
    {
        if (blank($ocrText)) {
            return null;
        }

        if (! config('evidence.llm.enabled', true)) {
            Log::info('LlmEvidenceParser: LLM disabled via config', ['evidence_id' => $evidence->id]);
            return null;
        }

        $user = User::find($evidence->user_id);
        if (! $user) {
            return null;
        }

        // Siapkan wallets & categories untuk konteks LLM
        $wallets = $user->wallets()->where('group_type', '!=', 'System')->get()->map(fn ($w) => [
            'name' => $w->name,
            'keyword' => $w->keyword,
            'group_type' => $w->group_type,
        ])->toArray();

        $categories = $user->categories()->with('type')->get()->map(fn ($c) => [
            'category_name' => $c->category_name,
            'keyword' => $c->keyword,
            'type' => $c->type?->name,
        ])->toArray();

        // Prompt khusus struk: minta LLM kelompokkan transaksi, jangan pakai regex
        $prompt = $this->buildPrompt($ocrText);

        try {
            $result = $this->aiManager->parseTransaction(
                user: $user,
                text: $ocrText,
                wallets: $wallets,
                categories: $categories,
                activeMemories: [], // Evidence tidak pakai memory per-user dulu
                prompt: $prompt,
            );

            if (! $result->success || $result->transaction === null) {
                Log::info('LlmEvidenceParser: LLM returned no transaction', [
                    'evidence_id' => $evidence->id,
                    'error' => $result->error,
                    'provider' => $result->provider,
                ]);
                return null;
            }

            $trx = $result->transaction;

            // Mapping ParsedTransaction -> EvidenceData
            // Untuk struk, merchantName diisi dari category atau notes
            $merchant = $trx->category ?? $trx->notes ?? null;
            // Jika transactionType adalah Transfer, walletName diambil dari source/dest
            $walletName = $trx->sourceWallet ?? $trx->destinationWallet;

            Log::info('LlmEvidenceParser: LLM success', [
                'evidence_id' => $evidence->id,
                'amount' => $trx->amount,
                'category' => $trx->category,
                'provider' => $result->provider,
                'model' => $result->model,
                'confidence' => $result->confidence,
            ]);

            return new EvidenceData(
                documentType: DocumentType::ShoppingReceipt, // default untuk LLM; bisa override via intent
                rawText: $ocrText,
                walletName: $walletName,
                merchantName: $merchant,
                destinationName: $trx->subject,
                amount: (float) $trx->amount,
                currency: $trx->amount > 0 ? 'IDR' : null,
                transactionType: $trx->transactionType?->value ?? 'EXPENSE',
                description: $trx->notes,
                confidence: round((float) $result->confidence, 4),
                metadata: [
                    'engine' => 'LLM',
                    'provider' => $result->provider,
                    'model' => $result->model,
                    'llm_confidence' => $result->confidence,
                    'subject' => $trx->subject,
                    'category' => $trx->category,
                    'source_wallet' => $trx->sourceWallet,
                    'memory_candidates' => array_map(fn ($m) => (array) $m, $trx->memoryCandidates),
                    'via' => 'ocr_llm_fallback',
                ],
            );
        } catch (\Throwable $e) {
            Log::warning('LlmEvidenceParser failed: '.$e->getMessage(), [
                'evidence_id' => $evidence->id,
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    private function buildPrompt(string $ocrText): string
    {
        return <<<PROMPT
Kamu adalah parser struk/bukti transaksi Bendaharaku.
Tugas: kelompokkan teks OCR berikut menjadi 1 transaksi yang rapi.

Teks OCR:
"""
{$ocrText}
"""

Aturan:
- Ekstrak amount (nominal) dalam IDR (tanpa Rp, hanya angka, contoh 25000)
- Tentukan category dari daftar kategori user jika ada yang mirip, atau tebak kategori umum (Makan, Transport, Belanja, Transfer)
- Tentukan wallet jika ada keyword wallet di teks, atau biarkan null
- Subject adalah nama merchant/toko atau pihak hutang/piutang jika ada
- Notes adalah ringkasan 1 kalimat

Jika teks tidak jelas, tetap buat 1 transaksi dengan amount yang paling mungkin.
PROMPT;
    }
}
