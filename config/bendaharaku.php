<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | System Wallets Configuration
    |--------------------------------------------------------------------------
    */
    'system_wallets' => [
        'merchant' => env('SYSTEM_WALLET_MERCHANT', 'Merchant System'),
        'external' => env('SYSTEM_WALLET_EXTERNAL', 'External System'),
        'debt' => env('SYSTEM_WALLET_DEBT', 'System Hutang'),
        'receivable' => env('SYSTEM_WALLET_RECEIVABLE', 'System Piutang'),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Engine Configuration
    |--------------------------------------------------------------------------
    */
    'ai' => [
        'feedback_weights' => [
            'transaction_type' => 0.40,
            'category_id' => 0.25,
            'wallets' => 0.20,
            'amount' => 0.10,
            'subject' => 0.05,
        ],
        'confidence' => [
            'threshold_auto_clear' => 0.85,
            'weights' => [
                // Rebalanced: ai_base + category + wallet sudah cukup untuk auto-clear
                // tanpa membutuhkan memori historis (penting untuk user baru / transaksi pertama).
                // Simulasi kasus ideal (ai=0.85, category=1, wallet=1):
                //   (0.85×0.50) + (0×0.05) + (1×0.20) + (1×0.25) = 0.425+0+0.20+0.25 = 0.875 ✓
                // Simulasi kasus Python NLP rendah (ai=0.71, category=1, wallet=1):
                //   (0.71×0.50) + (0×0.05) + (1×0.20) + (1×0.25) = 0.355+0+0.20+0.25 = 0.805 → Draft
                //   (masih draft kalau AI-nya kurang yakin — ini memang yang diinginkan)
                'ai_base' => 0.50,
                'memory_match' => 0.05, // Bonus kecil, bukan penentu utama
                'category_match' => 0.20,
                'wallet_match' => 0.25,
            ],
        ],
        'memory' => [
            'decay_rate' => 0.05,
            'prune_threshold' => 0.20,
            'max_memories' => 15,
            'max_effective_weight' => 10.0,
        ],
    ],
];
