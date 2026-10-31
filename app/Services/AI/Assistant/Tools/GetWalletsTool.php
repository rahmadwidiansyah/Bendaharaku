<?php

declare(strict_types=1);

namespace App\Services\AI\Assistant\Tools;

use App\Models\User;
use App\Services\AI\Assistant\AssistantTool;

final class GetWalletsTool implements AssistantTool
{
    public function name(): string
    {
        return 'get_wallets';
    }

    public function description(): string
    {
        return 'Ambil daftar dompet pengguna beserta saldo saat ini (tanpa dompet sistem).';
    }

    public function schema(): array
    {
        return [
            'name' => $this->name(),
            'description' => $this->description(),
            'parameters' => [
                'type' => 'object',
                'properties' => (object) [],
                'required' => [],
            ],
        ];
    }

    public function rules(): array
    {
        return [];
    }

    public function execute(User $user, array $params): array
    {
        $wallets = $user->wallets()
            ->where('group_type', '!=', 'System')
            ->orderBy('id')
            ->get(['id', 'name', 'balance', 'group_type', 'is_pinned']);

        return [
            'count' => $wallets->count(),
            'wallets' => $wallets->map(fn ($wallet) => [
                'id' => $wallet->id,
                'name' => $wallet->name,
                'balance' => (float) $wallet->balance,
                'group_type' => $wallet->group_type,
                'is_pinned' => (bool) $wallet->is_pinned,
            ])->all(),
        ];
    }
}
