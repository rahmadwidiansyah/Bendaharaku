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
    ) {}

    public function handle(EvidenceContext $context, Closure $next): void
    {
        $start = microtime(true);

        Log::channel('evidence')->info('Parsing stage started', [
            'evidence_id' => $context->evidence->id,
            'document_type' => $context->documentType?->value,
        ]);

        // Dispatch ke parser berdasarkan document type
        $parsedData = match ($context->documentType) {
            DocumentType::QrisReceipt => $this->parseQrisReceipt($context),
            DocumentType::ShoppingReceipt => $this->parseShoppingReceipt($context),
            default => $this->parseTransferReceipt($context),
        };

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
