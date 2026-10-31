<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\DTO\AIParseResult;
use App\DTO\ChatProcessOptions;
use App\DTO\AiProviderRequest;
use App\Models\User;
use App\Services\AI\AIManager;
use App\Services\AI\Providers\PythonNLPProvider;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * CommaMultiParser — jalur lokal untuk multi-transaksi yang dipisah koma.
 *
 * Aturan user: teks seperti "beli makan 20k tunai, beli bakso 10k seabank,
 * dapet gaji 20k dana, pindah saldo dana ke spay 200k" TIDAK langsung
 * dilempar ke LLM. Urutan:
 *
 *   1. Pecah teks per koma menjadi segmen (aman terhadap desimal "1,5"
 *      dan ribuan gaya Inggris "1,000" — hanya koma yang diikuti spasi
 *      yang dianggap pemisah).
 *   2. Parse tiap segmen dengan parser lokal (Python NLP — gratis, sama
 *      dengan jalur single).
 *   3. Jika SEMUA segmen lolos ambang skor minimum → rakit hasil multi
 *      tanpa menyentuh LLM sama sekali.
 *   4. Jika ADA segmen yang gagal / skornya di bawah ambang → return null
 *      dan caller melanjutkan ke jalur LLM multi yang existing.
 *
 * Return bukan-null = hasil final (bentuk sama dengan processMulti);
 * return null = "jalur ini tidak layak, silakan fallback".
 */
class CommaMultiParser
{
    /**
     * Pola nominal bahasa Indonesia — identik dengan MultiTransactionRouter.
     */
    private const NOMINAL_PATTERN = '/\b(\d+(?:[.,]\d+)?)\s*(k|rb|ribu|rbu|jt|juta|jtr|m)?\b/iu';

    /**
     * Batas jumlah segmen: lebih dari ini dianggap bukan pesan catat harian
     * dan lebih aman diserahkan ke LLM (konteks utuh).
     */
    private const MAX_SEGMENTS = 10;

    public function __construct(
        private readonly PythonNLPProvider $pythonNlp,
        private readonly AIManager $aiManager,
        private readonly DraftPayloadBuilder $draftBuilder,
        private readonly MultiTransactionProcessor $multiProcessor,
    ) {}

    /**
     * Coba proses teks multi-transaksi via pemisahan koma + parser lokal.
     *
     * @return array|null Bentuk hasil identik dengan MultiTransactionProcessor::process()
     *                    saat seluruh segmen lolos ambang; null = fallback ke jalur LLM.
     */
    public function tryProcess(
        User $user,
        string $text,
        array $wallets,
        array $categories,
        array $activeMemories,
        string $source,
        ?ChatProcessOptions $options = null,
    ): ?array {
        $segments = $this->splitSegments($text);
        if ($segments === null) {
            return null;
        }

        $threshold = (float) config('bendaharaku.ai.parser.min_confidence', 0.85);
        $pythonPayload = $this->aiManager->injectMemories($wallets, $categories, $activeMemories);

        $parsedResults = [];
        foreach ($segments as $segment) {
            $result = $this->parseSegment($segment, $pythonPayload['wallets'], $pythonPayload['categories']);

            // Gate per segmen: parser gagal / skor di bawah ambang / nominal-
            // kategori tidak lengkap → seluruh teks dialihkan ke LLM.
            if ($result === null || ! $result->success || $result->transaction === null) {
                Log::info('CommaMultiParser: segmen gagal diparsing, fallback ke LLM', [
                    'user_id' => $user->id,
                    'segment' => $segment,
                ]);

                return null;
            }

            if ($result->confidence < $threshold) {
                Log::info('CommaMultiParser: skor segmen di bawah ambang, fallback ke LLM', [
                    'user_id' => $user->id,
                    'segment' => $segment,
                    'confidence' => $result->confidence,
                    'threshold' => $threshold,
                ]);

                return null;
            }

            if ($this->draftBuilder->validateParsed($result->transaction) !== null) {
                Log::info('CommaMultiParser: segmen gagal guard nominal/kategori, fallback ke LLM', [
                    'user_id' => $user->id,
                    'segment' => $segment,
                ]);

                return null;
            }

            $parsedResults[] = $result;
        }

        Log::info('CommaMultiParser: seluruh segmen lolos ambang, proses tanpa LLM', [
            'user_id' => $user->id,
            'segments' => count($segments),
            'min_confidence' => min(array_map(fn ($r) => $r->confidence, $parsedResults)),
        ]);

        return $this->multiProcessor->processLocalSegments(
            user: $user,
            parsedResults: $parsedResults,
            text: $text,
            source: $source,
            options: $options,
        );
    }

    /**
     * Pecah teks menjadi segmen per koma.
     *
     * Aturan pemisahan: hanya koma yang diikuti whitespace. Dengan itu:
     *   - "gaji 1,5 juta"      → TIDAK terpecah (desimal Indonesia)
     *   - "1,000,000"          → TIDAK terpecah (ribuan gaya Inggris)
     *   - "makan 20k, bakso 10k" → terpecah 2 segmen
     *
     * Setiap segmen wajib memuat minimal satu nominal — segmen tanpa nominal
     * (mis. sambungan "dan juga") berarti bukan pola daftar transaksi.
     *
     * @return list<string>|null null = bukan pola multi-koma yang layak
     */
    private function splitSegments(string $text): ?array
    {
        $trimmed = trim($text);
        if (! str_contains($trimmed, ',')) {
            return null;
        }

        $parts = preg_split('/,(?=\s)/u', $trimmed) ?: [];
        $parts = array_values(array_filter(array_map('trim', $parts), fn (string $p) => $p !== ''));

        if (count($parts) < 2 || count($parts) > self::MAX_SEGMENTS) {
            return null;
        }

        foreach ($parts as $part) {
            if (! preg_match(self::NOMINAL_PATTERN, $part)) {
                return null;
            }
        }

        return $parts;
    }

    /**
     * Parse satu segmen dengan parser lokal (Python NLP).
     * Panggilan langsung — TANPA fallback LLM di dalam; gate ada di tryProcess.
     */
    private function parseSegment(string $segment, array $wallets, array $categories): ?AIParseResult
    {
        try {
            $request = new AiProviderRequest(
                text: $segment,
                apiKey: '',
                model: 'local-nlp',
                wallets: $wallets,
                categories: $categories,
            );

            return $this->pythonNlp->parseTransaction($request);
        } catch (Throwable $e) {
            Log::warning('CommaMultiParser: Python NLP tidak tersedia', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
