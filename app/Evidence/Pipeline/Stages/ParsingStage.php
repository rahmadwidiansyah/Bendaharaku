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
 * Mendukung: TransferReceipt, ShoppingReceipt.
 * Parser lain (QRIS, BankStatement, dll) ditambahkan di sini.
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

        // Jika LLM primary, coba LLM dulu
        if ($llmEnabled && $useLlmPrimary && ! blank($ocrText)) {
            $llmData = $this->llmParser->parse($context->evidence, $ocrText);
            if ($llmData !== null) {
                $parsedData = $llmData;
                Log::channel('evidence')->info('Parsing via LLM primary', ['evidence_id' => $context->evidence->id]);
            }
        }

        // Fallback / default: regex parser per document_type
        if ($parsedData === null) {
            $parsedData = match ($context->documentType) {
                DocumentType::QrisReceipt => $this->parseQrisReceipt($context),
                DocumentType::ShoppingReceipt => $this->parseShoppingReceipt($context),
                default => $this->parseTransferReceipt($context),
            };
        }

        // Jika regex hasilnya low confidence / amount null → coba LLM fallback (sesuai request: OCR text dikirim ke LLM API biar langsung dikelompokkan)
        $needsLlmFallback = $llmEnabled
            && ! $useLlmPrimary
            && ! blank($ocrText)
            && ($parsedData === null || $parsedData->amount === null || $parsedData->confidence < $threshold);

        if ($needsLlmFallback) {
            Log::channel('evidence')->info('Parsing low confidence, trying LLM fallback', [
                'evidence_id' => $context->evidence->id,
                'regex_confidence' => $parsedData?->confidence,
                'threshold' => $threshold,
            ]);
            $llmData = $this->llmParser->parse($context->evidence, $ocrText);
            if ($llmData !== null) {
                $parsedData = $llmData;
            }
        }

        $context->parsedData = $parsedData;

        $duration = (int) ((microtime(true) - $start) * 1000);
        $context->recordStageDuration('PARSE', $duration);

        Log::channel('evidence')->info('Parsing stage completed', [
            'evidence_id' => $context->evidence->id,
            'document_type' => $context->documentType?->value,
            'amount' => $parsedData?->amount,
            'reference' => $parsedData?->referenceNumber,
            'merchant' => $parsedData?->merchantName,
            'confidence' => $parsedData?->confidence,
            'duration_ms' => $duration,
        ]);

        $next($context);
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
}
