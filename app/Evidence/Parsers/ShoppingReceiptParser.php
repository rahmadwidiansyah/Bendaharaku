<?php

declare(strict_types=1);

namespace App\Evidence\Parsers;

use App\Enums\DocumentType;
use App\Evidence\DTO\EvidenceData;
use App\Evidence\Parsers\Extractors\AmountExtractor;
use App\Evidence\Parsers\Extractors\DateExtractor;
use App\Evidence\Parsers\Extractors\ItemExtractor;
use App\Evidence\Parsers\Extractors\MerchantExtractor;
use App\Evidence\Parsers\Extractors\PaymentMethodExtractor;
use App\Evidence\Parsers\Extractors\ReceiptInfoExtractor;
use App\Evidence\Parsers\Extractors\SummaryExtractor;
use App\Models\Evidence;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * ShoppingReceiptParser — Parser untuk dokumen jenis SHOPPING_RECEIPT.
 *
 * Hanya dijalankan apabila document_type == SHOPPING_RECEIPT.
 * Mengekstrak informasi dari OCR text menggunakan extractor khusus belanja.
 *
 * Mendukung: Indomaret, Alfamart, Super Indo, McDonald's, KFC, dll.
 */
class ShoppingReceiptParser
{
    private MerchantExtractor $merchantExtractor;

    private SummaryExtractor $summaryExtractor;

    private ItemExtractor $itemExtractor;

    private PaymentMethodExtractor $paymentMethodExtractor;

    private DateExtractor $dateExtractor;

    private AmountExtractor $amountExtractor;

    private ReceiptInfoExtractor $receiptInfoExtractor;

    public function __construct(
        ?MerchantExtractor $merchantExtractor = null,
        ?SummaryExtractor $summaryExtractor = null,
        ?ItemExtractor $itemExtractor = null,
        ?PaymentMethodExtractor $paymentMethodExtractor = null,
        ?DateExtractor $dateExtractor = null,
        ?AmountExtractor $amountExtractor = null,
        ?ReceiptInfoExtractor $receiptInfoExtractor = null,
    ) {
        $this->merchantExtractor = $merchantExtractor ?? new MerchantExtractor;
        $this->summaryExtractor = $summaryExtractor ?? new SummaryExtractor;
        $this->itemExtractor = $itemExtractor ?? new ItemExtractor;
        $this->paymentMethodExtractor = $paymentMethodExtractor ?? new PaymentMethodExtractor;
        $this->dateExtractor = $dateExtractor ?? new DateExtractor;
        $this->amountExtractor = $amountExtractor ?? new AmountExtractor;
        $this->receiptInfoExtractor = $receiptInfoExtractor ?? new ReceiptInfoExtractor;
    }

