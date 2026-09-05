<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OCR Service Configuration — Tesseract primary, RapidOCR fallback
    |--------------------------------------------------------------------------
    |
    | Engine auto: coba Tesseract dulu (ringan, <100MB), jika confidence <0.6
    | atau text <20 char atau tanpa digit → fallback ke RapidOCR (ONNX, 512MB).
    | PaddleOCR tidak dipakai lagi untuk server 2GB.
    |
    */

    // URL service OCR (RapidOCR)
    'url' => env('OCR_URL', 'http://ocr-service:8000'),

    // Engine: auto|tesseract|rapid
    'engine' => env('OCR_ENGINE', 'auto'),

    // Fallback engine ketika auto gagal
    'fallback' => env('OCR_FALLBACK', 'rapid'),

    // Endpoint untuk extract text (RapidOCR)
    'extract_endpoint' => env('OCR_EXTRACT_ENDPOINT', '/ocr/extract'),

    // Timeout request ke RapidOCR service (detik)
    'timeout' => (int) env('OCR_TIMEOUT', 30),

    // Connection timeout (detik)
    'connect_timeout' => (int) env('OCR_CONNECT_TIMEOUT', 5),

    // Jumlah retry jika request gagal
    'retry' => (int) env('OCR_RETRY', 3),

    // Delay antar retry (ms)
    'retry_delay' => (int) env('OCR_RETRY_DELAY', 1000),

    // Port OCR service
    'port' => (int) env('OCR_PORT', 0),

    // Confidence threshold Tesseract untuk fallback ke Rapid (0.0 - 1.0)
    'confidence_threshold' => (float) env('OCR_CONFIDENCE_THRESHOLD', 0.6),

    // Tesseract specific
    'tesseract' => [
        'psm' => (int) env('TESSERACT_PSM', 6),
        'oem' => (int) env('TESSERACT_OEM', 1),
        'lang' => env('TESSERACT_LANG', 'ind+eng'),
        'timeout' => (int) env('TESSERACT_TIMEOUT', 15),
        'min_text_len' => (int) env('OCR_MIN_TEXT_LEN', 20),
    ],

    // RapidOCR specific
    'rapid' => [
        'url' => env('OCR_RAPID_URL', env('OCR_URL', 'http://ocr-service:8000')),
        'timeout' => (int) env('OCR_RAPID_TIMEOUT', 30),
    ],

    // Maksimum ukuran gambar (MB)
    'max_image_size_mb' => (float) env('OCR_MAX_IMAGE_SIZE_MB', 10.0),

    // Debug mode
    'debug' => (bool) env('OCR_DEBUG', false),
];
