<?php

declare(strict_types=1);

namespace App\Services\Evidence;

use App\Enums\DocumentType;
use App\Evidence\DTO\EvidenceData;
use App\Evidence\Parsers\Extractors\NumberParser;
use App\Models\Evidence;
use App\Models\User;
use App\Services\AI\AiCredentialManager;
use App\Services\AI\AIManager;
use App\Services\AI\AiPreferenceManager;
use App\Services\AI\AiProviderFactory;
use Illuminate\Support\Facades\Log;

class LlmEvidenceParser
{
    public function __construct(
        private readonly AIManager $aiManager,
        private readonly ?AiPreferenceManager $preferenceManager = null,
        private readonly ?AiCredentialManager $credentialManager = null,
        private readonly ?AiProviderFactory $providerFactory = null,
    ) {}

    private function prefManager(): AiPreferenceManager
    {
        return $this->preferenceManager ?? app(AiPreferenceManager::class);
    }

    private function credManager(): AiCredentialManager
    {
        return $this->credentialManager ?? app(AiCredentialManager::class);
    }

    private function factory(): AiProviderFactory
    {
        return $this->providerFactory ?? app(AiProviderFactory::class);
    }

    /**
     * Parse OCR text via LLM (AI Parser) untuk pengelompokan transaksi otomatis.
     * Generic entry — delegates to semantic shopping parser if text looks like shopping receipt.
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

        // If text is shopping-like, use semantic shopping parser directly (bypass LocalRuleEngine)
        if ($this->isShoppingLikeText($ocrText) || $this->isProbablyShoppingReceipt($evidence)) {
            $shopping = $this->parseShoppingReceipt($evidence, $ocrText);
            if ($shopping !== null) {
                return $shopping;
            }
            // fell through -> try generic AIManager fallback below
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
                activeMemories: [],
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
            $merchant = $trx->category ?? $trx->notes ?? null;
            $walletName = $trx->sourceWallet ?? $trx->destinationWallet;

            // Validate amount
            $amount = $this->normalizeAmount($trx->amount);
            if ($amount === null || $amount <= 0) {
                Log::warning('LlmEvidenceParser: invalid amount from generic LLM', ['evidence_id' => $evidence->id, 'amount' => $trx->amount]);

                return null;
            }

            Log::info('LlmEvidenceParser: LLM success', [
                'evidence_id' => $evidence->id,
                'amount' => $amount,
                'category' => $trx->category,
                'provider' => $result->provider,
                'model' => $result->model,
                'confidence' => $result->confidence,
            ]);

            return new EvidenceData(
                documentType: DocumentType::ShoppingReceipt,
                rawText: $ocrText,
                walletName: $walletName,
                merchantName: $merchant,
                destinationName: $trx->subject,
                amount: $amount,
                currency: $amount > 0 ? 'IDR' : null,
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

    /**
     * Semantic document parser for SHOPPING_RECEIPT.
     * Sends full raw OCR text to LLM with strict instruction.
     * Backend validates response strictly before returning EvidenceData.
     */
    public function parseShoppingReceipt(Evidence $evidence, string $ocrText): ?EvidenceData
    {
        if (blank($ocrText)) {
            return null;
        }

        if (! config('evidence.llm.enabled', true)) {
            return null;
        }

        $user = User::find($evidence->user_id);
        if (! $user) {
            return null;
        }

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

        $prompt = $this->buildShoppingReceiptPrompt($ocrText, $wallets, $categories);

        try {
            $preference = $this->prefManager()->resolveActivePreference($user);
            if (! $preference) {
                Log::info('LlmEvidenceParser[shopping]: no LLM preference', ['evidence_id' => $evidence->id]);

                return null;
            }

            $credential = $this->credManager()->getCredential($user, $preference->provider);
            if (! $credential || blank($credential->api_key) || ! $credential->is_valid) {
                Log::info('LlmEvidenceParser[shopping]: credential invalid', ['evidence_id' => $evidence->id, 'provider' => $preference->provider->value]);

                return null;
            }

            $adapter = $this->factory()->make($preference->provider);
            $model = $preference->selected_model ?? $preference->provider->defaultModel();

            $rawJson = $adapter->generateText($prompt, $credential->api_key, $model);

            $data = $this->decodeJsonStrict($rawJson);
            if ($data === null) {
                Log::warning('LlmEvidenceParser[shopping]: JSON decode failed', ['evidence_id' => $evidence->id, 'raw' => substr($rawJson, 0, 1000)]);

                return null;
            }

            $validation = $this->validateShoppingReceiptResponse($data);
            if ($validation !== true) {
                Log::warning('LlmEvidenceParser[shopping]: validation failed', ['evidence_id' => $evidence->id, 'error' => $validation, 'data' => $data]);

                return null;
            }

            $amount = $this->normalizeAmount($data['amount']);
            if ($amount === null || $amount <= 0) {
                Log::warning('LlmEvidenceParser[shopping]: amount invalid after normalization', ['evidence_id' => $evidence->id, 'raw_amount' => $data['amount']]);

                return null;
            }

            $docTypeRaw = strtoupper(trim((string) ($data['document_type'] ?? 'SHOPPING_RECEIPT')));
            $docType = DocumentType::tryFrom($docTypeRaw) ?? DocumentType::ShoppingReceipt;

            // Enforce SHOPPING_RECEIPT for this parser; if LLM says otherwise but text is shopping-like, coerce
            if ($docType !== DocumentType::ShoppingReceipt) {
                // Only allow shopping; if LLM misclassifies, still treat as shopping but log warning
                Log::warning('LlmEvidenceParser[shopping]: document_type mismatch, coercing to SHOPPING_RECEIPT', ['evidence_id' => $evidence->id, 'got' => $docTypeRaw]);
                $docType = DocumentType::ShoppingReceipt;
            }

            $intentRaw = strtolower(trim((string) ($data['intent'] ?? 'expense')));
            $intent = in_array($intentRaw, ['expense', 'income', 'transfer'], true) ? strtoupper($intentRaw) : 'EXPENSE';
            // Shopping receipt must be expense
            if ($intent === 'INCOME') {
                $intent = 'EXPENSE';
            }

            $merchant = $data['merchant'] ?? null;
            $category = $data['category'] ?? null;
            $sourceWallet = $data['source_wallet'] ?? null;
            $destWallet = $data['destination_wallet'] ?? null;
            $reference = $data['reference'] ?? null;
            $confidence = isset($data['confidence']) ? (float) $data['confidence'] : 0.90;
            $confidence = max(0.0, min(1.0, $confidence));

            // Resolve merchant/category fallback: if merchant null use category as merchantName
            $merchantName = $merchant ?? $category;

            Log::info('LlmEvidenceParser[shopping]: LLM semantic success', [
                'evidence_id' => $evidence->id,
                'amount' => $amount,
                'merchant' => $merchant,
                'category' => $category,
                'provider' => $preference->provider->value,
                'model' => $model,
                'confidence' => $confidence,
            ]);

            return new EvidenceData(
                documentType: $docType,
                rawText: $ocrText,
                walletName: $sourceWallet,
                merchantName: $merchantName,
                destinationName: $destWallet,
                referenceNumber: $reference,
                amount: $amount,
                currency: 'IDR',
                transactionType: $intent,
                description: $merchant ?? $category ?? 'Shopping receipt',
                confidence: round($confidence, 4),
                metadata: [
                    'engine' => 'LLM_SHOPPING_SEMANTIC',
                    'provider' => $preference->provider->value,
                    'model' => $model,
                    'llm_confidence' => $confidence,
                    'intent' => $intentRaw,
                    'category' => $category,
                    'source_wallet' => $sourceWallet,
                    'via' => 'ocr_llm_shopping_semantic',
                    'raw_llm_json' => $data,
                ],
            );
        } catch (\Throwable $e) {
            Log::warning('LlmEvidenceParser[shopping] failed: '.$e->getMessage(), [
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

    private function buildShoppingReceiptPrompt(string $ocrText, array $wallets, array $categories): string
    {
        $walletsJson = json_encode($wallets, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $categoriesJson = json_encode($categories, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
Kamu adalah semantic document parser untuk Bendaharaku (aplikasi catatan keuangan Indonesia).
Tugas: pahami seluruh raw OCR text dari foto struk belanja/restaurant/marketplace dan kembalikan SATU JSON valid (tanpa markdown, tanpa ```json).

=== RAW OCR TEXT ===
"""
{$ocrText}
"""

=== KONTEKS USER ===
- Available wallets: {$walletsJson}
- Available categories: {$categoriesJson}

=== ATURAN PALING PENTING: NOMINAL (amount) ===
OCR mengandung BANYAK angka yang BUKAN nominal transaksi. JANGAN salah ambil:
- nomor HP (mis. 081225223086, 6285700991499)
- kode pos / alamat (mis. 5029, 32362, "Semarang, Jawa Tengah, 5029")
- tanggal (05 Sep 2026, 18/07/2026)
- jam (19:33)
- nomor receipt / order ID / SKU (ReceiptNumber419408, Order ID DSA004, 50283, 260714NGR75UK7)
- quantity (PA 3.00, 1, 2 x)
- harga item satuan (Rp39.100)
- subtotal (Rp39.100, 23.000)
- diskon ( -Rp4.614, -Rp6.898)
- pajak / biaya layanan (Rp2.000)

Untuk field "amount", PRIORITASKAN nominal yang secara kontekstual merupakan:
- TOTAL
- GRAND TOTAL
- TOTAL PEMBAYARAN
- TOTAL BAYAR
- AMOUNT PAID
- JUMLAH BAYAR
- TOTAL BELANJA

Aturan tambahan amount:
- Jangan memilih angka hanya karena urutan kemunculan pertama.
- Jangan memilih angka hanya karena merupakan angka terbesar.
- Prioritaskan baris yang mengandung kata TOTAL / PEMBAYARAN di dekat footer struk.
- Jika ada "Total Pembayaran Rp29.588" di footer, maka amount = 29588 (bukan 32362 kode pos, bukan 39100 harga item).
- Jika ada "49,000" di baris akhir sebagai total, maka amount = 49000 (bukan 5029 kode pos di alamat).
- Normalisasi: "49,000" -> 49000, "Rp29.588" -> 29588, "39.100" -> 39100.

=== CONTOH YANG HARUS BENAR ===
OCR:
"""
Burjo Mabar TART A
Jl. Kesatrian No. K7, Jab nga
h, Semarang, Jawa Tengah, 5029
081225223086
05 Sep 2026 19:33
ReceiptNumber419408
Order ID DSA004 |
Collected By Kasir Burjp Ma
Nasi Ayam Bali Crisp
Magelangan Rendang
PA 3.00
Ais...
49,000
"""
Maka output harus:
{"document_type":"SHOPPING_RECEIPT","intent":"expense","amount":49000,"merchant":"Burjo Mabar","category":"Jajan & Nongkrong","source_wallet":null,"destination_wallet":null,"reference":null,"confidence":0.95}

OCR:
"""
Nota Pesanan
Nama Pembeli: Umi Mutoharoh Nama Penjual: UGREEN Official Store
Alamat Pembeli: Jalan Poros bunga jaya, KA8. OGAN KOMERING ULU TIMUR, MADANG SUKU I, SUMATERA SELATAN, ID, 32362
No. Handphone Pembeli: 6285700991499
No. Pesanan 260714NGR75UK7 18/07/2026 Cash on Delivery Hemat Kargo
Rincian Pesanan
UGREEN Adapter OTG USB 3.1 Type C To USB 3.0 Female 50283 50283 Rp39.100 1 Rp39.100
Subtotal Rp39.100
Total Kuantitas (Aktif) 1 produk
Subtotal Pesanan Rp39.100
Biaya Layanan Rp2.000
Diskon Voucher Toko -Rp4.614
Diskon Voucher Shopee -Rp6.898
Total Pembayaran Rp29.588
"""
Maka: {"document_type":"SHOPPING_RECEIPT","intent":"expense","amount":29588,"merchant":"UGREEN Official Store","category":"Belanja","source_wallet":null,"destination_wallet":null,"reference":"260714NGR75UK7","confidence":0.95}
Perhatikan: 32362 adalah kode pos (JANGAN dipilih), 50283 adalah SKU, 39100 adalah harga item/subtotal (bukan total final). Yang benar 29588 dari "Total Pembayaran".

=== OUTPUT SCHEMA (WAJIB) ===
Return ONLY valid JSON object dengan field:
{
  "document_type": "SHOPPING_RECEIPT",
  "intent": "expense" | "income" | "transfer" (shopping selalu expense),
  "amount": number (integer IDR, >0, tanpa Rp, tanpa separator),
  "merchant": string | null (nama toko/restaurant, mis. "Burjo Mabar", "UGREEN Official Store", "Indomaret"),
  "category": string | null (kategori belanja, mis. "Jajan & Nongkrong", "Belanja", "Makan & Minum"),
  "source_wallet": string | null,
  "destination_wallet": string | null,
  "reference": string | null (order ID / receipt number jika ada),
  "confidence": number 0.0-1.0
}

JANGAN tambahkan field lain. JANGAN bungkus dengan markdown. Response HARUS bisa di-json_decode.

PROMPT;
    }

    /**
     * Decode JSON strictly, strip markdown fences if present.
     */
    private function decodeJsonStrict(string $raw): ?array
    {
        $clean = trim(preg_replace('/\A\s*```(?:json)?\s*|\s*```\s*\z/i', '', $raw));
        // Some LLMs wrap with extra text; try to extract first { ... } block
        if (! str_starts_with($clean, '{')) {
            if (preg_match('/\{.*\}/s', $clean, $m)) {
                $clean = $m[0];
            }
        }
        $data = json_decode($clean, true);
        if (! is_array($data)) {
            return null;
        }

        return $data;
    }

    private function validateShoppingReceiptResponse(array $data): bool|string
    {
        $required = ['document_type', 'intent', 'amount', 'confidence'];
        foreach ($required as $f) {
            if (! array_key_exists($f, $data)) {
                return "missing field {$f}";
            }
        }
        // Amount may be numeric or formatted string like "49,000" / "Rp29.588"
        $norm = $this->normalizeAmount($data['amount']);
        if ($norm === null || $norm <= 0) {
            return 'amount must be numeric >0 (got '.json_encode($data['amount']).')';
        }
        // Allow amount with decimal but must be positive; will normalize
        $doc = strtoupper(trim((string) $data['document_type']));
        $validDocs = ['SHOPPING_RECEIPT', 'TRANSFER_RECEIPT', 'QRIS_RECEIPT', 'UNKNOWN'];
        if (! in_array($doc, $validDocs, true)) {
            return "invalid document_type {$doc}";
        }
        if (isset($data['intent'])) {
            $intent = strtolower(trim((string) $data['intent']));
            if (! in_array($intent, ['expense', 'income', 'transfer', 'debt', 'receivable'], true)) {
                return "invalid intent {$intent}";
            }
        }
        if (isset($data['confidence'])) {
            $c = (float) $data['confidence'];
            if ($c < 0 || $c > 1) {
                return 'confidence must be 0.0-1.0';
            }
        }
        // merchant/category may be null or string
        foreach (['merchant', 'category', 'source_wallet', 'destination_wallet', 'reference'] as $opt) {
            if (array_key_exists($opt, $data) && $data[$opt] !== null && ! is_string($data[$opt])) {
                return "{$opt} must be string or null";
            }
        }

        return true;
    }

    /**
     * Normalize amount from LLM (handles string "49,000", "Rp29.588", int, float)
     */
    public function normalizeAmount(mixed $raw): ?float
    {
        if (is_int($raw) || is_float($raw)) {
            return (float) $raw;
        }
        if (is_string($raw)) {
            $trimmed = trim($raw);
            // Remove Rp/IDR prefix
            $trimmed = preg_replace('/^\s*(rp|idr)\s*\.?\s*/i', '', $trimmed);
            $trimmed = trim($trimmed);
            // If purely numeric string with separators, use NumberParser
            // NumberParser expects format like "49,000" or "29.588"
            try {
                $parsed = NumberParser::parse($trimmed);
                if ($parsed > 0) {
                    return $parsed;
                }
            } catch (\Throwable) {
            }
            // Fallback: remove all non-digit except . ,
            $clean = preg_replace('/[^0-9.,]/', '', $trimmed);
            if ($clean === '' || $clean === null) {
                return null;
            }
            try {
                $parsed2 = NumberParser::parse($clean);

                return $parsed2 > 0 ? $parsed2 : null;
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    private function isShoppingLikeText(string $text): bool
    {
        $lower = mb_strtolower($text);
        // Heuristics from classifier + shopping keywords
        if (preg_match('/\b(subtotal|grand\s*total|total\s*pembayaran|total\s*belanja|item\s*details|order\s*id|nota\s*pesanan|rincian\s*pesanan|receipt\s*number|kasir|collected\s*by|quantity|produk\s*variasi)\b/iu', $text)) {
            return true;
        }
        // Known shopping merchants (burjo, indomaret etc) hint
        if (preg_match('/\b(burjo|indomaret|alfamart|shopee|tokopedia|ugreen|mcd|kfc|mixue|super\s*indo)\b/iu', $text)) {
            return true;
        }

        return false;
    }

    private function isProbablyShoppingReceipt(Evidence $evidence): bool
    {
        try {
            $type = $evidence->document_type ?? null;
            if ($type instanceof DocumentType && $type === DocumentType::ShoppingReceipt) {
                return true;
            }
            if (is_string($type) && strtoupper($type) === 'SHOPPING_RECEIPT') {
                return true;
            }
        } catch (\Throwable) {
        }

        return false;
    }
}
