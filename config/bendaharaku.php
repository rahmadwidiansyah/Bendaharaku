<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | System Wallets Configuration
    |--------------------------------------------------------------------------
    |
    | Pemetaan nama dompet sistem otomatis untuk menyeimbangkan arus kas
    | berdasarkan aturan Double-Entry Bookkeeping Bendaharaku.
    |
    */
    'system_wallets' => [
        'merchant'   => env('SYSTEM_WALLET_MERCHANT', 'Merchant System'),
        'external'   => env('SYSTEM_WALLET_EXTERNAL', 'External System'),
        'debt'       => env('SYSTEM_WALLET_DEBT', 'System Hutang'),
        'receivable' => env('SYSTEM_WALLET_RECEIVABLE', 'System Piutang'),
    ],
];