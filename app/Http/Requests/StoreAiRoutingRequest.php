<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\AiProvider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAiRoutingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'mode' => ['required', Rule::in(['manual', 'automatic'])],
            'primary_provider' => ['nullable', Rule::enum(AiProvider::class)],
            'primary_model' => ['nullable', 'string', 'max:150'],
            'fallback_models' => ['nullable', 'array', 'max:8'],
            'fallback_models.*.provider' => ['required_with:fallback_models', Rule::enum(AiProvider::class)],
            'fallback_models.*.model' => ['required_with:fallback_models', 'string', 'max:150'],
            'fallback_models.*.account_id' => ['sometimes', 'integer', 'min:1'],
            'provider_priority' => ['nullable', 'array', 'max:10'],
            'provider_priority.*' => [Rule::enum(AiProvider::class)],
            'free_policy' => ['required', Rule::in(['free_only', 'free_first', 'paid_allowed'])],
            'allow_paid_fallback' => ['required', 'boolean'],
            'max_candidates' => ['sometimes', 'integer', 'min:1', 'max:8'],
            'max_attempts' => ['sometimes', 'integer', 'min:1', 'max:12'],
            'expand_beyond_primary' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $data = $validator->getData();
            // free_only tanpa allow_paid adalah valid; free_first + allow_paid=false juga valid (efektif free-only).
            // paid_allowed sebaiknya disertai allow_paid=true agar konsisten.
            if (($data['free_policy'] ?? '') === 'paid_allowed' && empty($data['allow_paid_fallback'])) {
                $validator->errors()->add('allow_paid_fallback', 'Untuk paid-allowed, izin paid fallback harus diaktifkan.');
            }
            if (($data['mode'] ?? '') === 'manual' && empty($data['primary_model'])) {
                $validator->errors()->add('primary_model', 'Mode manual wajib memiliki model utama.');
            }
        });
    }
}
