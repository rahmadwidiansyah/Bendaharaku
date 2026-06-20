<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | System Wallets Configuration
    |--------------------------------------------------------------------------
    */
    'system_wallets' => [
        'merchant'   => env('SYSTEM_WALLET_MERCHANT', 'Merchant System'),
        'external'   => env('SYSTEM_WALLET_EXTERNAL', 'External System'),
        'debt'       => env('SYSTEM_WALLET_DEBT', 'System Hutang'),
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
            'category_id'      => 0.25,
            'wallets'          => 0.20,
            'amount'           => 0.10,
            'subject'          => 0.05,
        ],
        'confidence' => [
            'threshold_auto_clear' => 0.85,
            'weights' => [
                'ai_base'        => 0.40,
                'memory_match'   => 0.25,
                'category_match' => 0.15,
                'wallet_match'   => 0.20,
            ]
        ],
        'memory' => [
            'decay_rate'           => 0.05, 
            'prune_threshold'      => 0.20, 
            'max_memories'         => 15,   
            'max_effective_weight' => 10.0, 
        ]
    ],
];