<?php

declare(strict_types=1);

namespace App\Support\Admin;

use Closure;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\Request;

/**
 * Helper sorting tabel admin yang aman.
 *
 * Kolom yang boleh di-sort WAJIB didefinisikan di whitelist per endpoint.
 * Nilai `sort` / `direction` dari request tidak pernah diinterpolasi langsung
 * ke query — hanya key whitelist yang dipakai.
 */
final class SortableTable
{
    public const ALLOWED_PER_PAGE = [10, 15, 20, 25, 50, 100];

    /**
     * @param  array<string, string|Closure(Builder, string): void>  $columns
     * @return array{sort: string, direction: 'asc'|'desc'}
     */
    public static function apply(
        Builder $query,
        Request $request,
        array $columns,
        string $defaultSort,
        string $defaultDirection = 'desc',
        ?string $tieBreaker = 'id',
    ): array {
        $sort = (string) $request->query('sort', $defaultSort);
        if (! array_key_exists($sort, $columns)) {
            $sort = $defaultSort;
        }

        $direction = strtolower((string) $request->query('direction', $defaultDirection));
        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = $defaultDirection;
        }

        $target = $columns[$sort];
        if ($target instanceof Closure) {
            $target($query, $direction);
        } else {
            $query->orderBy($target, $direction);
        }

        // Tie-breaker supaya urutan stabil antar halaman.
        if ($tieBreaker !== null) {
            $query->orderBy($tieBreaker, $defaultDirection);
        }

        return ['sort' => $sort, 'direction' => $direction];
    }

    public static function perPage(Request $request, int $default = 15): int
    {
        $perPage = (int) $request->input('per_page', $default);

        return in_array($perPage, self::ALLOWED_PER_PAGE, true) ? $perPage : $default;
    }

    /**
     * @return array{sort: string, direction: string, per_page: int}
     */
    public static function filters(Request $request, array $sort, int $perPage): array
    {
        return [
            'sort' => $sort['sort'],
            'direction' => $sort['direction'],
            'per_page' => $perPage,
        ];
    }
}
