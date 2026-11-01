<?php

declare(strict_types=1);

namespace App\Services\AI\Assistant;

use Illuminate\Support\Facades\Validator;

/**
 * AssistantResponse — Lapis D arsitektur assistant: kontrak respons
 * terstruktur antara model dan frontend, dengan validasi ketat.
 *
 * Kontrak JSON yang diharapkan dari model:
 *   {"type":"assistant_response","message":"<teks>","status":"<status>"}
 *
 * Status yang didukung:
 *   success | draft | needs_confirmation | failed | conversation
 *
 * Aturan:
 * - parse() HANYA memvalidasi BENTUK (message + status). Keberhasilan
 *   transaksi TIDAK pernah dipercaya dari model — status final untuk
 *   jalur transaksi selalu ditimpa pemanggil dari hasil backend.
 * - Output tidak valid → null; pemanggil wajib memakai
 *   AssistantFallback::messageFor(statusAktual) agar hasil transaksi
 *   yang sudah tersimpan tetap terwakili.
 */
final class AssistantResponse
{
    public const TYPE = 'assistant_response';

    public const STATUSES = [
        'success',
        'draft',
        'needs_confirmation',
        'failed',
        'conversation',
    ];

    public const MAX_MESSAGE_LENGTH = 1000;

    public function __construct(
        public readonly string $message,
        public readonly string $status,
    ) {}

    /**
     * Ekstrak + validasi output model.
     *
     * Toleran terhadap: markdown fence, teks di sekitar JSON, "type" tidak
     * ada (konteks sudah diketahui), status tidak ada (default conversation).
     * Null = tidak valid → pemanggil memakai fallback.
     */
    public static function parse(?string $raw): ?self
    {
        $data = self::extractJson($raw);
        if ($data === null) {
            return null;
        }

        // "type" wajib benar bila ada; boleh tidak ada (model lupa).
        if (isset($data['type']) && $data['type'] !== self::TYPE) {
            return null;
        }

        // Struktural longgar (4000): pesan 1001-4000 karakter masih diselamatkan
        // lewat pemotongan di sanitizeMessage, bukan ditolak.
        $validator = Validator::make($data, [
            'message' => ['required', 'string', 'max:4000'],
            'status' => ['sometimes', 'string', 'in:'.implode(',', self::STATUSES)],
        ]);

        if ($validator->fails()) {
            return null;
        }

        $validated = $validator->validated();
        $message = self::sanitizeMessage((string) $validated['message']);

        if ($message === '') {
            return null;
        }

        return new self(
            message: $message,
            status: $validated['status'] ?? 'conversation',
        );
    }

    /**
     * Respons fallback dari template (status aktual dari backend).
     * Status tidak dikenal jatuh ke 'conversation'.
     */
    public static function fallback(string $status): self
    {
        $status = in_array($status, self::STATUSES, true) ? $status : 'conversation';

        return new self(
            message: trans('assistant.fallback.'.$status),
            status: $status,
        );
    }

    public static function make(string $message, string $status): self
    {
        return new self(message: $message, status: $status);
    }

    /** @return array{type: string, message: string, status: string} */
    public function toArray(): array
    {
        return [
            'type' => self::TYPE,
            'message' => $this->message,
            'status' => $this->status,
        ];
    }

    /**
     * Sanitasi teks pesan: buang karakter kontrol (kecuali newline/tab),
     * rapikan whitespace, potong pada batas maksimum.
     */
    private static function sanitizeMessage(string $message): string
    {
        $clean = preg_replace('/[^\P{C}\n\t]/u', '', $message) ?? '';
        $clean = trim(preg_replace('/[ \t]+/', ' ', $clean) ?? '');

        return mb_substr($clean, 0, self::MAX_MESSAGE_LENGTH);
    }

    /**
     * Ekstraksi JSON dari output model, berlapis:
     *   1. decode langsung
     *   2. strip markdown fence ```json ... ```
     *   3. ambil objek {...} seimbang pertama dari teks bebas
     * Bukan regex sederhana sebagai satu-satunya validator — setelah
     * ekstraksi, json_decode + Validator yang memutuskan.
     */
    private static function extractJson(?string $raw): ?array
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $decoded = self::tryDecode(trim($raw));
        if ($decoded !== null) {
            return $decoded;
        }

        $unfenced = self::stripFences(trim($raw));
        if ($unfenced !== null) {
            $decoded = self::tryDecode($unfenced);
            if ($decoded !== null) {
                return $decoded;
            }
        }

        $balanced = self::extractBalancedObject($raw);
        if ($balanced !== null) {
            return self::tryDecode($balanced);
        }

        return null;
    }

    private static function tryDecode(string $json): ?array
    {
        try {
            $decoded = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    private static function stripFences(string $text): ?string
    {
        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $text, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * Ambil objek {...} seimbang pertama dengan menghitung depth kurung
     * kurawal (peka string: kurung di dalam kutip tidak dihitung).
     */
    private static function extractBalancedObject(string $text): ?string
    {
        $start = strpos($text, '{');
        if ($start === false) {
            return null;
        }

        $depth = 0;
        $inString = false;
        $escaped = false;
        $length = mb_strlen($text);

        for ($i = $start; $i < $length; $i++) {
            $ch = $text[$i];

            if ($escaped) {
                $escaped = false;

                continue;
            }

            if ($ch === '\\' && $inString) {
                $escaped = true;

                continue;
            }

            if ($ch === '"') {
                $inString = ! $inString;

                continue;
            }

            if ($inString) {
                continue;
            }

            if ($ch === '{') {
                $depth++;
            } elseif ($ch === '}') {
                $depth--;
                if ($depth === 0) {
                    return mb_substr($text, $start, $i - $start + 1);
                }
            }
        }

        return null;
    }
}
