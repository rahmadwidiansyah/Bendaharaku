<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OCR Service Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk OCR Microservice (PaddleOCR via FastAPI).
    | Service ini berjalan terpisah dari Laravel app.
    |
    | Semua nilai numerik wajib di-cast ke tipe yang benar di sini
    | agar tidak menyebabkan TypeError pada typed property.
    |
    */

    // URL service OCR
    'url' => env('OCR_URL', 'http://ocr-service:8000'),

    // Nama engine yang digunakan (metadata, bukan logic)
    'engine' => env('OCR_ENGINE', 'PaddleOCR'),

    // Endpoint untuk extract text
    'extract_endpoint' => env('OCR_EXTRACT_ENDPOINT', '/ocr/extract'),

    // Timeout request ke OCR service (dalam detik)
    'timeout' => (int) env('OCR_TIMEOUT', 30),

    // Connection timeout (dalam detik)
    'connect_timeout' => (int) env('OCR_CONNECT_TIMEOUT', 5),

    // Jumlah retry jika request gagal
    'retry' => (int) env('OCR_RETRY', 3),

    // Delay antar retry (dalam milidetik)
    'retry_delay' => (int) env('OCR_RETRY_DELAY', 1000),

    // Port OCR service (0 = gunakan default dari URL)
    'port' => (int) env('OCR_PORT', 0),

    // Confidence threshold untuk validasi hasil OCR (0.0 - 1.0)
    'confidence_threshold' => (float) env('OCR_CONFIDENCE_THRESHOLD', 0.8),

    // Maksimum ukuran gambar yang dikirim (dalam MB)
    'max_image_size_mb' => (float) env('OCR_MAX_IMAGE_SIZE_MB', 10.0),

    // Debug mode
    'debug' => (bool) env('OCR_DEBUG', false),
];
