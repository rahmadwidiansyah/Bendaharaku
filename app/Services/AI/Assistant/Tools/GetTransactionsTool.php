<?php

declare(strict_types=1);

namespace App\Services\AI\Assistant\Tools;

use App\Models\TransactionLog;
use App\Models\User;
use App\Services\AI\Assistant\AssistantTool;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

final class GetTransactionsTool implements AssistantTool
{
    public const MAX_DAYS = 92;

    public const MAX_LIMIT = 50;

    public function name(): string
    {
        return 'get_transactions';
    }

    public function description(): string
    {
        return 'Ambil transaksi pengguna pada rentang tanggal tertentu, opsional difilter kategori, tipe, atau dompet.';
    }

    public function schema(): array
    {
        return [
            'name' => $this->name(),
            'description' => $this->description(),
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'start_date' => [
                        'type' => 'string',
                        'description' => 'Tanggal awal YYYY-MM-DD. Default awal bulan berjalan.',
                    ],
                    'end_date' => [
                        'type' => 'string',
                        'description' => 'Tanggal akhir YYYY-MM-DD. Default akhir bulan berjalan.',
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
                ],
                'required' => [],
            ],
        ];
    }

    public function rules(): array
    {
        return [
            'start_date' => ['sometimes', 'date_format:Y-m-d'],
            'end_date' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'category_id' => ['sometimes', 'integer', 'min:1'],
            'type' => ['sometimes', 'string', 'in:income,expense,transfer,debt,receivable'],
            'wallet_id' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:'.self::MAX_LIMIT],
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

        $transactions = $query
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return [
            'count' => $transactions->count(),
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
}
