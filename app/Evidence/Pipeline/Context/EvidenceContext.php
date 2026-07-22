<?php

declare(strict_types=1);

namespace App\Evidence\Pipeline\Context;

use App\Enums\DocumentType;
use App\Evidence\DTO\EvidenceData;
use App\Evidence\DTO\TransactionDraft;
use App\Models\Evidence;

/**
 * EvidenceContext — Context object yang menjadi media komunikasi antar stage.
 *
 * Setiap stage membaca/menulis data melalui context.
 * Tidak ada direct dependency antar stage.
 */
class EvidenceContext
{
    public Evidence $evidence;

    public ?string $ocrText = null;

    public ?string $normalizedText = null;

    public ?DocumentType $documentType = null;

    public ?EvidenceData $parsedData = null;

    public ?TransactionDraft $draft = null;

    public array $warnings = [];

    public array $metadata = [];

    /** @var array<string, int> Stage durations dalam milidetik */
    public array $stageDurations = [];

    public int $normalizationChanges = 0;

    public function __construct(Evidence $evidence)
    {
        $this->evidence = $evidence;
    }

    /**
     * Dapatkan text yang akan dipakai oleh classifier dan parser.
     * Prioritas: normalized > raw OCR.
     */
    public function getTextForProcessing(): string
    {
        return $this->normalizedText ?? $this->ocrText ?? '';
    }

    /**
     * Record durasi stage.
     */
    public function recordStageDuration(string $stage, int $ms): void
    {
        $this->stageDurations[$stage] = $ms;
    }

    /**
     * Total durasi seluruh pipeline.
     */
    public function totalDurationMs(): int
    {
        return array_sum($this->stageDurations);
    }

    /**
     * Konversi ke array untuk logging/metadata.
     */
    public function toArray(): array
    {
        return [
            'evidence_id' => $this->evidence->id,
            'evidence_uuid' => $this->evidence->uuid,
            'ocr_text_length' => strlen($this->ocrText ?? ''),
            'normalized_text_length' => strlen($this->normalizedText ?? ''),
            'document_type' => $this->documentType?->value,
            'has_parsed_data' => $this->parsedData !== null,
            'has_draft' => $this->draft !== null,
            'warnings' => $this->warnings,
            'stage_durations' => $this->stageDurations,
            'normalization_changes' => $this->normalizationChanges,
        ];
    }
}
