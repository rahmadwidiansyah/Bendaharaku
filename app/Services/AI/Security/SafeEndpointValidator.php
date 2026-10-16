<?php

declare(strict_types=1);

namespace App\Services\AI\Security;

/**
 * Validasi + normalisasi endpoint OpenAI-compatible milik pengguna.
 * Mencegah SSRF ke jaringan privat / metadata cloud / skema berbahaya,
 * sambil tetap mendukung endpoint publik yang sah (termasuk port kustom).
 */
class SafeEndpointValidator
{
    /** Host yang selalu ditolak (metadata cloud + loopback literal). */
    private const BLOCKED_HOSTS = [
        'localhost',
        'metadata.google.internal',
        'metadata.google.com',
    ];

    private const BLOCKED_IPS = [
        '169.254.169.254', // AWS/GCP/Azure IMDS
        '100.100.100.100', // Alibaba IMDS
        '192.0.0.192', // Oracle IMDS (beberapa dok)
    ];

    /**
     * Normalisasi base URL atau throw ValidationException aman (tanpa secret).
     * - Wajib http/https (tolak file://, gopher://, ftp://, dsb).
     * - Tolak userinfo (user:pass@host), fragment query aneh tetap boleh di path? query ditolak.
     * - Resolve DNS host → tolak jika SEMUA hasil privat/reserved (toleran jika ada satu publik? tidak: tolak jika ADA yang privat — anti DNS rebinding).
     * - Tolak IP literal privat/reserved/loopback/multicast.
     * - Kembalikan URL ternormalisasi tanpa trailing slash.
     */
    public static function normalizeBaseUrl(string $raw): string
    {
        $url = trim($raw);
        if ($url === '') {
            throw new \InvalidArgumentException('Base URL tidak boleh kosong.');
        }
        if (strlen($url) > 500) {
            throw new \InvalidArgumentException('Base URL terlalu panjang.');
        }

        $parts = parse_url($url);
        $schemePart = is_array($parts) && isset($parts['scheme']) ? (string) $parts['scheme'] : '';
        $hostPart = is_array($parts) && isset($parts['host']) ? (string) $parts['host'] : '';
        if (! is_array($parts) || $schemePart === '' || $hostPart === '') {
            throw new \InvalidArgumentException('Base URL harus URL http(s) lengkap, mis. https://api.contoh.com/v1/chat/completions.');
        }

        $scheme = strtolower((string) $parts['scheme']);
        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new \InvalidArgumentException('Base URL hanya boleh http atau https.');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new \InvalidArgumentException('Base URL tidak boleh mengandung kredensial userinfo.');
        }

        $host = strtolower(trim((string) $parts['host'], '.'));
        if ($host === '' || str_contains($host, ' ') || str_contains($host, '_') && ! filter_var($host, FILTER_VALIDATE_IP)) {
            // underscore bukan hostname valid (tapi biarkan IP lolos ke cek IP).
            if (! filter_var($host, FILTER_VALIDATE_IP)) {
                throw new \InvalidArgumentException('Hostname base URL tidak valid.');
            }
        }

        if (in_array($host, self::BLOCKED_HOSTS, true)) {
            throw new \InvalidArgumentException('Host base URL tidak diizinkan.');
        }

        // IP literal → cek langsung.
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if (! self::isPublicIp($host)) {
                throw new \InvalidArgumentException('Base URL tidak boleh menunjuk ke alamat IP privat/internal.');
            }
            if (in_array($host, self::BLOCKED_IPS, true)) {
                throw new \InvalidArgumentException('Base URL tidak boleh menunjuk ke endpoint metadata cloud.');
            }
        } else {
            // Hostname → resolve DNS (A + AAAA), tolak jika ada hasil privat/reserved/metadata.
            $ips = self::resolveHostIps($host);
            if ($ips === []) {
                throw new \InvalidArgumentException('Hostname base URL tidak dapat di-resolve.');
            }
            foreach ($ips as $ip) {
                if (in_array($ip, self::BLOCKED_IPS, true) || ! self::isPublicIp($ip)) {
                    throw new \InvalidArgumentException('Base URL menunjuk ke alamat internal (DNS). Gunakan endpoint publik.');
                }
            }
        }

        $port = $parts['port'] ?? null;
        if ($port !== null && ($port < 1 || $port > 65535)) {
            throw new \InvalidArgumentException('Port base URL tidak valid.');
        }

        $path = (string) ($parts['path'] ?? '');
        // Tolak path traversal / backslash encoding.
        if (str_contains($path, '..') || str_contains($path, '\\')) {
            throw new \InvalidArgumentException('Path base URL tidak valid.');
        }

        $normalized = $scheme.'://'.$host.($port ? ':'.$port : '').rtrim($path, '/');
        // Query/fragment tidak dibutuhkan untuk base endpoint → buang agar kanonis.
        if (isset($parts['query']) && $parts['query'] !== '') {
            throw new \InvalidArgumentException('Base URL tidak boleh mengandung query string.');
        }

        return $normalized;
    }

    /** Turunkan {base}/... → {root}/models secara aman (tetap di origin yang sama). */
    public static function deriveModelsUrl(string $normalizedBase): string
    {
        $candidate = $normalizedBase;
        foreach (['/chat/completions', '/chat/completion', '/v1/chat/completions'] as $suffix) {
            if (str_ends_with($candidate, $suffix)) {
                $candidate = substr($candidate, 0, -strlen($suffix));
                break;
            }
        }

        return rtrim($candidate, '/').'/models';
    }

    /** @return list<string> */
    private static function resolveHostIps(string $host): array
    {
        $ips = [];
        // gethostbynamel hanya IPv4; tambahkan dns_get_record untuk AAAA.
        $v4 = @gethostbynamel($host);
        if (is_array($v4)) {
            $ips = array_merge($ips, $v4);
        }
        if (function_exists('dns_get_record')) {
            try {
                $aaaa = @dns_get_record($host, DNS_AAAA);
                if (is_array($aaaa)) {
                    foreach ($aaaa as $rec) {
                        if (! empty($rec['ipv6'])) {
                            $ips[] = (string) $rec['ipv6'];
                        }
                    }
                }
            } catch (\Throwable) {
                // abaikan, hasil IPv4 cukup
            }
        }

        return array_values(array_unique($ips));
    }

    private static function isPublicIp(string $ip): bool
    {
        // FILTER_FLAG_NO_PRIV_RANGE | NO_RES_RANGE menolak privat/reserved/loopback/multicast.
        // Catatan: 169.254.x.x dan 100.100.100.100 tertolak sebagai reserved/link-local.
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }
}
