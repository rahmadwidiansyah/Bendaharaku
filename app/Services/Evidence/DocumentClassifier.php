<?php

declare(strict_types=1);

namespace App\Services\Evidence;

use App\Enums\DocumentType;
use App\Models\Evidence;

/**
 * DocumentClassifier — Rule-based classifier untuk menentukan jenis dokumen.
 *
 * Menerima OCR text dan mengembalikan DocumentType berdasarkan keyword matching.
 * Tidak menggunakan AI — hanya rule-based.
 *
 * Confidence dihitung: jumlah keyword cocok / jumlah keyword target.
 */
class DocumentClassifier
{
    private string $engine;

    private string $version;

    private float $confidenceThreshold;

    private array $keywords;

    public function __construct()
    {
        $this->engine = config('classifier.engine', 'RuleBased');
        $this->version = config('classifier.version', '1.0');
        $this->confidenceThreshold = config('classifier.confidence_threshold', 0.2);
        $this->keywords = config('classifier.keywords', []);
    }

    /**
     * Klasifikasi evidence berdasarkan OCR text.
     *
     * @return array{document_type: DocumentType, confidence: float, matched_keywords: string[], total_keywords: int}
     */
    public function classify(Evidence $evidence): array
    {
        $ocrText = strtolower($evidence->normalized_text ?? $evidence->ocr_text ?? '');

        if (trim($ocrText) === '') {
            return $this->buildResult(DocumentType::Unknown, [], 0);
        }

        $bestType = DocumentType::Unknown;
        $bestConfidence = 0.0;
        $bestMatched = [];
        $bestTotal = 0;

        foreach ($this->keywords as $typeString => $typeKeywords) {
            $documentType = DocumentType::tryFrom($typeString);

            if (! $documentType) {
                continue;
            }

            $matched = [];
            foreach ($typeKeywords as $keyword) {
                $keywordLower = strtolower($keyword);
                if (str_contains($ocrText, $keywordLower)) {
                    $matched[] = $keyword;
                }
            }

            $totalKeywords = count($typeKeywords);
            $confidence = $totalKeywords > 0 ? count($matched) / $totalKeywords : 0;

            // Ambil yang confidence tertinggi
            if ($confidence > $bestConfidence) {
                $bestConfidence = $confidence;
                $bestType = $documentType;
                $bestMatched = $matched;
                $bestTotal = $totalKeywords;
            }
        }

        // Jika di bawah threshold → UNKNOWN
        if ($bestConfidence < $this->confidenceThreshold) {
            return $this->buildResult(DocumentType::Unknown, [], 0);
        }

        return $this->buildResult($bestType, $bestMatched, $bestTotal, $bestConfidence);
    }

    /**
     * Build classification result.
     */
    private function buildResult(
        DocumentType $type,
        array $matchedKeywords,
        int $totalKeywords,
        ?float $overrideConfidence = null,
    ): array {
        $confidence = $overrideConfidence ?? ($totalKeywords > 0 ? 1.0 : 0.0);

        return [
            'document_type' => $type,
            'confidence' => round($confidence, 4),
            'matched_keywords' => $matchedKeywords,
            'total_keywords' => $totalKeywords,
        ];
    }
}
