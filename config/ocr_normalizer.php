<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Currency Aliases
    |--------------------------------------------------------------------------
    |
    | Mapping variasi OCR currency ke format normal.
    | Key = format normal, Value = array variasi yang mungkin muncul dari OCR.
    |
    */
    'currency_aliases' => [
        'Rp' => ['Rpl', 'Rp.', 'RP', 'IDR', 'Rp1', 'rp', 'rp.'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Wallet Aliases
    |--------------------------------------------------------------------------
    |
    | Mapping nama wallet dari variasi OCR ke format normal.
    | Key = format normal, Value = array variasi.
    |
    */
    'wallet_aliases' => [
        'SeaBank' => ['Sea BanK', 'Seabank', 'SEA BANK', 'sea bank'],
        'ShopeePay' => ['Shopee Pay', 'SHOPEE PAY', 'shopee pay', 'Shopeepay'],
        'GoPay' => ['Go Pay', 'GO PAY', 'go pay', 'Gopay'],
        'DANA' => ['Dana', 'dana', 'Dana Wallet'],
        'OVO' => ['OV0', 'Ovo', 'ovo', '0VO'],
        'LinkAja' => ['Link Aja', 'LINK AJA', 'link aja', 'Linkaja'],
        'BCA' => ['Bank BCA', 'bank bca', 'BCA'],
        'Mandiri' => ['Bank Mandiri', 'bank mandiri', 'MANDIRI'],
        'BRI' => ['Bank BRI', 'bank bri', 'BRI'],
        'BNI' => ['Bank BNI', 'bank bni', 'BNI'],
        'QRIS' => ['Q R I S', 'Q.R.I.S', 'Q-R-I-S', 'Q R IS', 'Q R1S'],
        'Permata' => ['Bank Permata', 'permata', 'PERMATA'],
        'Danamon' => ['Bank Danamon', 'danamon', 'DANAMON'],
        'CIMB' => ['CIMB Niaga', 'cimb', 'CIMB'],
        'BSI' => ['Bank Syariah Indonesia', 'bsi', 'BSI'],
        'Jago' => ['Bank Jago', 'jago', 'JAGO'],
        'Blu' => ['Bank Blu', 'blu', 'BLU'],
        'Neobank' => ['Neo Bank', 'neobank', 'NEOBANK'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Number Context Rules
    |--------------------------------------------------------------------------
    |
    | Aturan konversi karakter OCR yang salah dalam konteks angka.
    | Hanya diterapkan di sekitar digit (bukan di tengah kata).
    |
    */
    'number_char_map' => [
        'O' => '0', // Huruf O → angka 0
        'o' => '0',
        'l' => '1', // Huruf l → angka 1
        'I' => '1', // Huruf I → angka 1
        'S' => '5', // Huruf S → angka 5 (hanya dalam konteks angka)
        'B' => '8', // Huruf B → angka 8 (hanya dalam konteks angka)
        'Z' => '2', // Huruf Z → angka 2
        'G' => '6', // Huruf G → angka 6
    ],

    /*
    |--------------------------------------------------------------------------
    | Noise Patterns
    |--------------------------------------------------------------------------
    |
    | Regex patterns untuk karakter noise OCR yang harus dihapus.
    |
    */
    'noise_patterns' => [
        '/^-{3,}$/m',           // --- (garis strip berulang)
        '/^={3,}$/m',           // === (garis equals berulang)
        '/^\*{3,}$/m',          // *** (garis bintang berulang)
        '/^_{3,}$/m',           // ___ (garis bawah berulang)
        '/^~{3,}$/m',           // ~~~ (garis tilde berulang)
        '/^\.{3,}$/m',          // ... (titik berulang)
        '/^#{3,}$/m',           // ### (hash berulang)
    ],

    /*
    |--------------------------------------------------------------------------
    | Whitespace Cleanup
    |--------------------------------------------------------------------------
    |
    | Regex patterns untuk normalisasi whitespace.
    |
    */
    'whitespace' => [
        // Double/triple+ space → single space
        '/ {2,}/' => ' ',
        // Tab → single space
        '/\t/' => ' ',
        // Multiple newlines → single newline
        '/\n{3,}/' => "\n\n",
        // Space before period
        '/ \./' => '.',
        // Space before comma
        '/ ,/' => ',',
    ],

    /*
    |--------------------------------------------------------------------------
    | Reference Number Cleanup
    |--------------------------------------------------------------------------
    |
    | Pattern untuk membersihkan reference number dari spasi/tanda baca.
    |
    */
    'reference_cleanup' => [
        '/\s+/',                // Semua whitespace
        '/(?<=\w)-(?=\w)/',    // Dash di antara alphanumeric (bukan sebagai negatif)
    ],

    /*
    |--------------------------------------------------------------------------
    | Unicode Normalization
    |--------------------------------------------------------------------------
    |
    | Normalisasi Unicode yang sering muncul dari OCR.
    |
    */
    'unicode_map' => [
        "\xC2\xA0" => ' ',     // Non-breaking space → regular space
        "\xE2\x80\x93" => '-', // En dash → hyphen
        "\xE2\x80\x94" => '-', // Em dash → hyphen
        "\xE2\x80\x98" => "'", // Left single quote → apostrophe
        "\xE2\x80\x99" => "'", // Right single quote → apostrophe
        "\xE2\x80\x9C" => '"', // Left double quote
        "\xE2\x80\x9D" => '"', // Right double quote
        "\xE2\x80\xA6" => '...', // Ellipsis character
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Aktifkan logging detail untuk normalizer.
    |
    */
    'debug' => env('EVIDENCE_NORMALIZER_DEBUG', false),

];