    /**
     * Parse OCR text dari shopping receipt menjadi EvidenceData.
     */
    public function parse(Evidence $evidence): EvidenceData
    {
        $ocrText = $evidence->normalized_text ?? $evidence->ocr_text ?? '';

        try {
            Log::info('Shopping receipt parser started', [
                'evidence_id' => $evidence->id,
                'uuid' => $evidence->uuid,
                'text_length' => strlen($ocrText),
            ]);
        } catch (\Throwable) {
            // Log channel may not be available in tests
        }

        // Ekstrak semua field
        $merchantResult = $this->merchantExtractor->extract($ocrText);
        $summaryResult = $this->summaryExtractor->extract($ocrText);
        $itemResult = $this->itemExtractor->extract($ocrText);
        $paymentResult = $this->paymentMethodExtractor->extract($ocrText);
        $dateResult = $this->dateExtractor->extract($ocrText);
        $receiptInfoResult = $this->receiptInfoExtractor->extract($ocrText);

        // Build transaction time
        $transactionTime = $this->buildTransactionTime(
            $dateResult['transaction_time'],
            $receiptInfoResult['date'] ?? null,
            $receiptInfoResult['time'] ?? null,
        );

        // Build description
        $description = $this->buildDescription($merchantResult['merchant_name'], $itemResult['items']);

        // Build transaction type
        $transactionType = 'EXPENSE';

        // Build metadata
        $metadata = [
            'extractors' => [
                'merchant' => ['found' => $merchantResult['merchant_name'] !== null, 'confidence' => $merchantResult['confidence']],
                'summary' => ['found' => $summaryResult['total'] !== null, 'confidence' => $summaryResult['confidence']],
                'items' => ['found' => count($itemResult['items']) > 0, 'confidence' => $itemResult['confidence'], 'count' => count($itemResult['items'])],
                'payment_method' => ['found' => $paymentResult['payment_method'] !== null, 'confidence' => $paymentResult['confidence']],
                'date' => ['found' => $dateResult['transaction_time'] !== null, 'confidence' => $dateResult['confidence']],
                'receipt_info' => ['found' => $receiptInfoResult['receipt_number'] !== null || $receiptInfoResult['cashier'] !== null, 'confidence' => $receiptInfoResult['confidence']],
            ],
            'items' => array_map(fn ($item) => $item->toArray(), $itemResult['items']),
            'restaurant_merchants' => config('shopping_parser.restaurant_merchants', []),
            'retail_merchants' => config('shopping_parser.retail_merchants', []),
            'pharmacy_merchants' => config('shopping_parser.pharmacy_merchants', []),
        ];

        // Hitung overall confidence
        $confidences = array_filter([
            $merchantResult['confidence'],
            $summaryResult['confidence'],
            $itemResult['confidence'],
            $paymentResult['confidence'],
            $dateResult['confidence'],
            $receiptInfoResult['confidence'],
        ], fn ($c) => $c > 0);

        $overallConfidence = count($confidences) > 0
            ? array_sum($confidences) / count($confidences)
            : 0.0;

        $result = new EvidenceData(
            documentType: DocumentType::ShoppingReceipt,
            rawText: $ocrText,
            merchantName: $merchantResult['merchant_name'],
            transactionType: $transactionType,
            amount: $summaryResult['total'],
            currency: $summaryResult['total'] !== null ? 'IDR' : null,
            transactionTime: $transactionTime,
            description: $description,
            confidence: round($overallConfidence, 4),
            metadata: $metadata,
            subtotal: $summaryResult['subtotal'],
            tax: $summaryResult['tax'],
            discount: $summaryResult['discount'],
            serviceCharge: $summaryResult['service_charge'],
            paymentMethod: $paymentResult['payment_method'],
            receiptNumber: $receiptInfoResult['receipt_number'],
            cashier: $receiptInfoResult['cashier'],
            items: $itemResult['items'],
        );

        try {
            Log::info('Shopping receipt parser completed', [
                'evidence_id' => $evidence->id,
                'uuid' => $evidence->uuid,
                'merchant' => $result->merchantName,
                'amount' => $result->amount,
                'items_count' => count($result->items),
                'payment_method' => $result->paymentMethod,
                'confidence' => $result->confidence,
            ]);
        } catch (\Throwable) {
            // Log channel may not be available in tests
        }

        return $result;
    }

    /**
     * Build transaction time dari beberapa sumber.
     */
    private function buildTransactionTime(?string $dateExtractorResult, ?string $dateFromInfo, ?string $timeFromInfo): ?string
    {
        // Prioritas: DateExtractor result
        if ($dateExtractorResult !== null) {
            return $dateExtractorResult;
        }

        // Fallback: gabungkan date + time dari ReceiptInfo
        if ($dateFromInfo !== null) {
            $time = $timeFromInfo ?? '00:00:00';

            try {
                $parsed = Carbon::parse($dateFromInfo.' '.$time);

                return $parsed->format('Y-m-d\TH:i:s');
            } catch (\Throwable) {
                return $dateFromInfo;
            }
        }

        return null;
    }

    /**
     * Build description dari merchant dan items.
     */
    private function buildDescription(?string $merchantName, array $items): ?string
    {
        $parts = [];

        if ($merchantName !== null) {
            $parts[] = $merchantName;
        }

        if (count($items) > 0) {
            $itemNames = array_slice(array_map(fn ($item) => $item->name, $items), 0, 3);
            $parts[] = implode(', ', $itemNames);
            if (count($items) > 3) {
                $parts[] = '('.count($items).' items)';
            }
        }

        return count($parts) > 0 ? implode(' - ', $parts) : null;
    }
}
