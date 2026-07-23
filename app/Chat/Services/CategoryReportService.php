<?php

declare(strict_types=1);

namespace App\Chat\Services;

use App\Chat\Components\ReportSectionComponent;
use App\Chat\Components\TextComponent;
use App\Chat\DTOs\ChatResponse;
use App\Models\User;

class CategoryReportService
{
    public function buildCategoryResponse(User $user, array $metadata): ChatResponse
    {
        $categories = $user->categories()
            ->with('type')
            ->orderBy('type_id')
            ->orderBy('category_name')
            ->get();

        if ($categories->isEmpty()) {
            return ChatResponse::command([
                new TextComponent(translationKey: 'chat.command.category_empty'),
            ], $metadata);
        }

        $grouped = $categories->groupBy(fn ($category) => $category->type?->name ?? 'Other');

        $sections = [];
        foreach ($grouped as $typeName => $items) {
            $sectionKey = match (strtolower($typeName)) {
                'income' => 'chat.command.category_section_income',
                'expense' => 'chat.command.category_section_expense',
                'transfer' => 'chat.command.category_section_transfer',
                'debt' => 'chat.command.category_section_debt',
                'receivable' => 'chat.command.category_section_receivable',
                default => null,
            };
            $typeIcon = match (strtolower($typeName)) {
                'income' => '💰',
                'expense' => '💸',
                'transfer' => '🔄',
                'debt' => '🤝',
                'receivable' => '💵',
                default => '📁',
            };

            $sections[] = [
                'type_name' => $typeName,
                'type_icon' => $typeIcon,
                'label_key' => $sectionKey,
                'categories' => $items->pluck('category_name')->values()->all(),
            ];
        }

        $components = [
            new ReportSectionComponent(
                title: '',
                emoji: '🏷️',
                items: $sections,
                translationKey: 'chat.command.category_title',
                count: $categories->count(),
            ),
        ];

        return ChatResponse::command($components, $metadata);
    }
}
