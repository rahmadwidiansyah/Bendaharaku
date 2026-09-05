<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pipeline Version
    |--------------------------------------------------------------------------
    |
    | Versi pipeline saat ini. Digunakan untuk tracking di evidence_processing_logs.
    |
    */
    'pipeline_version' => env('EVIDENCE_PIPELINE_VERSION', '1.0'),

    /*
    |--------------------------------------------------------------------------
    | Retry Policy
    |--------------------------------------------------------------------------
    |
    | Konfigurasi retry per stage. Retry akan berhenti setelah mencapai max_tries.
    |
    */
    'retry' => [
        'ocr' => [
            'max_tries' => env('EVIDENCE_OCR_MAX_RETRIES', 3),
            'backoff' => env('EVIDENCE_OCR_BACKOFF', 60), // seconds
        ],
        'classifier' => [
            'max_tries' => env('EVIDENCE_CLASSIFIER_MAX_RETRIES', 2),
            'backoff' => env('EVIDENCE_CLASSIFIER_BACKOFF', 30),
        ],
        'parser' => [
            'max_tries' => env('EVIDENCE_PARSER_MAX_RETRIES', 2),
            'backoff' => env('EVIDENCE_PARSER_BACKOFF', 30),
        ],
        'resolver' => [
            'max_tries' => env('EVIDENCE_RESOLVER_MAX_RETRIES', 2),
            'backoff' => env('EVIDENCE_RESOLVER_BACKOFF', 30),
        ],
        'commit' => [
            'max_tries' => env('EVIDENCE_COMMIT_MAX_RETRIES', 1),
            'backoff' => env('EVIDENCE_COMMIT_BACKOFF', 10),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | Timeout untuk setiap stage dalam detik.
    |
    */
    'timeout' => [
        'ocr' => env('EVIDENCE_OCR_TIMEOUT', 120),
        'pipeline' => env('EVIDENCE_PIPELINE_TIMEOUT', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Max File Size
    |--------------------------------------------------------------------------
    |
    | Ukuran maksimum file evidence dalam MB.
    |
    */
    'max_file_size_mb' => env('EVIDENCE_MAX_FILE_SIZE_MB', 10),

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | Aktifkan logging detail untuk debugging.
    |
    */
    'debug' => env('EVIDENCE_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Cleanup
    |--------------------------------------------------------------------------
    |
    | Konfigurasi cleanup otomatis untuk evidence yang tidak selesai.
    |
    */
    'cleanup' => [
        'enabled' => env('EVIDENCE_CLEANUP_ENABLED', true),
        'stale_hours' => env('EVIDENCE_STALE_HOURS', 24), // Hapus evidence yang gagal > 24 jam
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache TTL
    |--------------------------------------------------------------------------
    |
    | TTL untuk cache OCR version, classifier keywords, dll.
    |
    */
    'cache_ttl' => env('EVIDENCE_CACHE_TTL', 3600), // 1 hour

    /*
    |--------------------------------------------------------------------------
    | Log Channel
    |--------------------------------------------------------------------------
    |
    | Channel untuk evidence logs.
    |
    */
    'log_channel' => env('EVIDENCE_LOG_CHANNEL', 'evidence'),

    /*
    |--------------------------------------------------------------------------
    | LLM Fallback untuk OCR
    |--------------------------------------------------------------------------
    |
    | Jika true, hasil OCR text akan dikirim ke LLM API (via AIManager) untuk
    | pengelompokan transaksi otomatis. Regex parser tetap jadi fallback jika
    | LLM tidak dikonfigurasi atau gagal. Cocok untuk struk yang rumit.
    |
    */
    'llm' => [
        'enabled' => env('EVIDENCE_LLM_ENABLED', true),
        'fallback_threshold' => (float) env('EVIDENCE_LLM_FALLBACK_THRESHOLD', 0.6),
        'primary' => env('EVIDENCE_LLM_PRIMARY', false), // jika true, LLM jadi primary sebelum regex
    ],

];
