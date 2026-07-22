<?php

declare(strict_types=1);

namespace App\Evidence\DTO;

use App\Enums\DocumentType;

/**
 * EvidenceData — DTO standar hasil ekstraksi parser evidence.
 *
 * DTO ini menjadi format output untuk seluruh parser
 * (TransferReceipt, ShoppingReceipt, QRIS, dll).
 *
 * Semua field nullable kecuali document_type dan raw_text.
 */
readonly class EvidenceData
{
    public function __construct(
        public DocumentType $documentType,
        public string $rawText,
        public ?string $walletName = null,
        public ?string $bankName = null,
        public ?string $merchantName = null,
        public ?string $merchantCity = null,
        public ?string $destinationName = null,
        public ?string $destinationAccount = null,
        public ?string $referenceNumber = null,
        public ?string $transactionType = null,
        public ?float $amount = null,
        public ?string $currency = null,
        public ?string $transactionTime = null,
        public ?string $description = null,
        public float $confidence = 0.0,
        public array $metadata = [],
        // Shopping receipt fields
        public ?float $subtotal = null,
        public ?float $tax = null,
        public ?float $discount = null,
        public ?float $serviceCharge = null,
        public ?string $paymentMethod = null,
        public ?string $receiptNumber = null,
        public ?string $cashier = null,
        /** @var ReceiptItem[] */
        public array $items = [],
        // QRIS receipt fields
        public ?string $terminalId = null,
        public ?string $issuer = null,
        public ?string $acquirer = null,
        public ?string $transactionStatus = null,
        public ?string $date = null,
        public ?string $time = null,
    ) {}

    /**
     * Konversi ke array untuk disimpan sebagai JSON di database.
     */
    public function toArray(): array
    {
        return [
            'document_type' => $this->documentType->value,
            'wallet_name' => $this->walletName,
            'bank_name' => $this->bankName,
            'merchant_name' => $this->merchantName,
            'merchant_city' => $this->merchantCity,
            'destination_name' => $this->destinationName,
            'destination_account' => $this->destinationAccount,
            'reference_number' => $this->referenceNumber,
            'transaction_type' => $this->transactionType,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'transaction_time' => $this->transactionTime,
            'description' => $this->description,
            'raw_text' => $this->rawText,
            'confidence' => $this->confidence,
            'metadata' => $this->metadata,
            'subtotal' => $this->subtotal,
            'tax' => $this->tax,
            'discount' => $this->discount,
            'service_charge' => $this->serviceCharge,
            'payment_method' => $this->paymentMethod,
            'receipt_number' => $this->receiptNumber,
            'cashier' => $this->cashier,
            'items' => array_map(fn (ReceiptItem $item) => $item->toArray(), $this->items),
            'terminal_id' => $this->terminalId,
            'issuer' => $this->issuer,
            'acquirer' => $this->acquirer,
            'transaction_status' => $this->transactionStatus,
            'date' => $this->date,
            'time' => $this->time,
        ];
    }

    /**
     * Buat instance dari array (untuk decoding dari database).
     */
    public static function fromArray(array $data): self
    {
        $items = [];
        if (isset($data['items']) && is_array($data['items'])) {
            $items = array_map(fn (array $item) => ReceiptItem::fromArray($item), $data['items']);
        }

        return new self(
            documentType: DocumentType::from($data['document_type'] ?? 'UNKNOWN'),
            rawText: $data['raw_text'] ?? '',
            walletName: $data['wallet_name'] ?? null,
            bankName: $data['bank_name'] ?? null,
            merchantName: $data['merchant_name'] ?? null,
            merchantCity: $data['merchant_city'] ?? null,
            destinationName: $data['destination_name'] ?? null,
            destinationAccount: $data['destination_account'] ?? null,
            referenceNumber: $data['reference_number'] ?? null,
            transactionType: $data['transaction_type'] ?? null,
            amount: $data['amount'] ?? null,
            currency: $data['currency'] ?? null,
            transactionTime: $data['transaction_time'] ?? null,
            description: $data['description'] ?? null,
            confidence: $data['confidence'] ?? 0.0,
            metadata: $data['metadata'] ?? [],
            subtotal: $data['subtotal'] ?? null,
            tax: $data['tax'] ?? null,
            discount: $data['discount'] ?? null,
            serviceCharge: $data['service_charge'] ?? null,
            paymentMethod: $data['payment_method'] ?? null,
            receiptNumber: $data['receipt_number'] ?? null,
            cashier: $data['cashier'] ?? null,
            items: $items,
            terminalId: $data['terminal_id'] ?? null,
            issuer: $data['issuer'] ?? null,
            acquirer: $data['acquirer'] ?? null,
            transactionStatus: $data['transaction_status'] ?? null,
            date: $data['date'] ?? null,
            time: $data['time'] ?? null,
        );
    }
}
