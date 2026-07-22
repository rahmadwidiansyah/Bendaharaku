<?php

declare(strict_types=1);

namespace App\Evidence\Pipeline\Contracts;

use App\Evidence\Pipeline\Context\EvidenceContext;
use Closure;

/**
 * Contract untuk setiap stage dalam evidence pipeline.
 *
 * Setiap stage:
 * - hanya bertanggung jawab pada satu proses
 * - tidak mengetahui implementasi stage lain
 * - memanggil stage berikutnya melalui $next
 */
interface EvidenceStage
{
    /**
     * Proses stage dan lanjutkan ke stage berikutnya.
     */
    public function handle(EvidenceContext $context, Closure $next): void;
}
