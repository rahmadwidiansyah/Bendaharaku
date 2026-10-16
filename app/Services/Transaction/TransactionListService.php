<?php

declare(strict_types=1);

namespace App\Services\Transaction;

use App\Models\TransactionType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Daftar transaksi gabungan (log + draft) dengan pagination keyset.
 *
 * Desain pagination (lihat review penutup Tahap 2A):
 * - UNION ALL dua leg (satu per sumber, filter per-sumber sesuai semantik
 *   Dashboard yang berlaku: rentang tanggal = kolom `date` untuk log dan
 *   `created_at` untuk draft — sengaja TIDAK disatukan).
 * - Urutan total deterministik: sort_date DESC, sort_ts DESC, kind ASC,
 *   id DESC. Kolom `kind` ('draft' < 'log') WAJIB sebelum `id` karena ID dua
 *   tabel berada pada sequence independen yang rentangnya tumpang-tindih
 *   (cursor `before_id` murni terbukti tidak aman).
 * - Cursor opak base64url(JSON {d,t,k,i}) memuat seluruh kunci urutan;
 *   kondisi halaman-berikutnya adalah cerminan leksikografis ORDER BY.
 * - Agregat harian + marker kalender dihitung dari SELURUH rentang (query
 *   ringan terpisah), bukan dari baris halaman aktif.
 * - Draft kedaluwarsa tetap tampil seperti perilaku baca saat ini (tidak
 *   ada filter expires_at) — lihat test pengunci perilaku.
 */
class TransactionListService
{
    public const DEFAULT_LIMIT = 50;

    public const MAX_LIMIT = 100;

    /**
     * @param  array{start_date?: string, end_date?: string, type?: string, search?: string}  $filters
     * @return array{data: list<array>, next_cursor: ?string, has_more: bool}
     */
    public function paginate(User $user, array $filters, ?string $cursor, int $limit = self::DEFAULT_LIMIT): array
    {
        $limit = max(1, min(self::MAX_LIMIT, $limit));
        $norm = $this->normalizeFilters($filters);
        $decoded = self::decodeCursor($cursor);

        $union = $this->buildUnion($user->id, $norm);

        $rows = DB::query()->fromSub($union, 'u')
            ->select(['kind', 'id', 'sort_date', 'sort_ts', 'amount', 'notes', 'subject', 'is_cleared', 'reference_number', 'raw_date', 'created_at', 'draft_created_at', 'type_id', 'type_name', 'category_id', 'category_name', 'category_icon', 'sw_id', 'sw_name', 'sw_group', 'dw_id', 'dw_name', 'due_date', 'due_date_type', 'due_date_interval', 'payload', 'original_text'])
            ->when($decoded, function ($q) use ($decoded) {
                $q->where(function ($w) use ($decoded) {
                    $w->where('sort_date', '<', $decoded['d'])
                        ->orWhere(fn ($q2) => $q2
                            ->where('sort_date', $decoded['d'])
                            ->where('sort_ts', '<', $decoded['t']))
                        ->orWhere(fn ($q2) => $q2
                            ->where('sort_date', $decoded['d'])
                            ->where('sort_ts', $decoded['t'])
                            ->where(fn ($q3) => $q3
                                ->where('kind', '>', $decoded['k'])
                                ->orWhere(fn ($q4) => $q4
                                    ->where('kind', $decoded['k'])
                                    ->where('id', '<', $decoded['i']))));
                });
            })
            ->orderByDesc('sort_date')
            ->orderByDesc('sort_ts')
            ->orderBy('kind')
            ->orderByDesc('id')
            ->limit($limit + 1)
            ->get();

        $hasMore = $rows->count() > $limit;
        $page = $rows->take($limit);

        $data = $page->map(fn ($row) => $this->mapRow($row))->all();

        $last = $page->last();
        $nextCursor = $hasMore && $last
            ? self::encodeCursor((string) $last->sort_date, (float) $last->sort_ts, (string) $last->kind, (int) $last->id)
            : null;

        return ['data' => $data, 'next_cursor' => $nextCursor, 'has_more' => $hasMore];
    }

