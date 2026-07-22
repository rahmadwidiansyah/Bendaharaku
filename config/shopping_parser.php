<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Shopping Receipt Parser Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk ShoppingReceiptParser.
    | Semua values dapat diubah tanpa ubah code.
    |
    */

    'engine' => env('SHOPPING_PARSER_ENGINE', 'RuleBased'),
    'version' => env('SHOPPING_PARSER_VERSION', '1.0'),

    /*
    |--------------------------------------------------------------------------
    | Merchant Aliases
    |--------------------------------------------------------------------------
    |
    | Mapping variasi nama merchant ke nama canonical.
    | Digunakan oleh MerchantExtractor untuk menormalkan nama toko.
    |
    */
    'merchant_aliases' => [
        'Indomaret' => ['indomaret', 'INDOMARET', 'Indomaret '],
        'Alfamart' => ['alfamart', 'ALFAMART', 'Alfamart '],
        'Alfamidi' => ['alfamidi', 'ALFAMIDI', 'Alfamidi '],
        'Super Indo' => ['super indo', 'SUPER INDO', 'Superindo', 'superindo'],
        'Hypermart' => ['hypermart', 'HYPERMART', 'Hypermart '],
        'Transmart' => ['transmart', 'TRANSMART', 'Transmart ', 'Carrefour', 'carrefour'],
        'Lawson' => ['lawson', 'LAWSON', 'Lawson '],
        'FamilyMart' => ['familymart', 'FAMILYMART', 'Family Mart', 'family mart'],
        'Mixue' => ['mixue', 'MIXUE', 'Mixue ', 'Mixue Ice Cream & Tea'],
        "McDonald's" => ["mcdonald's", "MCDONALD'S", 'McDonalds', 'mcdonalds', "McDonald's "],
        'KFC' => ['kfc', 'KFC', 'Kentucky Fried Chicken'],
        'Starbucks' => ['starbucks', 'STARBUCKS', 'Starbucks '],
        'Apotek K24' => ['apotek k24', 'APOTEK K24', 'K24', 'k24', 'Apotek K24 '],
        'Guardian' => ['guardian', 'GUARDIAN', 'Guardian '],
        'Watsons' => ['watsons', 'WATSONS', 'Watsons '],
    ],

    /*
    |--------------------------------------------------------------------------
    | Restaurant Merchants
    |--------------------------------------------------------------------------
    |
    | Daftar merchant yang merupakan restoran/cafe.
    | Jika merchant cocok, category default adalah "Makan & Minum".
    |
    */
    'restaurant_merchants' => [
        'McDonald\'s', 'KFC', 'Starbucks', 'Mixue',
        'Burger King', 'Pizza Hut', 'Subway',
        'JCo', 'Krispy Kreme', 'Baskin Robbins',
        'Solaria', 'Bakmi GM', 'Marugame Udon',
        'Chatime', 'Gong Cha', 'KOI Thé',
    ],

    /*
    |--------------------------------------------------------------------------
    | Retail Merchants
    |--------------------------------------------------------------------------
    |
    | Daftar merchant yang merupakan retail/minimarket/supermarket.
    | Jika merchant cocok, category default adalah "Belanja".
    |
    */
    'retail_merchants' => [
        'Indomaret', 'Alfamart', 'Alfamidi', 'Super Indo',
        'Hypermart', 'Transmart', 'Lawson', 'FamilyMart',
        'Circle K', 'Alfa Express', 'Dan Dan',
        'Yogya', 'Matahari', 'Ramayana',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pharmacy Merchants
    |--------------------------------------------------------------------------
    |
    | Daftar merchant yang merupakan apotek.
    | Jika merchant cocok, category default adalah "Kesehatan".
    |
    */
    'pharmacy_merchants' => [
        'Apotek K24', 'Guardian', 'Watsons',
        'Century', 'K24', 'Apotek Kimia Farma',
    ],

    /*
    |--------------------------------------------------------------------------
    | Receipt Keywords
    |--------------------------------------------------------------------------
    |
    | Keywords untuk identifikasi bahwa teks adalah struk belanja.
    |
    */
    'receipt_keywords' => [
        'total', 'subtotal', 'ppn', 'kasir', 'qty', 'item',
        'diskon', 'tunai', 'kembalian', 'change', 'cash',
        'discount', 'belanja', 'harga', 'struk', 'bayar',
        'payment', 'change', 'receipt', 'thank you',
        'terima kasih', 'selamat belanja', 'harga jual',
        'jumlah', 'total belanja', 'grand total',
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Method Keywords
    |--------------------------------------------------------------------------
    |
    | Keywords untuk identifikasi metode pembayaran.
    |
    */
    'payment_methods' => [
        'Tunai' => ['tunai', 'cash', 'CASH', 'TUNAI'],
        'Debit' => ['debit', 'DEBIT', 'kartu debit'],
        'Kredit' => ['kredit', 'CREDIT', 'kartu kredit', 'credit card'],
        'QRIS' => ['qris', 'QRIS', 'QR Code'],
        'GoPay' => ['gopay', 'GOPAY', 'go pay'],
        'OVO' => ['ovo', 'OVO'],
        'DANA' => ['dana', 'DANA'],
        'ShopeePay' => ['shopeepay', 'SHOPEEPAY', 'shopee pay'],
        'LinkAja' => ['linkaja', 'LINKAJA', 'link aja'],
        'Transfer' => ['transfer', 'TRANSFER', 'tf bank'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Summary Keywords
    |--------------------------------------------------------------------------
    |
    | Keywords untuk mengidentifikasi baris summary (total, subtotal, dll).
    |
    */
    'summary_keywords' => [
        'total', 'grand total', 'total belanja', 'jumlah',
        'subtotal', 'sub total', 'sub total belanja',
        'ppn', 'pajak', 'tax', 'pph',
        'diskon', 'discount', 'potongan',
        'service charge', 'service', 'biaya layanan',
        'bayar', 'payment', 'tunai', 'cash',
        'kembalian', 'change', 'received',
    ],

    /*
    |--------------------------------------------------------------------------
    | Item Line Patterns
    |--------------------------------------------------------------------------
    |
    | Regex patterns untuk mengekstrak item dari baris struk.
    | Format: [pattern, name_group, qty_group, price_group, total_group]
    |
    */
    'item_patterns' => [
        // "2 x 4.000 8.000" atau "2 x Rp 4.000 Rp 8.000"
        '/^(.+?)\s+(\d+)\s*x\s*(?:rp\.?\s*)?([\d.,]+)\s+(?:rp\.?\s*)?([\d.,]+)$/i',
        // "AQUA 600ML 8.000" (nama + harga tanpa qty)
        '/^(.+?)\s+(?:rp\.?\s*)?([\d.,]+)$/i',
        // "2 x 4.000" (qty x harga, tanpa total)
        '/^(.+?)\s+(\d+)\s*x\s*(?:rp\.?\s*)?([\d.,]+)$/i',
    ],

    /*
    |--------------------------------------------------------------------------
    | Amount Patterns
    |--------------------------------------------------------------------------
    |
    | Regex patterns untuk mengekstrak amount/total dari baris tertentu.
    |
    */
    'amount_patterns' => [
        // "Grand Total: Rp 23.000"
        '/(?:grand\s*total|total\s*belanja|total|jumlah|sub\s*total|ppn|pajak|diskon|discount|service\s*charge|bayar|payment|tunai|cash|kembalian|change)[:\s]*(?:rp\.?\s*)?([\d.,]+)/i',
        // "Rp 23.000" standalone
        '/rp\.?\s*([\d.,]+)/i',
        // Large number standalone
        '/\b(\d{1,3}(?:[.,]\d{3})+(?:[.,]\d{2})?)\b/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Summary Line Patterns
    |--------------------------------------------------------------------------
    |
    | Pattern untuk mendeteksi baris summary/total.
    |
    */
    'summary_line_patterns' => [
        '/^(?:grand\s*)?total/i',
        '/^sub\s*total/i',
        '/^ppn/i',
        '/^pajak/i',
        '/^diskon/i',
        '/^discount/i',
        '/^service\s*charge/i',
        '/^bayar/i',
        '/^tunai/i',
        '/^cash/i',
        '/^kembalian/i',
        '/^change/i',
    ],

    /*
    |--------------------------------------------------------------------------
    | Date Patterns
    |--------------------------------------------------------------------------
    |
    | Regex patterns untuk mengekstrak tanggal dari struk belanja.
    |
    */
    'date_patterns' => [
        // "22/07/2026" atau "22-07-2026"
        '/(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4})/',
        // "22 Jul 2026" atau "22 Juli 2026"
        '/(\d{1,2}\s+(?:Jan|Feb|Mar|Apr|Mei|Jun|Jul|Agu|Sep|Okt|Nov|Des|Januari|Februari|Maret|April|Juni|Juli|Agustus|September|Oktober|November|Desember)\w*\s+\d{4})/i',
        // "2026-07-22"
        '/(\d{4}-\d{2}-\d{2})/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Time Patterns
    |--------------------------------------------------------------------------
    |
    | Regex patterns untuk mengekstrak waktu dari struk belanja.
    |
    */
    'time_patterns' => [
        // "09:30" atau "09:30:00"
        '/(\d{1,2}:\d{2}(?::\d{2})?)/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Receipt Number Patterns
    |--------------------------------------------------------------------------
    |
    | Regex patterns untuk mengekstrak nomor struk.
    |
    */
    'receipt_number_patterns' => [
        // "No: 001234" atau "No. 001234" atau "No 001234" — min 2 digit
        '/\b(?:no|nomor|no\.)\s*[:.]?\s*(\d{2,})/i',
        // "Struk: 001234"
        '/(?:struk|receipt)\s*[:.]?\s*(\w+)/i',
        // "#001234"
        '/#\s*(\d{2,})/i',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cashier Patterns
    |--------------------------------------------------------------------------
    |
    | Regex patterns untuk mengekstrak nama kasir.
    |
    */
    'cashier_patterns' => [
        // "Kasir: Budi" atau "Kasir: 001 Budi"
        '/(?:kasir|cashier)\s*[:.]?\s*(.+)/i',
        // "CR: Budi"
        '/(?:CR|Operator)\s*[:.]?\s*(.+)/i',
    ],

    /*
    |--------------------------------------------------------------------------
    | Skip Keywords for Items
    |--------------------------------------------------------------------------
    |
    | Keywords yang harus dilewati saat parsing item
    | (bukan item, tapi bagian header/footer/summary).
    |
    */
    'skip_keywords' => [
        'total', 'subtotal', 'sub total', 'grand total',
        'ppn', 'pajak', 'tax', 'pph',
        'diskon', 'discount', 'potongan',
        'service charge', 'service',
        'bayar', 'payment', 'tunai', 'cash',
        'kembalian', 'change', 'received',
        'terima kasih', 'thank you', 'selamat belanja',
        'struk ini', 'bukti', 'proof',
        'tanggal', 'date', ' waktu', 'time',
        'kasir', 'cashier', 'operator',
        'no', 'nomor', 'number',
    ],

];
