<?php

declare(strict_types=1);

namespace App\Evidence\DTO;

/**
 * ReceiptItem — DTO untuk item/baris pada struk belanja.
 *
 * Setiap item merepresentasikan satu baris produk pada receipt:
 * - name: nama produk
 * - qty: jumlah
 * - unit_price: harga satuan
 * - discount: diskon per item (nullable)
 * - total: total harga item (qty * unit_price - discount)
 * - confidence: confidence level ekstraksi item ini
 */
readonly class ReceiptItem
{
    public function __construct(
        public string $name,
        public int $qty = 1,
        public float $unitPrice = 0.0,
        public ?float $discount = null,
        public float $total = 0.0,
        public float $confidence = 0.0,
    ) {}

    /**
     * Konversi ke array untuk disimpan sebagai JSON.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'qty' => $this->qty,
            'unit_price' => $this->unitPrice,
            'discount' => $this->discount,
            'total' => $this->total,
            'confidence' => $this->confidence,
        ];
    }

    /**
     * Buat instance dari array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? '',
            qty: $data['qty'] ?? 1,
            unitPrice: $data['unit_price'] ?? 0.0,
            discount: $data['discount'] ?? null,
            total: $data['total'] ?? 0.0,
            confidence: $data['confidence'] ?? 0.0,
        );
    }
}
