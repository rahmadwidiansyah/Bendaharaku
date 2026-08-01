<?php

declare(strict_types=1);

namespace App\Evidence\Parsers;

use App\Enums\DocumentType;
use App\Evidence\DTO\EvidenceData;
use App\Evidence\Parsers\Extractors\AcquirerExtractor;
use App\Evidence\Parsers\Extractors\AmountExtractor;
use App\Evidence\Parsers\Extractors\DateExtractor;
use App\Evidence\Parsers\Extractors\IssuerExtractor;
use App\Evidence\Parsers\Extractors\ReferenceExtractor;
use App\Evidence\Parsers\Extractors\StatusExtractor;
use App\Evidence\Parsers\Extractors\TerminalIdExtractor;
use App\Evidence\Parsers\Extractors\TimeExtractor;
use App\Evidence\Parsers\Extractors\WalletExtractor;
use App\Models\Evidence;
use Illuminate\Support\Facades\Log;

class QrisReceiptParser
{
    private AmountExtractor $amountExtractor;

    private ReferenceExtractor $referenceExtractor;

    private DateExtractor $dateExtractor;

    private WalletExtractor $walletExtractor;

    private IssuerExtractor $issuerExtractor;

    private AcquirerExtractor $acquirerExtractor;

    private StatusExtractor $statusExtractor;

    private TerminalIdExtractor $terminalIdExtractor;

    private TimeExtractor $timeExtractor;

    private array $merchantAliases;

    public function __construct(
        ?AmountExtractor $amountExtractor = null,
        ?ReferenceExtractor $referenceExtractor = null,
        ?DateExtractor $dateExtractor = null,
        ?WalletExtractor $walletExtractor = null,
        ?IssuerExtractor $issuerExtractor = null,
        ?AcquirerExtractor $acquirerExtractor = null,
        ?StatusExtractor $statusExtractor = null,
        ?TerminalIdExtractor $terminalIdExtractor = null,
        ?TimeExtractor $timeExtractor = null,
    ) {
        $this->amountExtractor = $amountExtractor ?? new AmountExtractor;
        $this->referenceExtractor = $referenceExtractor ?? new ReferenceExtractor;
        $this->dateExtractor = $dateExtractor ?? new DateExtractor;
        $this->walletExtractor = $walletExtractor ?? new WalletExtractor;
        $this->issuerExtractor = $issuerExtractor ?? new IssuerExtractor;
        $this->acquirerExtractor = $acquirerExtractor ?? new AcquirerExtractor;
        $this->statusExtractor = $statusExtractor ?? new StatusExtractor;
        $this->terminalIdExtractor = $terminalIdExtractor ?? new TerminalIdExtractor;
        $this->timeExtractor = $timeExtractor ?? new TimeExtractor;
        $this->merchantAliases = config('qris_parser.merchant_aliases', []);
    }

