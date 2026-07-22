<?php

declare(strict_types=1);

return [
    'engine' => env('QRIS_PARSER_ENGINE', 'QrisReceiptParser'),
    'version' => env('QRIS_PARSER_VERSION', '1.0'),

    'merchant_aliases' => [
        'Mixue' => ['MIXUE', 'Mixue', 'mixue', 'Mi Xue', 'MI XUE'],
        'Indomaret' => ['INDOMARET', 'Indomaret', 'indomaret'],
        'Alfamart' => ['ALFAMART', 'Alfamart', 'alfamart'],
        'Alfamidi' => ['ALFAMIDI', 'Alfamidi', 'alfamidi'],
        'Superindo' => ['SUPERINDO', 'Super Indo', 'Superindo', 'superindo'],
        'McDonalds' => ['MCDONALDS', "McDonald's", 'Mc Donalds', 'mcdonalds'],
        'KFC' => ['KFC', 'Kentucky', 'kentucky'],
        'Starbucks' => ['STARBUCKS', 'Starbucks', 'starbucks'],
        'Shell' => ['SHELL', 'Shell', 'shell'],
        'BP-AKR' => ['BP-AKR', 'BP AKR', 'bp-akr'],
        'Pertamina' => ['PERTAMINA', 'Pertamina', 'pertamina'],
    ],

    'merchant_categories' => [
        'Makan & Minum' => [
            'mixue', 'mcdonald', 'kfc', 'starbucks', 'hoka bento',
            'kober', 'seblak', 'bakso', 'soto', 'mie', 'nasi',
            'kopi', 'coffee', 'resto', 'restoran', 'warung',
            'ayam', 'bebek', 'ikan', 'sate', 'gorengan',
        ],
        'Belanja' => [
            'indomaret', 'alfamart', 'alfamidi', 'superindo',
            'hypermart', 'transmart', 'sentral', 'toko',
            'minimarket', 'supermarket', 'grosir',
        ],
        'Transportasi' => [
            'shell', 'pertamina', 'bp-akr', 'bensin', 'bbm',
            'parkir', 'tol', 'toll', 'grab', 'gojek',
            'transport', 'bahan bakar',
        ],
        'Kesehatan' => [
            'apotek', 'pharmacy', 'kimia farma', 'k24', 'rs',
            'rumah sakit', 'klinik', 'dokter', 'obat',
        ],
        'Hiburan' => [
            'cinema', 'xxi', 'cgv', 'bioskop', 'game',
            'playstation', 'timezone', 'fun world',
        ],
        'Pendidikan' => [
            'kursus', 'les', 'bimbel', 'ruangguru', 'zenius',
            'sekolah', 'universitas', 'buku',
        ],
    ],

    'wallet_aliases' => [
        'GoPay' => ['Go Pay', 'Gopay', 'GO PAY', 'go pay'],
        'OVO' => ['OVO', 'Ovo', 'ovo'],
        'DANA' => ['DANA', 'Dana', 'dana'],
        'ShopeePay' => ['Shopee Pay', 'Shopeepay', 'SHOPEE PAY', 'shopee pay'],
        'LinkAja' => ['Link Aja', 'Linkaja', 'LINK AJA', 'link aja'],
        'SeaBank' => ['SeaBank', 'Seabank', 'SEA BANK', 'sea bank'],
        'BCA' => ['BCA', 'Bank BCA', 'bca'],
        'Mandiri' => ['Mandiri', 'MANDIRI', 'Bank Mandiri'],
        'BRI' => ['BRI', 'Bank BRI', 'bri'],
        'BNI' => ['BNI', 'Bank BNI', 'bni'],
        'Permata' => ['Permata', 'Bank Permata', 'permata'],
        'Danamon' => ['Danamon', 'Bank Danamon', 'danamon'],
        'CIMB' => ['CIMB', 'CIMB Niaga', 'cimb'],
        'BSI' => ['BSI', 'Bank Syariah Indonesia', 'bsi'],
        'MayBank' => ['MayBank', 'Maybank', 'maybank'],
        'Jago' => ['Jago', 'Bank Jago', 'jago'],
        'Blu' => ['Blu', 'BLU', 'Bank Blu', 'blu'],
        'Neobank' => ['Neobank', 'Neo Bank', 'Neobank'],
    ],

    'issuer_aliases' => [
        'BCA' => ['BCA', 'Bank BCA', 'PT Bank Central Asia'],
        'Mandiri' => ['Mandiri', 'Bank Mandiri', 'PT Bank Mandiri'],
        'BRI' => ['BRI', 'Bank BRI', 'PT Bank Rakyat Indonesia'],
        'BNI' => ['BNI', 'Bank BNI', 'PT Bank Negara Indonesia'],
        'SeaBank' => ['SeaBank', 'PT Bank Seabank Indonesia'],
        'CIMB' => ['CIMB', 'CIMB Niaga', 'PT Bank CIMB Niaga'],
        'Permata' => ['Permata', 'Bank Permata', 'PT Bank Permata'],
        'Danamon' => ['Danamon', 'Bank Danamon', 'PT Bank Danamon'],
        'BSI' => ['BSI', 'BSI', 'PT Bank Syariah Indonesia'],
        'MayBank' => ['MayBank', 'Maybank', 'PT Bank Maybank Indonesia'],
    ],

    'acquirer_aliases' => [
        'GPN' => ['GPN', 'Gerbang Pembayaran Nasional'],
        'Rintis' => ['Rintis', 'PT Rintis Sejahtera'],
        'Artajasa' => ['Artajasa', 'PT Artajasa Pembayaran Elektronis'],
        'Finset' => ['Finset', 'PT Finset Nusantara'],
        'Nexus' => ['Nexus', 'PT Nexus Indah'],
        'Alto' => ['Alto', 'PT Alto Network'],
        'Prima' => ['Prima', 'PT Prima Guna'],
    ],

    'qris_keywords' => [
        'qris', 'qris payment', 'pembayaran qris',
        'qr code', 'quick response',
    ],

    'status_keywords' => [
        'BERHASIL' => ['BERHASIL', 'SUCCESS', 'Sukses', 'sukses', 'LUNAS', 'Paid', 'PAID'],
        'GAGAL' => ['GAGAL', 'FAILED', 'Ditolak', 'ditolak', 'REJECTED'],
        'PENDING' => ['PENDING', 'Menunggu', 'menunggu', 'Process', 'PROCESS'],
    ],

    'reference_patterns' => [
        '/ref[:\s]*(\d{12,20})/i',
        '/referensi[:\s]*(\d{12,20})/i',
        '/rrn[:\s]*(\d{12,16})/i',
        '/no\.?\s*ref[:\s]*(\d{12,20})/i',
        '/nomor\s*referensi[:\s]*(\d{12,20})/i',
        '/transaction[:\s]*(\d{12,20})/i',
    ],

    'date_patterns' => [
        '/(\d{1,2})\/(\d{1,2})\/(\d{4})/',
        '/(\d{4})-(\d{2})-(\d{2})/',
    ],

    'time_patterns' => [
        '/(\d{1,2})[.:](\d{2})(?:\s*(?:WIB|WITA|WIT))?/',
    ],

    'merchant_label_patterns' => [
        '/merchant[:\s]*\n*([A-Za-z0-9\s&.,\-]+)/i',
        '/pedagang[:\s]*\n*([A-Za-z0-9\s&.,\-]+)/i',
        '/merchant\s+name[:\s]*\n*([A-Za-z0-9\s&.,\-]+)/i',
    ],

    'amount_label_patterns' => [
        '/nominal[:\s]*rp\.?\s*([\d.,]+)/i',
        '/jumlah[:\s]*rp\.?\s*([\d.,]+)/i',
        '/total[:\s]*rp\.?\s*([\d.,]+)/i',
        '/amount[:\s]*rp\.?\s*([\d.,]+)/i',
    ],

    'wallet_label_patterns' => [
        '/metode[:\s]*([A-Za-z0-9\s]+)/i',
        '/method[:\s]*([A-Za-z0-9\s]+)/i',
        '/payment[:\s]*([A-Za-z0-9\s]+)/i',
        '/melalui[:\s]*([A-Za-z0-9\s]+)/i',
    ],

    'terminal_label_patterns' => [
        '/nmid[:\s]*([A-Za-z0-9]+)/i',
        '/terminal[:\s]*(?:id|no)?[:\s]*([A-Za-z0-9]+)/i',
        '/mid[:\s]*([A-Za-z0-9]+)/i',
    ],

    'label_mappings' => [
        'Merchant' => 'merchant',
        'Pedagang' => 'merchant',
        'Nominal' => 'amount',
        'Jumlah' => 'amount',
        'Total' => 'amount',
        'Metode' => 'wallet',
        'Pembayaran' => 'wallet',
        'Ref' => 'reference',
        'Referensi' => 'reference',
        'Tanggal' => 'date',
        'Tgl' => 'date',
        'Jam' => 'time',
        'Waktu' => 'time',
        'Status' => 'status',
        'Issuer' => 'issuer',
        'Acquirer' => 'acquirer',
        'NMID' => 'terminal',
        'Terminal' => 'terminal',
        'RRN' => 'reference',
    ],
];
