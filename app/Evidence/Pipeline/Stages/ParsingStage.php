<?php

declare(strict_types=1);

namespace App\Evidence\Pipeline\Stages;

use App\Enums\DocumentType;
use App\Evidence\DTO\EvidenceData;
use App\Evidence\Parsers\QrisReceiptParser;
use App\Evidence\Parsers\ShoppingReceiptParser;
use App\Evidence\Parsers\TransferReceiptParser;
use App\Evidence\Pipeline\Context\EvidenceContext;
use App\Evidence\Pipeline\Contracts\EvidenceStage;
use App\Services\Evidence\LlmEvidenceParser;
use Closure;
use Illuminate\Support\Facades\Log;

/**
 * ParsingStage — Parse teks OCR menjadi EvidenceData berdasarkan document type.
 *
 * Arsitektur baru untuk SHOPPING_RECEIPT:
 *   OCR raw text -> LLM semantic parser (text -> structured JSON) -> backend validation -> fallback regex
 * LLM menjadi sumber utama, bukan fallback. Regex hanya dipakai jika LLM invalid/gagal.
 *
 * Untuk TRANSFER_RECEIPT / QRIS tetap regex primary, LLM hanya fallback jika confidence rendah.
 *
 * Tesseract tetap primary OCR, RapidOCR tetap fallback OCR (di OCRStage).
 */
class ParsingStage implements EvidenceStage
{
    public function __construct(
        private TransferReceiptParser $transferReceiptParser,
        private ShoppingReceiptParser $shoppingReceiptParser,
        private QrisReceiptParser $qrisReceiptParser,
        private ?LlmEvidenceParser $llmParser = null,
    ) {
        $this->llmParser ??= app(LlmEvidenceParser::class);
    }

    public function handle(EvidenceContext $context, Closure $next): void
    {
        $start = microtime(true);

        Log::channel('evidence')->info('Parsing stage started', [
            'evidence_id' => $context->evidence->id,
            'document_type' => $context->documentType?->value,
        ]);

        $ocrText = $context->getTextForProcessing();
        $useLlmPrimary = (bool) config('evidence.llm.primary', false);
        $llmEnabled = (bool) config('evidence.llm.enabled', true);
        $threshold = (float) config('evidence.llm.fallback_threshold', 0.6);

        $parsedData = null;
        $engineUsed = null;

        // ── SHOPPING_RECEIPT: LLM semantic primary ──────────────────
        if ($context->documentType === DocumentType::ShoppingReceipt) {
            if ($llmEnabled && ! blank($ocrText)) {
                Log::channel('evidence')->info('Parsing SHOPPING_RECEIPT via LLM semantic primary', ['evidence_id' => $context->evidence->id]);
                $llmData = $this->tryShoppingLlm($context, $ocrText);
                if ($this->isValidParsedData($llmData)) {
                    $parsedData = $llmData;
                    $engineUsed = 'LLM_SHOPPING_SEMANTIC';
                    Log::channel('evidence')->info('Parsing SHOPPING_RECEIPT LLM success', ['evidence_id' => $context->evidence->id, 'amount' => $parsedData->amount]);
                } else {
                    if ($llmData !== null) {
                        Log::channel('evidence')->warning('Parsing SHOPPING_RECEIPT LLM invalid, fallback to regex', ['evidence_id' => $context->evidence->id, 'llm_amount' => $llmData->amount ?? null]);
                    } else {
                        Log::channel('evidence')->warning('Parsing SHOPPING_RECEIPT LLM returned null, fallback to regex', ['evidence_id' => $context->evidence->id]);
                    }
                    // Fallback to regex ShoppingReceiptParser
                    $parsedData = $this->parseShoppingReceipt($context);
                    $engineUsed = 'ShoppingReceiptParser(fallback)';
                    Log::channel('evidence')->info('Parsing SHOPPING_RECEIPT fallback regex result', ['evidence_id' => $context->evidence->id, 'amount' => $parsedData?->amount]);
                }
            } else {
                // LLM disabled or no text -> regex
                $parsedData = $this->parseShoppingReceipt($context);
                $engineUsed = 'ShoppingReceiptParser';
            }
        } else {
            // ── TRANSFER / QRIS / UNKNOWN: keep existing logic ─────────
            // Jika LLM primary global enabled, coba LLM dulu
            if ($llmEnabled && $useLlmPrimary && ! blank($ocrText)) {
                $llmData = $this->llmParser->parse($context->evidence, $ocrText);
                if ($llmData !== null && $this->isValidParsedData($llmData)) {
                    $parsedData = $llmData;
                    $engineUsed = 'LLM(primary)';
                    Log::channel('evidence')->info('Parsing via LLM primary', ['evidence_id' => $context->evidence->id]);
                }
            }

            // Extra: jika UNKNOWN tapi teks terlihat seperti shopping receipt, coba LLM shopping semantic dulu
            if ($parsedData === null && $context->documentType === DocumentType::Unknown && $llmEnabled && ! blank($ocrText) && $this->isShoppingLikeText($ocrText)) {
                Log::channel('evidence')->info('UNKNOWN but shopping-like, trying LLM shopping semantic before transfer', ['evidence_id' => $context->evidence->id]);
                $shoppingLlm = $this->tryShoppingLlm($context, $ocrText);
                if ($this->isValidParsedData($shoppingLlm)) {
                    $parsedData = $shoppingLlm;
                    $engineUsed = 'LLM_SHOPPING_SEMANTIC(unknown)';
                    Log::channel('evidence')->info('UNKNOWN shopping LLM success', ['evidence_id' => $context->evidence->id, 'amount' => $parsedData->amount]);
                }
            }

            // Fallback / default: regex parser per document_type
            if ($parsedData === null) {
                $parsedData = match ($context->documentType) {
                    DocumentType::QrisReceipt => $this->parseQrisReceipt($context),
                    DocumentType::ShoppingReceipt => $this->parseShoppingReceipt($context), // should not happen, handled above
                    default => $this->parseTransferReceipt($context),
                };
                if ($engineUsed === null) {
                    $engineUsed = match ($context->documentType) {
                        DocumentType::QrisReceipt => 'QrisReceiptParser',
                        DocumentType::ShoppingReceipt => 'ShoppingReceiptParser',
                        default => 'TransferReceiptParser',
                    };
                }
            }

            // Jika regex hasilnya low confidence / amount null → coba LLM fallback
            // Untuk UNKNOWN yang ternyata shopping-like, LlmEvidenceParser internal will route to shopping semantic
            $needsLlmFallback = $llmEnabled
                && ! $useLlmPrimary
                && $context->documentType !== DocumentType::ShoppingReceipt // shopping already handled
                && ! blank($ocrText)
                && ($parsedData === null || $parsedData->amount === null || $parsedData->confidence < $threshold);

            if ($needsLlmFallback) {
                Log::channel('evidence')->info('Parsing low confidence, trying LLM fallback', [
                    'evidence_id' => $context->evidence->id,
                    'regex_confidence' => $parsedData?->confidence,
                    'threshold' => $threshold,
                ]);
                $llmData = $this->llmParser->parse($context->evidence, $ocrText);
                if ($llmData !== null && $this->isValidParsedData($llmData)) {
                    $parsedData = $llmData;
                    $engineUsed = 'LLM(fallback)';
                } elseif ($llmData !== null) {
                    Log::channel('evidence')->warning('LLM fallback returned invalid data, keep regex', ['evidence_id' => $context->evidence->id]);
                }
            }
        }

        $context->parsedData = $parsedData;
        if ($engineUsed !== null) {
            $context->metadata['parser_engine'] = $engineUsed;
        }

        $duration = (int) ((microtime(true) - $start) * 1000);
        $context->recordStageDuration('PARSE', $duration);

        Log::channel('evidence')->info('Parsing stage completed', [
            'evidence_id' => $context->evidence->id,
            'document_type' => $context->documentType?->value,
            'amount' => $parsedData?->amount,
            'reference' => $parsedData?->referenceNumber,
            'merchant' => $parsedData?->merchantName,
            'confidence' => $parsedData?->confidence,
            'engine' => $engineUsed,
            'duration_ms' => $duration,
        ]);

        $next($context);
    }