    public function parse(Evidence $evidence): EvidenceData
    {
        $ocrText = $evidence->normalized_text ?? $evidence->ocr_text ?? '';

        Log::info('QRIS parsing started', [
            'evidence_id' => $evidence->id,
            'uuid' => $evidence->uuid,
            'text_length' => strlen($ocrText),
        ]);

        $merchantResult = $this->extractMerchant($ocrText);
        $amountResult = $this->amountExtractor->extract($ocrText);
        $walletResult = $this->walletExtractor->extract($ocrText);
        $referenceResult = $this->referenceExtractor->extract($ocrText);
        $dateResult = $this->dateExtractor->extract($ocrText);
        $timeResult = $this->timeExtractor->extract($ocrText);
        $issuerResult = $this->issuerExtractor->extract($ocrText);
        $acquirerResult = $this->acquirerExtractor->extract($ocrText);
        $statusResult = $this->statusExtractor->extract($ocrText);
        $terminalResult = $this->terminalIdExtractor->extract($ocrText);

        Log::info('QRIS merchant found', [
            'evidence_id' => $evidence->id,
            'merchant' => $merchantResult['merchant_name'],
            'confidence' => $merchantResult['confidence'],
        ]);

        Log::info('QRIS wallet found', [
            'evidence_id' => $evidence->id,
            'wallet' => $walletResult['wallet_name'],
            'confidence' => $walletResult['confidence'],
        ]);

        Log::info('QRIS amount found', [
            'evidence_id' => $evidence->id,
            'amount' => $amountResult['amount'],
            'confidence' => $amountResult['confidence'],
        ]);

        // Build transaction_time from date + time
        $transactionTime = $this->combineDateTime(
            $dateResult['transaction_time'],
            $timeResult['time'],
        );

        // Compute per-field confidence
        $confidences = array_filter([
            $merchantResult['confidence'],
            $amountResult['confidence'],
            $walletResult['confidence'],
            $referenceResult['confidence'],
            $dateResult['confidence'] > 0 || $timeResult['confidence'] > 0 ? max($dateResult['confidence'], $timeResult['confidence']) : 0,
        ], fn ($c) => $c > 0);

        $overallConfidence = count($confidences) > 0
            ? array_sum($confidences) / count($confidences)
            : 0.0;

        $metadata = [
            'extractors' => [
                'merchant' => ['found' => $merchantResult['merchant_name'] !== null, 'confidence' => $merchantResult['confidence']],
                'amount' => ['found' => $amountResult['amount'] !== null, 'confidence' => $amountResult['confidence']],
                'wallet' => ['found' => $walletResult['wallet_name'] !== null, 'confidence' => $walletResult['confidence']],
                'reference' => ['found' => $referenceResult['reference_number'] !== null, 'confidence' => $referenceResult['confidence']],
                'date' => ['found' => $dateResult['transaction_time'] !== null, 'confidence' => $dateResult['confidence']],
                'time' => ['found' => $timeResult['time'] !== null, 'confidence' => $timeResult['confidence']],
                'issuer' => ['found' => $issuerResult['issuer'] !== null, 'confidence' => $issuerResult['confidence']],
                'acquirer' => ['found' => $acquirerResult['acquirer'] !== null, 'confidence' => $acquirerResult['confidence']],
                'status' => ['found' => true, 'confidence' => $statusResult['confidence']],
                'terminal_id' => ['found' => $terminalResult['terminal_id'] !== null, 'confidence' => $terminalResult['confidence']],
            ],
            'parsing_duration_ms' => 0,
            'merchant_confidence' => $merchantResult['confidence'],
            'wallet_confidence' => $walletResult['confidence'],
            'amount_confidence' => $amountResult['confidence'],
            'overall_confidence' => round($overallConfidence, 4),
        ];

        $result = new EvidenceData(
            documentType: DocumentType::QrisReceipt,
            rawText: $ocrText,
            walletName: $walletResult['wallet_name'],
            merchantName: $merchantResult['merchant_name'],
            referenceNumber: $referenceResult['reference_number'],
            transactionType: 'EXPENSE',
            amount: $amountResult['amount'],
            currency: $amountResult['amount'] !== null ? 'IDR' : null,
            transactionTime: $transactionTime,
            paymentMethod: 'QRIS',
            confidence: round($overallConfidence, 4),
            metadata: $metadata,
            terminalId: $terminalResult['terminal_id'],
            issuer: $issuerResult['issuer'],
            acquirer: $acquirerResult['acquirer'],
            transactionStatus: $statusResult['transaction_status'],
            date: $dateResult['transaction_time'] ? explode('T', $dateResult['transaction_time'])[0] : null,
            time: $timeResult['time'],
        );

        Log::info('QRIS parsing finished', [
            'evidence_id' => $evidence->id,
            'uuid' => $evidence->uuid,
            'merchant' => $result->merchantName,
            'amount' => $result->amount,
            'wallet' => $result->walletName,
            'reference' => $result->referenceNumber,
            'confidence' => $result->confidence,
        ]);

        return $result;
    }

    private function extractMerchant(string $text): array
    {
        $lines = array_map('trim', explode("\n", $text));

        // 1. Label-based: cari "Merchant" atau label sejenis diikuti value
        foreach ($this->merchantLabelPatterns() as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $name = trim(preg_replace('/\s+/', ' ', $matches[1]));
                $canonical = $this->resolveMerchantAlias($name);
                if ($canonical !== null) {
                    return [
                        'merchant_name' => $canonical,
                        'confidence' => 0.95,
                        'raw' => $name,
                    ];
                }
                if (strlen($name) >= 3) {
                    return [
                        'merchant_name' => $name,
                        'confidence' => 0.7,
                        'raw' => $name,
                    ];
                }
            }
        }

        // 2. Line-based: cari baris dengan merchant aliases
        foreach ($lines as $line) {
            $canonical = $this->resolveMerchantAlias($line);
            if ($canonical !== null) {
                return [
                    'merchant_name' => $canonical,
                    'confidence' => 0.95,
                    'raw' => $line,
                ];
            }
        }

        // 3. Fallback: baris pertama yang pendek dan bukan keyword QRIS umum
        foreach ($lines as $line) {
            if (strlen($line) >= 3 && strlen($line) <= 50) {
                $lower = strtolower($line);
                if (in_array($lower, ['qris', 'qris payment', 'pembayaran berhasil', 'pembayaran', 'berhasil', 'sukses', 'qr code'])) {
                    continue;
                }
                if (preg_match('/^(total|nominal|jumlah|ref|tanggal|jam|status|metode)/i', $line)) {
                    continue;
                }

                return [
                    'merchant_name' => $line,
                    'confidence' => 0.5,
                    'raw' => $line,
                ];
            }
        }

        return [
            'merchant_name' => null,
            'confidence' => 0.0,
            'raw' => null,
        ];
    }

    private function resolveMerchantAlias(string $name): ?string
    {
        $lower = strtolower($name);
        foreach ($this->merchantAliases as $canonical => $aliases) {
            foreach ($aliases as $alias) {
                if (str_contains($lower, strtolower($alias))) {
                    return $canonical;
                }
            }
        }

        return null;
    }

    private function merchantLabelPatterns(): array
    {
        return config('qris_parser.merchant_label_patterns', [
            '/merchant[:\s]*\n*([A-Za-z0-9\s&.,\-]+)/i',
            '/pedagang[:\s]*\n*([A-Za-z0-9\s&.,\-]+)/i',
        ]);
    }

    private function combineDateTime(?string $isoDateTime, ?string $time): ?string
    {
        if ($isoDateTime !== null) {
            return $isoDateTime;
        }

        if ($time !== null) {
            $datePart = now()->format('Y-m-d');

            return "{$datePart}T{$time}:00";
        }

        return null;
    }
}
