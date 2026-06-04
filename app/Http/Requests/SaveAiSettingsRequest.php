<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\AiProvider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveAiSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'provider' => ['required', Rule::enum(AiProvider::class)],
            'api_key' => ['nullable', 'string', 'max:500'],
            'selected_model' => ['required', 'string', 'max:100'],
            'is_active_provider' => ['required', 'boolean'],
        ];
    }
}
