<?php

declare(strict_types=1);

namespace App\Evidence\Parsers;

use App\Enums\DocumentType;
use App\Evidence\DTO\EvidenceData;
use App\Evidence\Parsers\Extractors\AccountExtractor;
use App\Evidence\Parsers\Extractors\AmountExtractor;
use App\Evidence\Parsers\Extractors\DateExtractor;
use App\Evidence\Parsers\Extractors\DescriptionExtractor;
use App\Evidence\Parsers\Extractors\NameExtractor;
use App\Evidence\Parsers\Extractors\ReferenceExtractor;
use App\Evidence\Parsers\Extractors\WalletExtractor;
use App\Models\Evidence;
use Illuminate\Support\Facades\Log;

/**
 * TransferReceiptParser — Parser untuk dokumen jenis TRANSFER_RECEIPT.
 *
 * Hanya dijalankan apabila document_type == TRANSFER_RECEIPT.
 * Mengekstrak informasi dari OCR text menggunakan extractor kecil.
 *
 * Tidak melakukan: lookup DB, wallet matching, AI, duplicate detection.
 */
class TransferReceiptParser
{
    private AmountExtractor $amountExtractor;

    private ReferenceExtractor $referenceExtractor;

    private DateExtractor $dateExtractor;

    private AccountExtractor $accountExtractor;

    private NameExtractor $nameExtractor;

    private WalletExtractor $walletExtractor;

    private DescriptionExtractor $descriptionExtractor;

    public function __construct(
        ?AmountExtractor $amountExtractor = null,
        ?ReferenceExtractor $referenceExtractor = null,
        ?DateExtractor $dateExtractor = null,
        ?AccountExtractor $accountExtractor = null,
        ?NameExtractor $nameExtractor = null,
        ?WalletExtractor $walletExtractor = null,
        ?DescriptionExtractor $descriptionExtractor = null,
    ) {
        $this->amountExtractor = $amountExtractor ?? new AmountExtractor;
        $this->referenceExtractor = $referenceExtractor ?? new ReferenceExtractor;
        $this->dateExtractor = $dateExtractor ?? new DateExtractor;
        $this->accountExtractor = $accountExtractor ?? new AccountExtractor;
        $this->nameExtractor = $nameExtractor ?? new NameExtractor;
        $this->walletExtractor = $walletExtractor ?? new WalletExtractor;
        $this->descriptionExtractor = $descriptionExtractor ?? new DescriptionExtractor;
    }

    /**
     * Parse OCR text dari transfer receipt menjadi EvidenceData.
     */
    public function parse(Evidence $evidence): EvidenceData
    {
        // Gunakan normalized_text jika tersedia, fallback ke raw ocr_text
        $ocrText = $evidence->normalized_text ?? $evidence->ocr_text ?? '';

        Log::info('Transfer receipt parser started', [
            'evidence_id' => $evidence->id,
            'uuid' => $evidence->uuid,
            'text_length' => strlen($ocrText),
        ]);

        // Ekstrak semua field
        $amountResult = $this->amountExtractor->extract($ocrText);
        $referenceResult = $this->referenceExtractor->extract($ocrText);
        $dateResult = $this->dateExtractor->extract($ocrText);
        $accountResult = $this->accountExtractor->extract($ocrText);
        $nameResult = $this->nameExtractor->extract($ocrText);
        $walletResult = $this->walletExtractor->extract($ocrText);
        $descriptionResult = $this->descriptionExtractor->extract($ocrText);

        // Hitung overall confidence
        $confidences = array_filter([
            $amountResult['confidence'],
            $referenceResult['confidence'],
            $dateResult['confidence'],
            $accountResult['confidence'],
            $nameResult['confidence'],
            $walletResult['confidence'],
            $descriptionResult['confidence'],
        ], fn ($c) => $c > 0);

        $overallConfidence = count($confidences) > 0
            ? array_sum($confidences) / count($confidences)
            : 0.0;

        // Build metadata
        $metadata = [
            'extractors' => [
                'amount' => ['found' => $amountResult['amount'] !== null, 'confidence' => $amountResult['confidence']],
                'reference' => ['found' => $referenceResult['reference_number'] !== null, 'confidence' => $referenceResult['confidence']],
                'date' => ['found' => $dateResult['transaction_time'] !== null, 'confidence' => $dateResult['confidence']],
                'account' => ['found' => $accountResult['account'] !== null, 'confidence' => $accountResult['confidence']],
                'name' => ['found' => $nameResult['name'] !== null, 'confidence' => $nameResult['confidence']],
                'wallet' => ['found' => $walletResult['wallet_name'] !== null, 'confidence' => $walletResult['confidence']],
                'description' => ['found' => $descriptionResult['description'] !== null, 'confidence' => $descriptionResult['confidence']],
            ],
        ];

        $result = new EvidenceData(
            documentType: DocumentType::TransferReceipt,
            rawText: $ocrText,
            walletName: $walletResult['wallet_name'],
            bankName: $walletResult['bank_name'],
            destinationName: $nameResult['name'],
            destinationAccount: $accountResult['account'],
            referenceNumber: $referenceResult['reference_number'],
            transactionType: 'TRANSFER',
            amount: $amountResult['amount'],
            currency: $amountResult['amount'] !== null ? 'IDR' : null,
            transactionTime: $dateResult['transaction_time'],
            description: $descriptionResult['description'],
            confidence: round($overallConfidence, 4),
            metadata: $metadata,
        );

        Log::info('Transfer receipt parser completed', [
            'evidence_id' => $evidence->id,
            'uuid' => $evidence->uuid,
            'amount' => $result->amount,
            'reference' => $result->referenceNumber,
            'wallet' => $result->walletName,
            'confidence' => $result->confidence,
        ]);

        return $result;
    }
}
