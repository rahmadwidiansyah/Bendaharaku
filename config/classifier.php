<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Document Classifier Configuration
    |--------------------------------------------------------------------------
    |
    | Rule-based classifier untuk menentukan jenis dokumen.
    | Keyword lists dikonfigurasi di sini agar mudah diubah tanpa ubah code.
    |
    */

    // Engine identifier
    'engine' => env('CLASSIFIER_ENGINE', 'RuleBased'),

    // Version
    'version' => env('CLASSIFIER_VERSION', '1.0'),

    // Minimum confidence threshold untuk classification
    // Jika di bawah threshold → UNKNOWN
    // 0.15 lebih toleran untuk struk kertas pendek (244 char OCR Tesseract)
    'confidence_threshold' => env('CLASSIFIER_CONFIDENCE_THRESHOLD', 0.15),

    // Keyword lists per document type
    // Setiap type punya array keywords (case-insensitive matching)
    'keywords' => [

        'TRANSFER_RECEIPT' => [
            'transfer berhasil',
            'transfer success',
            'nomor referensi',
            'reference number',
            'transfer ke',
            'transfer dari',
            'transfer to',
            'transfer from',
            'berhasil ditransfer',
            'rekening tujuan',
            'rekening sumber',
            'destination account',
            'source account',
        ],

        'SHOPPING_RECEIPT' => [
            'total',
            'subtotal',
            'grand total',
            'ppn',
            'kasir',
            'qty',
            'item',
            'diskon',
            'tunai',
            'kembalian',
            'change',
            'cash',
            'discount',
            'belanja',
            'harga',
            'qty',
            'struk',
            // fallback keywords untuk struk kertas pendek / OCR Tesseract
            'rp',
            'idr',
            'jumlah',
            'bayar',
            'terima kasih',
            'thank you',
            'grandtotal',
            'total rp',
            'harga rp',
        ],

        'QRIS_RECEIPT' => [
            'qris',
            'nmid',
            'rrn',
            'issuer',
            'acquirer',
            'merchant pan',
            'payment gateway',
            'tagihan',
            'nominal',
            'pembayaran berhasil',
            'pembayaran qris',
            'qr code',
            'quick response',
            'merchant',
            'terminal',
            'mid',
            'berhasil',
            'sukses',
        ],

        'BANK_STATEMENT' => [
            'saldo awal',
            'saldo akhir',
            'mutasi',
            'debit',
            'kredit',
            'periode',
            'opening balance',
            'closing balance',
            'statement',
            'rekening koran',
        ],

        'PAYMENT_RECEIPT' => [
            'pembayaran berhasil',
            'payment success',
            'paid',
            'lunas',
            'terbayar',
            'payment completed',
            ' pembayaran selesai',
        ],

        'TOPUP_RECEIPT' => [
            'top up',
            'topup',
            'isi saldo',
            'saldo berhasil',
            'top up berhasil',
            'topup berhasil',
            'add balance',
        ],

        'WITHDRAW_RECEIPT' => [
            'withdraw',
            'penarikan',
            'tarik tunai',
            'withdrawal',
            'cash withdrawal',
            'penarikan dana',
        ],

        'DEPOSIT_RECEIPT' => [
            'deposit',
            'setor',
            'setoran',
            'deposit berhasil',
            'cash deposit',
            'setor tunai',
        ],

        'INVOICE' => [
            'invoice',
            'faktur',
            'tagihan',
            'bill',
            'due date',
            'jatuh tempo',
            'purchase order',
        ],

    ],

];