    private function tryShoppingLlm(EvidenceContext $context, string $ocrText): ?EvidenceData
    {
        try {
            return $this->llmParser->parseShoppingReceipt($context->evidence, $ocrText);
        } catch (\Throwable $e) {
            Log::channel('evidence')->warning('Shopping LLM parse exception: '.$e->getMessage(), ['evidence_id' => $context->evidence->id]);

            return null;
        }
    }

    /**
     * Backend validation per spec:
     * - amount numeric and >0
     * - document type valid
     * - amount must be consistent (non-zero)
     */
    private function isValidParsedData(?EvidenceData $data): bool
    {
        if ($data === null) {
            return false;
        }
        if ($data->amount === null) {
            return false;
        }
        if (! is_numeric($data->amount)) {
            return false;
        }
        if ((float) $data->amount <= 0) {
            return false;
        }
        // document type must be valid enum (already typed)
        // confidence should be 0-1
        if ($data->confidence < 0 || $data->confidence > 1) {
            return false;
        }

        // For shopping, amount should not be suspicious postcode-like without context? Already validated via LLM prompt.
        // Allow any positive amount.
        return true;
    }

    private function parseTransferReceipt(EvidenceContext $context): ?EvidenceData
    {
        return $this->transferReceiptParser->parse($context->evidence);
    }

    private function parseShoppingReceipt(EvidenceContext $context): ?EvidenceData
    {
        return $this->shoppingReceiptParser->parse($context->evidence);
    }

    private function parseQrisReceipt(EvidenceContext $context): ?EvidenceData
    {
        return $this->qrisReceiptParser->parse($context->evidence);
    }

    private function isShoppingLikeText(string $text): bool
    {
        if (preg_match('/\b(subtotal|grand\s*total|total\s*pembayaran|total\s*belanja|item\s*details|order\s*id|nota\s*pesanan|rincian\s*pesanan|receipt\s*number|kasir|collected\s*by|quantity|produk\s*variasi)\b/iu', $text)) {
            return true;
        }
        if (preg_match('/\b(burjo|indomaret|alfamart|shopee|tokopedia|ugreen|mcd|kfc|mixue|super\s*indo)\b/iu', $text)) {
            return true;
        }
        // If text has many distractor numbers and a trailing total-like number near end, heuristics
        if (mb_strlen($text) > 80 && preg_match('/\b(total|jumlah|bayar)\b/iu', $text)) {
            return true;
        }

        return false;
    }
}
