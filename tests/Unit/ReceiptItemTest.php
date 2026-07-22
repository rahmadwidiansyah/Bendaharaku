<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Evidence\DTO\ReceiptItem;
use Tests\TestCase;

class ReceiptItemTest extends TestCase
{
    public function test_to_array(): void
    {
        $item = new ReceiptItem(
            name: 'AQUA 600ML',
            qty: 2,
            unitPrice: 4000.0,
            total: 8000.0,
            confidence: 0.9,
        );

        $array = $item->toArray();

        $this->assertEquals('AQUA 600ML', $array['name']);
        $this->assertEquals(2, $array['qty']);
        $this->assertEquals(4000.0, $array['unit_price']);
        $this->assertNull($array['discount']);
        $this->assertEquals(8000.0, $array['total']);
        $this->assertEquals(0.9, $array['confidence']);
    }

    public function test_from_array(): void
    {
        $data = [
            'name' => 'ROTI',
            'qty' => 1,
            'unit_price' => 15000.0,
            'discount' => 1000.0,
            'total' => 14000.0,
            'confidence' => 0.85,
        ];

        $item = ReceiptItem::fromArray($data);

        $this->assertEquals('ROTI', $item->name);
        $this->assertEquals(1, $item->qty);
        $this->assertEquals(15000.0, $item->unitPrice);
        $this->assertEquals(1000.0, $item->discount);
        $this->assertEquals(14000.0, $item->total);
        $this->assertEquals(0.85, $item->confidence);
    }

    public function test_from_array_with_defaults(): void
    {
        $item = ReceiptItem::fromArray(['name' => 'Test']);

        $this->assertEquals('Test', $item->name);
        $this->assertEquals(1, $item->qty);
        $this->assertEquals(0.0, $item->unitPrice);
        $this->assertNull($item->discount);
        $this->assertEquals(0.0, $item->total);
        $this->assertEquals(0.0, $item->confidence);
    }

    public function test_roundtrip(): void
    {
        $original = new ReceiptItem(
            name: 'Test Item',
            qty: 3,
            unitPrice: 5000.0,
            discount: 500.0,
            total: 14500.0,
            confidence: 0.8,
        );

        $restored = ReceiptItem::fromArray($original->toArray());

        $this->assertEquals($original->name, $restored->name);
        $this->assertEquals($original->qty, $restored->qty);
        $this->assertEquals($original->unitPrice, $restored->unitPrice);
        $this->assertEquals($original->discount, $restored->discount);
        $this->assertEquals($original->total, $restored->total);
        $this->assertEquals($original->confidence, $restored->confidence);
    }
}
