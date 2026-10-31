<?php

declare(strict_types=1);

namespace App\Services\AI\Assistant\Tools;

use App\Models\User;
use App\Services\AI\Assistant\AssistantTool;

final class GetCategoriesTool implements AssistantTool
{
    private const MAX_ROWS = 100;

    public function name(): string
    {
        return 'get_categories';
    }

    public function description(): string
    {
        return 'Ambil daftar kategori pengguna, opsional difilter berdasarkan tipe income atau expense.';
    }

    public function schema(): array
    {
        return [
            'name' => $this->name(),
            'description' => $this->description(),
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'type' => [
                        'type' => 'string',
                        'enum' => ['income', 'expense'],
                        'description' => 'Filter tipe kategori. Kosongkan untuk semua kategori.',
                    ],
                ],
                'required' => [],
            ],
        ];
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'string', 'in:income,expense'],
        ];
    }

    public function execute(User $user, array $params): array
    {
        $type = isset($params['type']) ? ucfirst((string) $params['type']) : null;

        $categories = $user->categories()
            ->with('type:id,name')
            ->when($type !== null, fn ($query) => $query->whereHas(
                'type',
                fn ($typeQuery) => $typeQuery->where('name', $type)
            ))
            ->orderBy('category_name')
            ->limit(self::MAX_ROWS)
            ->get(['id', 'category_name', 'custom_name', 'icon', 'custom_icon', 'type_id', 'system_key']);

        return [
            'count' => $categories->count(),
            'categories' => $categories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->category_name,
                'type' => $category->type?->name,
                'icon' => $category->icon,
                'system_key' => $category->system_key,
            ])->all(),
        ];
    }
}