    /**
     * Subtotal income/expense per tanggal + marker kalender untuk SELURUH
     * rentang terfilter. Klasifikasi menyalin persis logika Dashboard.vue
     * (nama tipe + grup wallet; draft tanpa grup wallet selalu expense
     * untuk tipe Debt/Receivable karena payload tak menyimpan grup).
     *
     * @param  array{start_date?: string, end_date?: string, type?: string, search?: string}  $filters
     * @return array{dailyTotals: array<string, array{income: float, expense: float}>, markers: list<string>}
     */
    public function aggregates(User $user, array $filters): array
    {
        $norm = $this->normalizeFilters($filters);
        $totals = [];

        $add = function (string $date, float $amount, string $typeName, ?string $srcGroup) use (&$totals) {
            $isIncome = $typeName === 'Income';
            $isExpense = $typeName === 'Expense';
            if (in_array($typeName, ['Debt', 'Receivable'], true)) {
                if ($srcGroup === 'System') {
                    $isIncome = true;
                } else {
                    $isExpense = true;
                }
            }
            $totals[$date] ??= ['income' => 0.0, 'expense' => 0.0];
            if ($isIncome) {
                $totals[$date]['income'] += $amount;
            }
            if ($isExpense) {
                $totals[$date]['expense'] += $amount;
            }
        };

        $logRows = $this->buildLogQuery($user->id, $norm, true)
            ->select(['tl.date as d', 'tl.amount as a', 'tt.name as t', 'sw.group_type as g'])
            ->get();
        foreach ($logRows as $r) {
            $add(substr((string) $r->d, 0, 10), (float) $r->a, (string) $r->t, $r->g !== null ? (string) $r->g : null);
        }

        $draftRows = $this->buildDraftQuery($user->id, $norm, true)
            ->select(['td.created_at as c', 'td.payload as p'])
            ->get();
        foreach ($draftRows as $r) {
            $payload = is_string($r->p) ? json_decode($r->p, true) : (array) $r->p;
            $typeName = ucfirst((string) ($payload['type_key'] ?? 'expense'));
            $add($this->draftDateKey($payload, (string) $r->c), (float) ($payload['amount'] ?? 0), $typeName, null);
        }

        ksort($totals);

        return ['dailyTotals' => $totals, 'markers' => array_keys($totals)];
    }

    public static function encodeCursor(string $date, float $ts, string $kind, int $id): string
    {
        return rtrim(strtr(base64_encode(json_encode(['d' => $date, 't' => $ts, 'k' => $kind, 'i' => $id])), '+/', '-_'), '=');
    }

    /**
     * @return ?array{d: string, t: float, k: string, i: int}
     *
     * @throws InvalidArgumentException bila cursor cacat.
     */
    public static function decodeCursor(?string $cursor): ?array
    {
        if ($cursor === null || $cursor === '') {
            return null;
        }
        $json = base64_decode(strtr($cursor, '-_', '+/'), true);
        $data = is_string($json) ? json_decode($json, true) : null;
        if (! is_array($data)
            || ! isset($data['d'], $data['t'], $data['k'], $data['i'])
            || ! is_string($data['d']) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['d'])
            || ! is_numeric($data['t'])
            || ! in_array($data['k'], ['log', 'draft'], true)
            || ! is_int($data['i'])
        ) {
            throw new InvalidArgumentException('Cursor tidak valid.');
        }

