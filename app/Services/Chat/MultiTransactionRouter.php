<?php

declare(strict_types=1);

namespace App\Services\Chat;

/**
 * Heuristik router: tentukan apakah teks mengandung satu atau banyak transaksi.
 *
 * Strategi:
 *   - Cek kata penghubung eksplisit (dan, lalu, setelah itu, dll.)
 *   - Hitung jumlah nominal dalam satu kalimat
 *   - Deteksi separator kalimat (koma, titik koma, newline)
 *   - Deteksi pola berulang "<aktivitas> <nominal>"
 *
 * Python tetap digunakan untuk single transaction karena lebih cepat dan murah.
 * Multi-transaction langsung diarahkan ke LLM Provider.
 */
class MultiTransactionRouter
{
    /**
     * Kata penghubung yang sangat kuat mengindikasikan multi-transaksi.
     */
    private const STRONG_CONNECTORS = [
        'lalu', 'kemudian', 'setelah itu', 'terus', 'trus',
        'sekaligus', 'juga', 'plus', 'sama', 'ditambah',
        'dan juga', 'serta',
    ];

    /**
     * Kata "dan" sendiri ambigu (bisa "Makan dan minum" = 1 tx, "Makan 20k dan bensin 50k" = 2 tx).
     * Ditangani terpisah dengan context check.
     */
    private const WEAK_CONNECTORS = ['dan', 'n', ','];

    /**
     * Pola nominal dalam bahasa Indonesia:
     * - Angka murni: 20000, 5000000
     * - Shorthand: 20k, 50rb, 2jt, 500ribu, 5juta, 2m, 1.5jt
     */
    private const NOMINAL_PATTERN = '/\b(\d+(?:[.,]\d+)?)\s*(k|rb|ribu|jt|juta|m|jtr|rbu)?\b/i';

    /**
     * Tentukan apakah teks mengandung multi-transaksi.
     *
     * @return bool true = gunakan LLM multi-transaction parser
     *              false = gunakan Python single-transaction parser
     */
    public function isMultiTransaction(string $text): bool
    {
        $normalized = mb_strtolower(trim($text));

        // 1. Cek strong connectors — indikasi paling kuat
        foreach (self::STRONG_CONNECTORS as $connector) {
            if (str_contains($normalized, $connector)) {
                return true;
            }
        }

        // 2. Hitung jumlah nominal dalam kalimat
        preg_match_all(self::NOMINAL_PATTERN, $normalized, $nominalMatches);
        $nominalCount = count($nominalMatches[0]);

        // > 1 nominal = sangat mungkin multi-transaksi
        if ($nominalCount > 1) {
            return true;
        }

        // 3. Cek titik koma — separator transaksi eksplisit
        if (str_contains($normalized, ';')) {
            return true;
        }

        // 4. Cek newline — user sering menulis daftar transaksi per baris
        if (substr_count($normalized, "\n") >= 1) {
            return true;
        }

        // 5. Cek koma dengan nominal di sekitarnya (bedakan "makan dan minum" vs "makan 20k, bensin 50k")
        // Hanya koma yang diikuti pola <kata> <nominal> yang dianggap multi
        if ($this->hasCommaWithMultipleActivities($normalized)) {
            return true;
        }

        return false;
    }

    /**
     * Deteksi koma yang memisahkan aktivitas berbeda dengan nominal masing-masing.
     * Contoh: "makan 20k, bensin 50k" → true
     *         "makan nasi dan lauk 20k" → false
     */
    private function hasCommaWithMultipleActivities(string $text): bool
    {
        $parts = explode(',', $text);
        if (count($parts) < 2) {
            return false;
        }

        // Setiap bagian harus mengandung minimal satu nominal agar dianggap transaksi tersendiri
        $partsWithNominal = array_filter($parts, function (string $part) {
            return (bool) preg_match(self::NOMINAL_PATTERN, trim($part));
        });

        return count($partsWithNominal) >= 2;
    }
}
