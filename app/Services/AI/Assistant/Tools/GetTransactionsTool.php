<?php

declare(strict_types=1);

namespace App\Services\AI\Assistant\Tools;

use App\Models\TransactionLog;
use App\Models\User;
use App\Services\AI\Assistant\AssistantTool;
use App\Services\AI\Assistant\Tools\Concerns\SummarizesCashflow;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

final class GetTransactionsTool implements AssistantTool
{
    use SummarizesCashflow;

    public const MAX_DAYS = 92;

    public const MAX_LIMIT = 50;

    public function name(): string
    {
        return 'get_transactions';
    }

    public function description(): string
    {
        return 'Ambil transaksi pengguna pada rentang tanggal tertentu, opsional difilter kategori, tipe, atau dompet. Untuk pertanyaan tentang bulan tertentu (mis. "oktober"), WAJIB isi period (YYYY-MM) — jangan mengandalkan default, karena default adalah bulan berjalan.';
    }

    public function schema(): array
    {
        return [
            'name' => $this->name(),
            'description' => $this->description(),
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'period' => [
                        'type' => 'string',
                        'description' => 'Bulan yang ditanyakan user, format YYYY-MM (mis. 2026-10 untuk Oktober 2026). WAJIB untuk pertanyaan per-bulan; hanya boleh dalam 12 bulan terakhir.',
                    ],
                    'start_date' => [
                        'type' => 'string',
                        'description' => 'Tanggal awal YYYY-MM-DD untuk rentang kustom. Default awal bulan berjalan.',
                    ],
                    'end_date' => [
                        'type' => 'string',
                        'description' => 'Tanggal akhir YYYY-MM-DD untuk rentang kustom. Default akhir bulan berjalan.',
                    ],
                    'category_id' => [
                        'type' => 'integer',
                        'description' => 'ID kategori dari get_categories.',
                    ],
                    'type' => [
                        'type' => 'string',
                        'enum' => ['income', 'expense', 'transfer', 'debt', 'receivable'],
                        'description' => 'Filter tipe transaksi.',
                    ],
                    'wallet_id' => [
                        'type' => 'integer',
                        'description' => 'ID dompet dari get_wallets (sumber atau tujuan).',
                    ],
                    'limit' => [
                        'type' => 'integer',
                        'minimum' => 1,
                        'maximum' => self::MAX_LIMIT,
                        'description' => 'Jumlah baris maksimum (default 20, maksimal 50).',
                    ],
                    'sort' => [
                        'type' => 'string',
                        'enum' => ['date_desc', 'date_asc', 'amount_desc', 'amount_asc'],
                        'description' => 'Urutan hasil. Gunakan amount_desc untuk pertanyaan transaksi terbesar, amount_asc untuk terkecil. Default date_desc.',
                    ],
                ],
                'required' => [],
            ],
        ];
    }

    public function rules(): array
    {
        return [
            'period' => ['sometimes', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'start_date' => ['sometimes', 'date_format:Y-m-d'],
            'end_date' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'category_id' => ['sometimes', 'integer', 'min:1'],
            'type' => ['sometimes', 'string', 'in:income,expense,transfer,debt,receivable'],
            'wallet_id' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:'.self::MAX_LIMIT],
            'sort' => ['sometimes', 'string', 'in:date_desc,date_asc,amount_desc,amount_asc'],
        ];
    }

    public function execute(User $user, array $params): array
    {
        [$start, $end] = $this->resolveRange($params);
        $limit = (int) ($params['limit'] ?? 20);

        $query = TransactionLog::query()
            ->where('user_id', $user->id)
            ->where('is_cleared', true)
            ->whereNull('deleted_at')
            ->whereBetween('date', [$start, $end])
            ->with([
                'type:id,name',
                'category:id,category_name,custom_name',
                'sourceWallet:id,name',
                'destinationWallet:id,name',
            ]);

        if (isset($params['category_id'])) {
            $query->where('category_id', (int) $params['category_id']);
        }

        if (isset($params['type'])) {
            $typeName = ucfirst((string) $params['type']);
            $query->whereHas('type', fn ($typeQuery) => $typeQuery->where('name', $typeName));
        }

        if (isset($params['wallet_id'])) {
            $walletId = (int) $params['wallet_id'];
            $query->where(fn ($walletQuery) => $walletQuery
                ->where('source_wallet_id', $walletId)
                ->orWhere('destination_wallet_id', $walletId));
        }

        $this->applySort($query, (string) ($params['sort'] ?? 'date_desc'));

        $transactions = $query
            ->limit($limit)
            ->get();

        // Draft (is_cleared=false) sengaja tidak masuk daftar, tapi jumlahnya
        // dilaporkan agar LLM bisa menyebut "X draft belum dikonfirmasi"
        // alih-alih keliru menyimpulkan data kosong.
        $draftCount = TransactionLog::query()
            ->where('user_id', $user->id)
            ->where('is_cleared', false)
            ->whereNull('deleted_at')
            ->whereBetween('date', [$start, $end])
            ->count();

        return [
            'count' => $transactions->count(),
            'draft_count' => $draftCount,
            'period' => $params['period'] ?? null,
            'start_date' => $start,
            'end_date' => $end,
            'transactions' => $transactions->map(fn (TransactionLog $transaction) => [
                'id' => $transaction->id,
                'date' => $transaction->date?->toDateString(),
                'type' => $transaction->type?->name,
                'category' => $transaction->category?->category_name,
                'amount' => (float) $transaction->amount,
                'source_wallet' => $transaction->sourceWallet?->name,
                'destination_wallet' => $transaction->destinationWallet?->name,
                'subject' => $transaction->subject,
                'notes' => $transaction->notes !== null
                    ? mb_substr($transaction->notes, 0, 200)
                    : null,
            ])->all(),
        ];
    }

    /** @return array{0:string,1:string} */
    private function resolveRange(array $params): array
    {
        // period (YYYY-MM) adalah jalur utama untuk pertanyaan per-bulan:
        // LLM sering gagal menghitung start/end date eksplisit dan diam-diam
        // memakai default bulan berjalan (kasus "oktober" ter-query November).
        if (isset($params['period'])) {
            ['year' => $year, 'month' => $month] = $this->parsePeriod((string) $params['period']);
            $this->assertMonthInRange($year, $month);

            $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $end = $start->copy()->endOfMonth();

            return [$start->toDateString(), $end->toDateString()];
        }

        $start = isset($params['start_date'])
            ? Carbon::parse((string) $params['start_date'])
            : now()->startOfMonth();
        $end = isset($params['end_date'])
            ? Carbon::parse((string) $params['end_date'])
            : now()->endOfMonth();

        if ($end->lt($start)) {
            throw ValidationException::withMessages([
                'end_date' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
            ]);
        }

        if ($start->diffInDays($end) > self::MAX_DAYS) {
            throw ValidationException::withMessages([
                'end_date' => 'Rentang tanggal maksimal '.self::MAX_DAYS.' hari.',
            ]);
        }

        return [$start->toDateString(), $end->toDateString()];
    }

    private function applySort($query, string $sort): void
    {
        match ($sort) {
            'date_asc' => $query->orderBy('date')->orderBy('id'),
            'amount_desc' => $query->orderByDesc('amount')->orderByDesc('date')->orderByDesc('id'),
            'amount_asc' => $query->orderBy('amount')->orderByDesc('date')->orderByDesc('id'),
            default => $query->orderByDesc('date')->orderByDesc('id'),
        };
    }
}
