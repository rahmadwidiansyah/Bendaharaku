<?php

declare(strict_types=1);

namespace App\Evidence\Events;

use App\Models\Evidence;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EvidenceUploaded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Evidence $evidence,
    ) {}
}
