<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Status lifecycle Evidence Pipeline.
 *
 * Alur: UPLOADED → QUEUED → PROCESSING → OCR_COMPLETED → CLASSIFIED → PARSED → RESOLVED → READY → COMPLETED
 *                                                       ↘ FAILED
 */
enum EvidenceStatus: string
{
    case Uploaded = 'UPLOADED';
    case Queued = 'QUEUED';
    case Processing = 'PROCESSING';
    case OcrCompleted = 'OCR_COMPLETED';
    case Classified = 'CLASSIFIED';
    case Parsed = 'PARSED';
    case Resolved = 'RESOLVED';
    case Ready = 'READY';
    case Completed = 'COMPLETED';
    case Failed = 'FAILED';

    /**
     * Label untuk ditampilkan di UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Uploaded => 'Uploaded',
            self::Queued => 'Queued',
            self::Processing => 'Processing',
            self::OcrCompleted => 'OCR Completed',
            self::Classified => 'Classified',
            self::Parsed => 'Parsed',
            self::Resolved => 'Resolved',
            self::Ready => 'Ready',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
        };
    }

    /**
     * Warna badge untuk UI (Tailwind class suffix).
     */
    public function color(): string
    {
        return match ($this) {
            self::Uploaded => 'emerald',
            self::Queued => 'blue',
            self::Processing => 'amber',
            self::OcrCompleted => 'cyan',
            self::Classified => 'indigo',
            self::Parsed => 'violet',
            self::Resolved => 'fuchsia',
            self::Ready => 'green',
            self::Completed => 'emerald',
            self::Failed => 'red',
        };
    }

    /**
     * Apakah status ini merupakan terminal state (sudah selesai).
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Failed]);
    }

    /**
     * Apakah status ini merupakan status error.
     */
    public function isError(): bool
    {
        return $this === self::Failed;
    }
}
