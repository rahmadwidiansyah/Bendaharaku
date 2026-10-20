<?php

declare(strict_types=1);

namespace App\Services\Evidence;

use App\Chat\Services\DraftViewModelBuilder;
use App\DTO\ChatProcessOptions;
use App\DTO\MultiTransactionItem;
use App\DTO\MultiTransactionResult;
use App\Models\Evidence;
use App\Models\TransactionDraft;
use App\Models\User;
use App\Models\Wallet;
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
     * @return array{success: bool, is_multi?: bool, multi_result?: MultiTransactionResult, message?: string, error_code?: string}
     */
    public function group(Evidence $evidence, User $user, string $captionHint = ''): array
    {
        $ocrText = $evidence->ocr_text ?? $evidence->normalized_text ?? '';
        if (blank($ocrText)) {
            Log::warning('LlmEvidenceGroupingService: OCR text kosong', ['evidence_id' => $evidence->id]);

            return ['success' => false, 'error_code' => 'OCR_EMPTY', 'message' => 'OCR belum selesai, coba lagi.'];
        }

        // Build text untuk LLM: OCR + wallet hint + item filter hint (flex per user, tidak hardcode)
        $text = $ocrText;
        $captionHint = trim($captionHint);
        $normalizedCaption = preg_replace('/\s+/', ' ', mb_strtolower($captionHint));
        $normalizedCaption = str_replace(['punya ku', 'milik ku'], ['punyaku', 'milikku'], $normalizedCaption);
        $isFilterCaption = $captionHint !== '' && $captionHint !== '[Evidence]' && preg_match('/\b(punyaku|cuma|cuman|hanya|punya saya|punya aku|milikku|milik saya|yang saya)\b/iu', $normalizedCaption);
        $walletRoles = ['source' => null, 'destination' => null];
        $isWalletCaption = false;

        // Ekstrak wallet hint dari caption secara independen, sadar peran sumber vs tujuan.
        if ($captionHint !== '' && $captionHint !== '[Evidence]') {
            $walletRoles = $this->resolveWalletRoles($captionHint, $user);
            $isWalletCaption = $walletRoles['source'] !== null || $walletRoles['destination'] !== null;
        }

        if ($captionHint !== '' && $captionHint !== '[Evidence]') {
            // SELALU kirim full caption ke LLM (flexible, tidak hardcode) — prioritas caption > OCR
            $text .= "\n\n[User caption (full): \"{$captionHint}\"]";
            Log::info('Evidence LLM: caption full included', [
                'evidence_id' => $evidence->id,
                'hint' => $captionHint,
                'is_filter' => (bool) $isFilterCaption,
                'source_wallet' => $walletRoles['source']?->name,
                'destination_wallet' => $walletRoles['destination']?->name,
            ]);

            if ($isFilterCaption) {
                // Caption filter + wallet hint bisa bersamaan (mis. "punyaku magelangan rendang dan es kopi abc ya bayar pakai dana")
                $text .= "\n[User filter: hanya simpan item yang disebutkan di caption di atas — abaikan item lain di struk]";
                Log::info('Evidence LLM: caption as item filter', ['evidence_id' => $evidence->id, 'hint' => $captionHint]);
                foreach ($this->walletHintLines($walletRoles) as $line) {
                    $text .= "\n".$line;
                }
                if ($isWalletCaption) {
                    Log::info('Evidence LLM: caption wallet roles resolved (filter+wallet)', ['evidence_id' => $evidence->id, 'hint' => $captionHint, 'roles' => $this->walletRoleLog($walletRoles)]);
                }
            } else {
                if ($isWalletCaption) {
                    foreach ($this->walletHintLines($walletRoles) as $line) {
                        $text .= "\n".$line;
                    }
                    Log::info('Evidence LLM: caption wallet roles resolved', ['evidence_id' => $evidence->id, 'hint' => $captionHint, 'roles' => $this->walletRoleLog($walletRoles)]);
                } else {
                    // Caption bukan wallet valid dan bukan filter eksplisit → treat sebagai catatan user
                    $text .= "\n[User note: gunakan caption di atas sebagai konteks tambahan untuk grouping, jika ada nama barang spesifik di caption, prioritaskan yang disebut]";
                    Log::info('Evidence LLM: caption generic note', ['evidence_id' => $evidence->id, 'hint' => $captionHint]);
                }
            }
        }

        // OCR wallet adalah bukti sisi SUMBER (rekening/dompet yang dipakai membayar).
        // Jangan biarkan caption tujuan (mis. "pindah ke dana") menimpa sumber dari OCR.
        $ocrWalletCandidate = $this->detectWalletInOcr($ocrText, $user);
        $captionSource = $walletRoles['source'];
        $captionDestination = $walletRoles['destination'];
        $ocrAddsSource = $ocrWalletCandidate !== null
            && $captionSource === null
            && ($captionDestination === null || $captionDestination->id !== $ocrWalletCandidate->id);

        if ($ocrAddsSource) {
            $text .= "\n[Source wallet hint (from OCR): {$ocrWalletCandidate->name}]";
            Log::info('Evidence LLM: OCR source wallet included', ['evidence_id' => $evidence->id, 'wallet' => $ocrWalletCandidate->name, 'caption_destination' => $captionDestination?->name]);
        } elseif ($ocrWalletCandidate) {
            Log::info('Evidence LLM: OCR wallet candidate ignored (caption priority)', [
                'evidence_id' => $evidence->id,
                'ocr_wallet' => $ocrWalletCandidate->name,
                'caption_source' => $captionSource?->name,
                'caption_destination' => $captionDestination?->name,
            ]);
        }

        // Enrichment draft: utamakan sumber. Caption tujuan saja tidak boleh jadi source wallet.
        $hintWallet = $walletRoles['source'] ?? ($ocrAddsSource ? $ocrWalletCandidate : null);

        // Instruksi PENTING: kelompok per kategori, jangan gabung semua jadi 1 (bug 5 item jadi 1)
        $text .= "\n\n[Instruksi PENTING: Kelompokkan HANYA per kategori user. Jika 5 item semua kategori 'Makanan' → jadi 1 transaksi dengan notes berisi semua nama dipisah koma (contoh: 'Ayam Goreng, Nasi, Es Teh'). Jika ada Es Jeruk kategori 'Minuman' dan Ayam Goreng kategori 'Makanan' → jadi 2 transaksi terpisah. Jangan buat 1 transaksi untuk semua jika beda kategori. Amount per transaksi = jumlah amount item dalam kategori tersebut. Notes = daftar nama barang dalam kategori itu (samakan dengan description). Jika ada filter user seperti 'punyaku cuma X', hanya buat transaksi untuk X yang disebutkan. Jangan gabung reference number (12+ digit) sebagai amount.]";

        // Panggil orchestrator dengan source EVIDENCE (biar is_cleared logic sesuai WEB/DRAFT)
        // Pakai WEB agar jadi draft (butuh QuickWalletPicker jika wallet null)
        $source = 'WEB';
        // Evidence multi-transaksi tidak boleh fallback diam-diam ke single: makna transaksi bisa berubah.
        $result = $this->orchestrator->process($user, $text, $source, new ChatProcessOptions(allowSingleFallback: false));

        // Fallback kertas vs digital: jika LLM gagal ekstrak nominal (amount 0) tapi evidence punya parsed amount 5029, pakai itu
        // Ini untuk struk kertas yang OCR-nya jelek (244 char) tapi TransferReceiptParser sudah benar 5029
        $parsedData = $evidence->parsed_data; // EvidenceData DTO (atau null)
        $fallbackAmount = $parsedData?->amount ?? $evidence->amount ?? null;
        if (! empty($result['is_multi']) && isset($result['multi_result'])) {
            $hasZeroAmount = false;
            foreach ($result['multi_result']->results as $it) {
                if ($it->isSuccess() && ($it->transaction?->amount ?? $it->draft?->payload['amount'] ?? 0) == 0) {
                    $hasZeroAmount = true;
                    break;
                }
                if (! $it->isSuccess() && ($it->errorCode?->value ?? '') === 'INVALID_AMOUNT') {
                    $hasZeroAmount = true;
                    break;
                }
            }
            // Jika semua item amount 0 dan ada fallback, pakai fallback (bagi rata atau single)
            if ($hasZeroAmount && $fallbackAmount && $fallbackAmount > 0) {
                Log::warning('Evidence LLM: amount 0 fallback ke parsed amount', ['evidence_id' => $evidence->id, 'fallback' => $fallbackAmount, 'caption' => $captionHint]);
                $totalItems = count($result['multi_result']->results);
                $failedItems = array_filter($result['multi_result']->results, fn ($it) => ! $it->isSuccess() && ($it->errorCode?->value ?? '') === 'INVALID_AMOUNT');
                $successCount = $result['multi_result']->successCount();
                // Hitung perItem berdasarkan jumlah item yang filter minta (jika ada filter, hitung dari failed+success yang terfilter)
                $targetCount = $successCount > 0 ? $successCount : count($failedItems);
                $perItem = $targetCount > 0 ? round($fallbackAmount / $targetCount, 2) : $fallbackAmount;

                if ($successCount === 0 && count($failedItems) > 0) {
                    // Semua failed karena INVALID_AMOUNT (kasus kertas 244 char) → buat ulang item yang gagal jadi sukses dengan fallback amount
                    // Jangan buat single, tapi perbaiki tiap failed item jadi draft dengan amount perItem
                    $newResults = [];
                    foreach ($result['multi_result']->results as $it) {
                        if (! $it->isSuccess() && ($it->errorCode?->value ?? '') === 'INVALID_AMOUNT') {
                            // Buat draft baru untuk item yang gagal, pakai raw text sebagai notes
                            $raw = $it->raw ?? 'Item';
                            // Coba resolve kategori & wallet dari raw + fallback
                            $fallbackText = $raw.' '.$perItem.' dana';
                            $fb = $this->orchestrator->process($user, $fallbackText, 'WEB');
                            if (! empty($fb['success']) && isset($fb['transaction'])) {
                                $newResults[] = MultiTransactionItem::success(index: $it->index, transaction: $fb['transaction'], raw: $raw);
                            } elseif (! empty($fb['success']) && isset($fb['draft'])) {
                                $newResults[] = MultiTransactionItem::successDraft(index: $it->index, draft: $fb['draft'], raw: $raw);
                            } else {
                                // Jika masih gagal, paksa buat draft manual dengan perItem
                                $cat = $user->categories()->where('type_id', function ($q) {
                                    $q->select('id')->from('transaction_types')->where('name', 'Expense')->limit(1);
                                })->first();
                                $wallet = $walletRoles['source'] ?? $walletRoles['destination'] ?? $this->detectWalletInOcr($ocrText, $user) ?? $user->wallets()->where('group_type', '!=', 'System')->first();
                                $payload = [
                                    'amount' => $perItem,
                                    'category_id' => $cat?->id,
                                    'category_name' => $cat?->category_name ?? 'Lainnya',
                                    'source_wallet_id' => $wallet?->id,
                                    'source_wallet_name' => $wallet?->name,
                                    'subject' => $raw,
                                    'notes' => $raw,
                                    'type_key' => 'expense',
                                    'needs_wallet' => false,
                                ];
                                $draft = TransactionDraft::create([
                                    'user_id' => $user->id,
                                    'conversation_id' => $user->conversations()->where('is_active', true)->latest()->value('id'),
                                    'ai_provider' => 'fallback',
                                    'ai_model' => 'fallback-amount',
                                    'draft_type' => 'single',
                                    'status' => 'pending',
                                    'ai_confidence' => 0.6,
                                    'original_text' => $raw,
                                    'expires_at' => now()->addHours(24),
                                    'payload' => $payload,
                                ]);
                                $fakeTrx = app(DraftViewModelBuilder::class)->buildFakeTransactionFromPayload($payload, null);
                                $newResults[] = MultiTransactionItem::successDraft(index: $it->index, draft: $draft, raw: $raw);
                            }
                        } else {
                            $newResults[] = $it;
                        }
                    }
                    // Ganti results dengan yang sudah diperbaiki
                    $result['multi_result'] = new MultiTransactionResult(results: $newResults, provider: $result['multi_result']->provider ?? 'fallback', model: $result['multi_result']->model ?? 'fallback', confidence: 0.6);
                    $result['success'] = true;
                    $result['is_multi'] = true;

                    return $result;
                } else {
                    // Patch amount 0 jadi perItem untuk yang sukses tapi 0
                    foreach ($result['multi_result']->results as $it) {
                        if ($it->isSuccess() && ($it->transaction?->amount ?? 0) == 0) {
                            $it->transaction->amount = $perItem;
                        }
                        if ($it->isSuccess() && ($it->draft?->payload['amount'] ?? 0) == 0) {
                            $it->draft->payload['amount'] = $perItem;
                        }
                    }
                }
            }
        }

        // SPEC §10-12: Caption is CONTEXT, not OCR replacement. If evidence has amount and LLM grouping returns amount 0, keep evidence amount (do not fabricate 0).
        // Handle single transaction with amount 0 fallback (parallel to multi fallback above)
        if (! empty($result['success']) && isset($result['transaction']) && ($result['transaction']->amount ?? 0) == 0 && $fallbackAmount && $fallbackAmount > 0) {
            Log::warning('Evidence LLM: single amount 0 fallback ke parsed amount', ['evidence_id' => $evidence->id, 'fallback' => $fallbackAmount, 'caption' => $captionHint]);
            $result['transaction']->amount = $fallbackAmount;
            // Also update draft if exists? For single, no draft, but ensure is_cleared logic re-evaluated
        }
        if (! empty($result['success']) && isset($result['draft']) && ($result['draft']->payload['amount'] ?? 0) == 0 && $fallbackAmount && $fallbackAmount > 0) {
            Log::warning('Evidence LLM: single draft amount 0 fallback', ['evidence_id' => $evidence->id, 'fallback' => $fallbackAmount]);
            $result['draft']->payload['amount'] = $fallbackAmount;
        }

        // SPEC §10-11: Caption wallet enrichment — if LLM didn't extract wallet but hintWallet exists, enrich it
        if (! empty($result['success']) && isset($result['transaction']) && empty($result['transaction']->sourceWallet) && $hintWallet) {
            $result['transaction']->sourceWallet = $hintWallet->name;
            Log::info('Evidence LLM: enriched single wallet from caption hint', ['evidence_id' => $evidence->id, 'wallet' => $hintWallet->name]);
        }
        if (! empty($result['success']) && isset($result['draft']) && empty($result['draft']->payload['source_wallet_name'] ?? $result['draft']->payload['source_wallet'] ?? null) && $hintWallet) {
            $result['draft']->payload['source_wallet_name'] = $hintWallet->name;
            $result['draft']->payload['source_wallet_id'] = $hintWallet->id;
            Log::info('Evidence LLM: enriched draft wallet from caption hint', ['evidence_id' => $evidence->id, 'wallet' => $hintWallet->name]);
        }
        // Multi wallet enrichment: if multi items have null wallet but hintWallet exists, enrich each
        if (! empty($result['is_multi']) && isset($result['multi_result']) && $hintWallet) {
            foreach ($result['multi_result']->results as $it) {
                if ($it->isSuccess() && $it->transaction && empty($it->transaction->sourceWallet)) {
                    $it->transaction->sourceWallet = $hintWallet->name;
                }
                if ($it->isSuccess() && $it->draft && empty($it->draft->payload['source_wallet_name'] ?? $it->draft->payload['source_wallet'] ?? null)) {
                    $it->draft->payload['source_wallet_name'] = $hintWallet->name;
                    $it->draft->payload['source_wallet_id'] = $hintWallet->id;
                }
            }
        }

        // Post-process: samakan notes = description (jika LLM isi notes tapi description kosong, atau sebaliknya)
        // Dan pastikan wallet null → biar QuickWalletPicker muncul (jangan default ke Dompet Cash)
        if (! empty($result['is_multi']) && isset($result['multi_result'])) {
            foreach ($result['multi_result']->results as $item) {
                if ($item->isSuccess() && $item->transaction) {
                    $trx = $item->transaction;
                    // Samakan notes & description
                    if (blank($trx->notes) && ! blank($trx->subject)) {
                        $trx->notes = $trx->subject;
                    } elseif (! blank($trx->notes) && blank($trx->subject)) {
                        $trx->subject = $trx->notes;
                    } elseif (! blank($trx->notes)) {
                        $trx->subject = $trx->notes;
                    }
                }
                if ($item->isSuccess() && $item->draft) {
                    $draft = $item->draft;
                    $payload = $draft->payload ?? [];
                    // Samakan notes/description di payload
                    if (! empty($payload['notes']) && empty($payload['subject'])) {
                        $payload['subject'] = $payload['notes'];
                    } elseif (empty($payload['notes']) && ! empty($payload['subject'])) {
                        $payload['notes'] = $payload['subject'];
                    }
                    $draft->payload = $payload;
                    // Jika draft masih needs_wallet, biarkan → QuickWalletPicker akan handle
                }
            }
        } elseif (! empty($result['success']) && isset($result['transaction'])) {
            $trx = $result['transaction'];
            if (! blank($trx->notes) && blank($trx->subject)) {
                $trx->subject = $trx->notes;
            } elseif (blank($trx->notes) && ! blank($trx->subject)) {
                $trx->notes = $trx->subject;
            }
        }

        return $result;
    }

    /**
     * @return array{source:Wallet|null,destination:Wallet|null}
     */
    private function resolveWalletRoles(string $hint, User $user): array
    {
        $roles = ['source' => null, 'destination' => null];
        $lower = mb_strtolower(trim($hint));
        if ($lower === '') {
            return $roles;
        }

        $wallets = $user->wallets()->where('group_type', '!=', 'System')->get();
        foreach ($wallets as $wallet) {
            $aliases = [$wallet->name];
            foreach (explode(',', $wallet->keyword ?? '') as $keyword) {
                $aliases[] = trim($keyword);
            }
            $aliases = array_values(array_unique(array_filter($aliases, fn (string $a) => $a !== '')));

            foreach ($aliases as $alias) {
                $needle = mb_strtolower($alias);
                if (mb_strlen($needle) < 2) {
                    continue;
                }

                $offset = 0;
                while (($position = mb_strpos($lower, $needle, $offset)) !== false) {
                    $beforeLength = min(24, $position);
                    $before = mb_substr($lower, $position - $beforeLength, $beforeLength);
                    $role = $this->walletRoleFromPrefix($before);
                    if ($roles[$role] === null) {
                        $roles[$role] = $wallet;
                    }
                    $offset = $position + mb_strlen($needle);
                }
            }
        }

        return $roles;
    }

    /**
     * Tentukan peran wallet dari kata sebelum penyebutannya.
     * Default sumber: "bayar pakai dana", "dari seabank".
     */
    private function walletRoleFromPrefix(string $before): string
    {
        $tail = trim((string) preg_replace('/\s+/u', ' ', $before));

        if (preg_match('/(?:^|\s)(?:ke|tujuan|menuju)\s*(?:rekening|akun|dompet|wallet)?\s*$/u', $tail)) {
            return 'destination';
        }
        if (preg_match('/(?:^|\s)(?:masuk|pindah|transfer|ditransfer|kirim|dikirim|top\s*up|topup|isi\s+saldo)\s*(?:ke|saldo|rekening|akun|dompet|wallet)*\s*$/u', $tail)) {
            return 'destination';
        }

        return 'source';
    }

    /**
     * @param  array{source:Wallet|null,destination:Wallet|null}  $roles
     * @return list<string>
     */
    private function walletHintLines(array $roles): array
    {
        $lines = [];
        if ($roles['source']) {
            $lines[] = "[Source wallet hint: {$roles['source']->name}]";
        }
        if ($roles['destination']) {
            $lines[] = "[Destination wallet hint: {$roles['destination']->name}]";
        }

        return $lines;
    }

    /**
     * @param  array{source:Wallet|null,destination:Wallet|null}  $roles
     * @return array{source:string|null,destination:string|null}
     */
    private function walletRoleLog(array $roles): array
    {
        return [
            'source' => $roles['source']?->name,
            'destination' => $roles['destination']?->name,
        ];
    }

    private function detectWalletInOcr(string $ocrText, User $user): ?Wallet
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