        return ['d' => $data['d'], 't' => (float) $data['t'], 'k' => $data['k'], 'i' => $data['i']];
    }

    /** @param  array{start_date?: string, end_date?: string, type?: string, search?: string}  $filters */
    private function normalizeFilters(array $filters): array
    {
        $now = Carbon::now();
        $start = (string) ($filters['start_date'] ?? $now->copy()->startOfMonth()->toDateString());
        $end = (string) ($filters['end_date'] ?? $now->copy()->endOfMonth()->toDateString());
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) {
            $start = $now->copy()->startOfMonth()->toDateString();
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
            $end = $now->copy()->endOfMonth()->toDateString();
        }
        $type = strtolower(trim((string) ($filters['type'] ?? '')));
        $search = trim((string) ($filters['search'] ?? ''));

        return ['start' => $start, 'end' => $end, 'type' => $type, 'search' => $search];
    }

    /** @return array<string, int> lowercase english key → type_id */
    private function typeMap(): array
    {
        return TransactionType::query()
            ->whereIn('name', ['Income', 'Expense', 'Transfer', 'Debt', 'Receivable'])
            ->pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [strtolower($name) => (int) $id])
            ->all();
    }

    private function buildLogQuery(int $userId, array $norm, bool $forAggregates = false): mixed
    {
        $q = DB::table('transaction_logs as tl')
            ->join('transaction_types as tt', 'tt.id', '=', 'tl.type_id')
            ->leftJoin('wallets as sw', 'sw.id', '=', 'tl.source_wallet_id')
            ->where('tl.user_id', $userId)
            ->where('tl.is_cleared', true)
            ->whereNull('tl.deleted_at')
            ->whereBetween('tl.date', [$norm['start'], $norm['end']]);

        if ($norm['type'] !== '') {
            $map = $this->typeMap();
            // Semantik Dashboard: tipe tak dikenal → filter diabaikan (bukan hasil kosong).
            if (isset($map[$norm['type']])) {
                $q->where('tl.type_id', $map[$norm['type']]);
            }
        }

        if ($norm['search'] !== '') {
            $search = $norm['search'];
            $q->where(function ($w) use ($search) {
                if (ctype_digit($search)) {
                    $w->where('tl.id', (int) $search)
                        ->orWhere('tl.reference_number', 'ILIKE', "%{$search}%");
                } else {
                    $w->where('tl.notes', 'ILIKE', "%{$search}%")
                        ->orWhere('tl.subject', 'ILIKE', "%{$search}%")
                        ->orWhere('tl.reference_number', 'ILIKE', "%{$search}%")
                        ->orWhereExists(function ($e) use ($search) {
                            $e->select(DB::raw(1))->from('categories as c')
                                ->whereColumn('c.id', 'tl.category_id')
                                ->where('c.category_name', 'ILIKE', "%{$search}%");
                        });
                }
            });
        }

        return $q;
    }

    private function buildDraftQuery(int $userId, array $norm, bool $forAggregates = false): mixed
    {
        $q = DB::table('transaction_drafts as td')
            ->where('td.user_id', $userId)
            ->where('td.status', 'pending')
            // Keputusan produk 1A: draft kedaluwarsa disembunyikan dari daftar
            // (konsisten dengan isPending() yang juga menolak konfirmasi).
            ->where(fn ($w) => $w->whereNull('td.expires_at')->orWhere('td.expires_at', '>', DB::raw('NOW()')))
            ->whereBetween('td.created_at', [$norm['start'].' 00:00:00', $norm['end'].' 23:59:59']);

        if ($norm['type'] !== '') {
            $q->whereRaw("LOWER(td.payload->>'type_key') ILIKE ?", [$norm['type']]);
        }

        if ($norm['search'] !== '') {
            $search = $norm['search'];
            $q->where(function ($w) use ($search) {
                $w->where('td.original_text', 'ILIKE', "%{$search}%")
                    ->orWhereRaw("td.payload->>'notes' ILIKE ?", ["%{$search}%"])
                    ->orWhereRaw("td.payload->>'subject' ILIKE ?", ["%{$search}%"])
                    ->orWhereRaw("td.payload->>'category_name' ILIKE ?", ["%{$search}%"]);
            });
        }

        return $q;
    }

    private function buildUnion(int $userId, array $norm): mixed
    {
        $logs = $this->buildLogQuery($userId, $norm)
            ->leftJoin('categories as c', 'c.id', '=', 'tl.category_id')
            ->leftJoin('wallets as dw', 'dw.id', '=', 'tl.destination_wallet_id')
            ->selectRaw(implode(', ', [
                "'log' as kind",
                'tl.id as id',
                'tl.date::text as sort_date',
                'FLOOR(EXTRACT(EPOCH FROM tl.created_at)) as sort_ts',
                'tl.amount as amount',
                'tl.notes as notes',
                'tl.subject as subject',
                'tl.is_cleared as is_cleared',
                'tl.reference_number as reference_number',
                'tl.date::text as raw_date',
                "to_char(tl.created_at, 'YYYY-MM-DD HH24:MI:SS') as created_at",
                'tt.id as type_id',
                'tt.name as type_name',
                'c.id as category_id',
                'c.category_name as category_name',
                'c.icon as category_icon',
                'sw.id as sw_id',
                'sw.name as sw_name',
                'sw.group_type as sw_group',
                'dw.id as dw_id',
                'dw.name as dw_name',
                'tl.due_date::text as due_date',
                'tl.due_date_type as due_date_type',
                'tl.due_date_interval as due_date_interval',
                'NULL::jsonb as payload',
                'NULL::text as original_text',
                'NULL::text as draft_created_at',
            ]));

        $drafts = $this->buildDraftQuery($userId, $norm)
            ->selectRaw(implode(', ', [
                "'draft' as kind",
                'td.id as id',
                "CASE WHEN td.payload->>'date' ~ '^\\d{4}-\\d{2}-\\d{2}' THEN substring(td.payload->>'date', 1, 10) ELSE to_char(td.created_at, 'YYYY-MM-DD') END as sort_date",
                'FLOOR(EXTRACT(EPOCH FROM td.created_at)) as sort_ts',
                'NULL::numeric as amount',
                'NULL::text as notes',
                'NULL::text as subject',
                'false as is_cleared',
                'NULL::text as reference_number',
                'NULL::text as raw_date',
                'NULL::text as created_at',
                'NULL::bigint as type_id',
                'NULL::text as type_name',
                'NULL::bigint as category_id',
                'NULL::text as category_name',
                'NULL::text as category_icon',
                'NULL::bigint as sw_id',
                'NULL::text as sw_name',
                'NULL::text as sw_group',
                'NULL::bigint as dw_id',
                'NULL::text as dw_name',
                'NULL::text as due_date',
                'NULL::text as due_date_type',
                'NULL::integer as due_date_interval',
                'td.payload as payload',
                'td.original_text as original_text',
                "to_char(td.created_at, 'YYYY-MM-DD HH24:MI:SS') as draft_created_at",
            ]));

        return $logs->unionAll($drafts);
    }

    private function draftDateKey(array $payload, string $createdAt): string
    {
        $raw = (string) ($payload['date'] ?? '');
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $raw, $m)) {
            return $m[1];
        }

        return substr($createdAt, 0, 10);
    }

    /** Petakan satu baris union ke bentuk array yang IDENTIK dengan map controller lama. */
    private function mapRow(object $row): array
    {
        if ($row->kind === 'draft') {
            $payload = is_string($row->payload) ? (json_decode($row->payload, true) ?? []) : (array) $row->payload;
            $created = Carbon::parse($row->draft_created_at);

            return [
                'id' => (int) $row->id,
                'is_draft' => true,
                'draft_id' => (int) $row->id,
                'amount' => (float) ($payload['amount'] ?? 0),
                'notes' => $payload['notes'] ?? $row->original_text,
                'subject' => $payload['subject'] ?? '-',
                'is_cleared' => false,
                'reference_number' => null,
                'date' => Carbon::parse($payload['date'] ?? $created)->translatedFormat('d M Y'),
                'raw_date' => Carbon::parse($payload['date'] ?? $created)->format('Y-m-d'),
                'time' => $created->format('H:i'),
                'type' => [
                    'id' => null,
                    'name' => ucfirst($payload['type_key'] ?? 'expense'),
                ],
                'category' => isset($payload['category_name']) ? [
                    'id' => $payload['category_id'] ?? null,
                    'category_name' => $payload['category_name'],
                ] : null,
                'source_wallet' => isset($payload['source_wallet_name']) ? [
                    'id' => $payload['source_wallet_id'] ?? null,
                    'name' => $payload['source_wallet_name'],
                ] : null,
                'destination_wallet' => isset($payload['destination_wallet_name']) ? [
                    'id' => $payload['destination_wallet_id'] ?? null,
                    'name' => $payload['destination_wallet_name'],
                ] : null,
                'due_date' => null,
                'due_date_type' => null,
                'due_date_interval' => null,
            ];
        }

        $created = Carbon::parse($row->created_at);

        return [
            'id' => (int) $row->id,
            'amount' => (float) $row->amount,
            'notes' => $row->notes,
            'subject' => $row->subject,
            'is_cleared' => (bool) $row->is_cleared,
            'reference_number' => $row->reference_number,
            'date' => Carbon::parse($row->raw_date)->translatedFormat('d M Y'),
            'raw_date' => $row->raw_date,
            'time' => $created->format('H:i'),
            'type' => $row->type_id === null ? null : ['id' => (int) $row->type_id, 'name' => $row->type_name],
            'category' => $row->category_id === null ? null : [
                'id' => (int) $row->category_id,
                'category_name' => $row->category_name,
                'icon' => $row->category_icon,
            ],
            'source_wallet' => $row->sw_id === null ? null : [
                'id' => (int) $row->sw_id,
                'name' => $row->sw_name,
                'group_type' => $row->sw_group,
            ],
            'destination_wallet' => $row->dw_id === null ? null : [
                'id' => (int) $row->dw_id,
                'name' => $row->dw_name,
            ],
            'due_date' => $row->due_date,
            'due_date_type' => $row->due_date_type,
            'due_date_interval' => $row->due_date_interval === null ? null : (int) $row->due_date_interval,
        ];
    }
}
